<?php

/*
 * Copyright 2018 Google Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2 as published by the
 * Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public
 * License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

namespace Drupal\Tests\apigee_m10n_teams\Kernel;

use Drupal\apigee_edge_teams\Entity\Team;
use Drupal\apigee_edge_teams\TeamPermissionHandlerInterface;
use Drupal\apigee_m10n\Entity\Package;
use Drupal\apigee_m10n_teams\Entity\Storage\TeamPackageStorageInterface;
use Drupal\apigee_m10n_teams\Entity\Storage\TeamSubscriptionStorageInterface;
use Drupal\apigee_m10n_teams\Entity\TeamsPackageInterface;
use Drupal\apigee_m10n_teams\Entity\TeamsRatePlan;
use Drupal\apigee_m10n_teams\Entity\TeamsSubscriptionInterface;
use Drupal\apigee_m10n_teams\Plugin\Field\FieldFormatter\TeamSubscribeFormFormatter;
use Drupal\apigee_m10n_teams\Plugin\Field\FieldFormatter\TeamSubscribeLinkFormatter;
use Drupal\apigee_m10n_teams\Plugin\Field\FieldWidget\CompanyTermsAndConditionsWidget;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\apigee_m10n\Traits\AccountProphecyTrait;
use Drupal\Tests\apigee_m10n_teams\Traits\TeamProphecyTrait;

/**
 * Tests the module affected overrides are overridden properly.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class MonetizationTeamsTest extends KernelTestBase {

  use AccountProphecyTrait;
  use TeamProphecyTrait;

  /**
   * A test team.
   *
   * @var \Drupal\apigee_edge_teams\Entity\TeamInterface
   */
  protected $team;

  /**
   * A test package.
   *
   * @var \Drupal\apigee_m10n\Entity\PackageInterface
   */
  protected $package;

  /**
   * A test rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $rate_plan;

  /**
   * A test subscription.
   *
   * @var \Drupal\apigee_m10n\Entity\SubscriptionInterface
   */
  protected $subscription;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entity_type_manager;

  public static $modules = [
    'key',
    'user',
    'apigee_edge',
    'apigee_edge_teams',
    'apigee_m10n',
    'apigee_m10n_teams',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $this->entity_type_manager = $this->container->get('entity_type.manager');

    // Create a team Entity.
    $this->team = Team::create(['name' => strtolower($this->randomMachineName(8) . '-' . $this->randomMachineName(4))]);

    $this->package = $this->entity_type_manager->getStorage('package')->create([
      'id' => $this->randomMachineName(),
    ]);
    $this->rate_plan = $this->entity_type_manager->getStorage('rate_plan')->create([
      'id' => $this->randomMachineName(),
      'package' => $this->package,
    ]);
    $this->subscription = $this->entity_type_manager->getStorage('subscription')->create([
      'id' => $this->randomMachineName(),
      'rate_plan' => $this->rate_plan,
    ]);
  }

  /**
   * Runs all of the assertions in this test suite.
   */
  public function testAll() {
    $this->assertCurrentTeam();
    $this->assertEntityAlter();
    $this->assertEntityAccess();
    $this->assertEntityLinks();
    $this->assertFieldOverrides();
  }

  /**
   * Make sure the monetization service returns the current team.
   */
  public function assertCurrentTeam() {
    $this->setCurrentTeamRoute($this->team);
    static::assertSame($this->team, \Drupal::service('apigee_m10n.teams')->currentTeam());
  }

  /**
   * Tests entity classes are overridden.
   */
  public function assertEntityAlter() {
    // Check class overrides.
    static::assertInstanceOf(TeamsPackageInterface::class, $this->package);
    static::assertInstanceOf(TeamsRatePlan::class, $this->rate_plan);
    static::assertInstanceOf(TeamsSubscriptionInterface::class, $this->subscription);

    // Check storage overrides.
    static::assertInstanceOf(TeamPackageStorageInterface::class, $this->entity_type_manager->getStorage('package'));
    static::assertInstanceOf(TeamSubscriptionStorageInterface::class, $this->entity_type_manager->getStorage('subscription'));
  }

  /**
   * Tests team entity access.
   */
  public function assertEntityAccess() {
    // Mock an account.
    $account = $this->prophesizeAccount();
    $non_member = $this->prophesizeAccount();

    // Prophesize the `apigee_edge_teams.team_permissions` service.
    $team_handler = $this->prophesize(TeamPermissionHandlerInterface::class);
    $team_handler->getDeveloperPermissionsByTeam($this->team, $account)->willReturn([
      'view package',
      'view rate_plan',
      'subscribe rate_plan',
    ]);
    $team_handler->getDeveloperPermissionsByTeam($this->team, $non_member)->willReturn([]);
    $this->container->set('apigee_edge_teams.team_permissions', $team_handler->reveal());

    // Create an entity we can test against `entityAccess`.
    $entity_id = strtolower($this->randomMachineName(8) . '-' . $this->randomMachineName(4));
    // We are only using package here because it's easy.
    $package = Package::create(['id' => $entity_id]);

    // Test view package for a team member.
    static::assertTrue($package->access('view', $account));
    // Test view package for a non team member.
    static::assertFalse($package->access('view', $non_member));

    // Populate the entity cache with the rate plan's API package because it
    // will be loaded when the rate plan cache tags are loaded.
    \Drupal::service('entity.memory_cache')->set("values:package:{$this->package->id()}", $this->package);

    // Test view rate plan for a team member.
    static::assertTrue($this->rate_plan->access('view', $account));
    // Test view rate plan for a non team member.
    static::assertFalse($this->rate_plan->access('view', $non_member));

    // Test view rate plan for a team member.
    static::assertTrue($this->rate_plan->access('subscribe', $account));
    // Test view rate plan for a non team member.
    static::assertFalse($this->rate_plan->access('subscribe', $non_member));
  }

  /**
   * Make sure the monetization service returns the current team.
   */
  public function assertEntityLinks() {
    // Package team url.
    static::assertSame("/teams/{$this->team->id()}/monetization/package/{$this->package->id()}", $this->package->toUrl('team')->toString());
    // Rate plan team url.
    static::assertSame("/teams/{$this->team->id()}/monetization/package/{$this->package->id()}/plan/{$this->rate_plan->id()}", $this->rate_plan->toUrl('team')->toString());
    static::assertSame("/teams/{$this->team->id()}/monetization/package/{$this->package->id()}/plan/{$this->rate_plan->id()}/subscribe", $this->rate_plan->toUrl('team-subscribe')->toString());
    // Team subscription URLs.
    static::assertSame("/teams/{$this->team->id()}/monetization/subscriptions", Url::fromRoute('entity.subscription.team_collection', ['team' => $this->team->id()])->toString());
    static::assertSame("/teams/{$this->team->id()}/monetization/subscription/{$this->subscription->id()}/unsubscribe", Url::fromRoute('entity.subscription.team_unsubscribe_form', [
      'team' => $this->team->id(),
      'subscription' => $this->subscription->id(),
    ])->toString());
  }

  /**
   * Make sure fields were overridden.
   */
  public function assertFieldOverrides() {
    /** @var \Drupal\Core\Field\FormatterPluginManager $formatter_manager */
    $formatter_manager = \Drupal::service('plugin.manager.field.formatter');
    /** @var \Drupal\Core\Field\WidgetPluginManager $widget_manager */
    $widget_manager = \Drupal::service('plugin.manager.field.widget');

    // Confirm formatter overrides.
    static::assertSame(TeamSubscribeFormFormatter::class, $formatter_manager->getDefinition('apigee_subscribe_form')['class']);
    static::assertSame(TeamSubscribeLinkFormatter::class, $formatter_manager->getDefinition('apigee_subscribe_link')['class']);

    // Confirm widget overrides.
    static::assertSame(CompanyTermsAndConditionsWidget::class, $widget_manager->getDefinition('apigee_tnc_widget')['class']);
  }

}
