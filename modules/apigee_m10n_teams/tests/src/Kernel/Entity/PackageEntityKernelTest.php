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

namespace Drupal\Tests\apigee_m10n_teams\Kernel\Entity;

use Drupal\apigee_m10n\Entity\Package;
use Drupal\apigee_m10n_teams\Entity\TeamsPackageInterface;
use Drupal\Tests\apigee_m10n\Traits\RatePlansPropertyEnablerTrait;
use Drupal\Tests\apigee_m10n_teams\Kernel\MonetizationTeamsKernelTestBase;
use Drupal\Tests\apigee_m10n_teams\Traits\TeamProphecyTrait;

/**
 * Tests the team package entity.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class PackageEntityKernelTest extends MonetizationTeamsKernelTestBase {

  use TeamProphecyTrait;
  use RatePlansPropertyEnablerTrait;

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * An apigee team.
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
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
      'apigee_m10n',
    ]);

    // Enable the Classy theme.
    \Drupal::service('theme_handler')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();

    // Makes sure the new user isn't root.
    $this->createAccount();
    $this->developer = $this->createAccount();
    $this->team = $this->createTeam();
    $this->addUserToTeam($this->team, $this->developer);

    $this->createCurrentUserSession($this->developer);

    $this->package = $this->createPackage();
  }

  /**
   * Test team package entity rendering.
   *
   * @throws \Exception
   */
  public function testPackageEntity() {
    $this->setCurrentTeamRoute($this->team);
    $this->warmTnsCache();
    $this->warmTeamTnsCache($this->team);

    // Check team access.
    static::assertTrue($this->package->access('view', $this->developer));

    // Make sure we get a team context when getting a package url.
    $url = $this->package->toUrl('team');
    static::assertSame("/teams/{$this->team->id()}/monetization/package/{$this->package->id()}", $url->toString());
    static::assertSame('entity.package.team', $url->getRouteName());

    // Load the cached package.
    $package = Package::load($this->package->id());

    static::assertInstanceOf(TeamsPackageInterface::class, $package);
    // Use the object comparator to compare the loaded package.
    static::assertEquals($this->package, $package);

    // Get the package products.
    static::assertGreaterThan(0, $this->count($package->getApiProducts()));

    // Render the package.
    $build = \Drupal::entityTypeManager()
      ->getViewBuilder('package')
      ->view($this->package, 'default');

    $rate_plan_1 = $this->createRatePlan($this->package);
    $rate_plan_2 = $this->createRatePlan($this->package);

    $this->stack->queueMockResponse(['get_monetization_package_plans' => ['plans' => [$rate_plan_1, $rate_plan_2]]]);
    $content = \Drupal::service('renderer')->renderRoot($build);

    $this->setRawContent((string) $content);

    $css_prefix = '.apigee-entity.package';
    // Package detail as rendered.
    $this->assertCssElementText("{$css_prefix} > h2", $package->label());

    // API Products.
    $this->assertCssElementText("{$css_prefix} .field--name-apiproducts .field__label", 'Included products');
    foreach ($this->package->get('apiProducts') as $index => $apiProduct) {
      // CSS indexes start at 1.
      $css_index = $index + 1;
      $this->assertCssElementText("{$css_prefix} .field--name-apiproducts .field__item:nth-child({$css_index})", $apiProduct->entity->label());
    }

    // Rate plans as rendered.
    foreach ([$rate_plan_1, $rate_plan_2] as $index => $rate_plan) {
      /** @var \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan */
      $this->assertLink($rate_plan->label());
      $this->assertLinkByHref($rate_plan->toUrl()->toString());
    }
  }

}
