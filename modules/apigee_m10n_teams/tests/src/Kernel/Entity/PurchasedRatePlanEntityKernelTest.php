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

use Apigee\Edge\Api\Monetization\Entity\Company;
use Apigee\Edge\Api\Monetization\Entity\Developer;
use Drupal\apigee_edge_teams\Entity\Team;
use Drupal\apigee_edge_teams\Entity\TeamRoleInterface;
use Drupal\apigee_m10n\Entity\PurchasedPlan;
use Drupal\apigee_m10n\Entity\RatePlan;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\apigee_m10n_teams\Entity\TeamsPurchasedPlan;
use Drupal\apigee_m10n_teams\Entity\TeamsPurchasedPlanInterface;
use Drupal\apigee_m10n_teams\Entity\TeamsRatePlan;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n_teams\Kernel\MonetizationTeamsKernelTestBase;
use Drupal\Tests\apigee_m10n_teams\Traits\TeamProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the team rate plan entity.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class PurchasedRatePlanEntityKernelTest extends MonetizationTeamsKernelTestBase {

  use TeamProphecyTrait;

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
   * A test product bundle.
   *
   * @var \Drupal\apigee_m10n\Entity\ProductBundleInterface
   */
  protected $product_bundle;

  /**
   * A test rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $rate_plan;

  /**
   * A test team purchased rate plan.
   *
   * @var \Drupal\apigee_m10n_teams\Entity\TeamsPurchasedPlanInterface
   */
  protected $purchased_plan;

  /**
   * Team role storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $team_role_storage;

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
    ]);

    // Enable the Classy theme.
    \Drupal::service('theme_handler')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();

    $this->team_role_storage = \Drupal::entityTypeManager()->getStorage('team_role');

    // Makes sure the new user isn't root.
    $this->createAccount();
    $this->developer = $this->createAccount();
    $this->team = $this->createTeam();
    $this->addUserToTeam($this->team, $this->developer);

    $this->createCurrentUserSession($this->developer);

    $this->product_bundle = $this->createProductBundle();
    $this->rate_plan = $this->createRatePlan($this->product_bundle);
    $this->purchased_plan = $this->createTeamPurchasedPlan($this->team, $this->rate_plan);

    $this->warmOrganizationCache();
  }

  /**
   * Test team member can not view purchased plans.
   *
   * @throws \Exception
   */
  public function testPurchasedRatePlanEntityNoAccess() {
    $new_permissions = $this->getDefaultTeamRolePermissions() + [
      'view purchased_plan' => 0,
    ];
    $this->team_role_storage->changePermissions(TeamRoleInterface::TEAM_MEMBER_ROLE, $new_permissions);

    $url = Url::fromRoute('entity.purchased_plan.team_collection', [
      'team' => $this->team->id(),
    ]);
    static::assertSame("/teams/{$this->team->id()}/monetization/purchased-plans", $url->toString());

    $this->stack
      ->queueMockResponse(['get_company_purchased_plans' => ['purchased_plans' => [$this->purchased_plan]]]);
    $this->stack
      ->queueMockResponse(['companies' => ['companies' => [$this->team->id()]]]);
    $request = Request::create($url->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);

    $this->setRawContent($response->getContent());
    static::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
  }

  /**
   * Test team member can view purchased plans.
   *
   * @throws \Exception
   */
  public function testPurchasedRatePlanEntityHasAccess() {
    $url = Url::fromRoute('entity.purchased_plan.team_collection', [
      'team' => $this->team->id(),
    ]);
    static::assertSame("/teams/{$this->team->id()}/monetization/purchased-plans", $url->toString());

    $this->stack
      ->queueMockResponse(['get_company_purchased_plans' => ['purchased_plans' => [$this->purchased_plan]]]);
    $this->stack
      ->queueMockResponse(['companies' => ['companies' => [$this->team->id()]]]);
    $request = Request::create($url->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);

    $this->setRawContent($response->getContent());
    static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    $this->assertText('Purchased plans');
    $this->assertText('Active and Future Purchased Plans');
    $this->assertLink($this->rate_plan->label());
  }

  /**
   * Gets the default array of team permissions for the member role.
   *
   * @return array
   *   An array of permissions.
   */
  protected function getDefaultTeamRolePermissions() {
    return [
      'api_product_access_internal' => 0,
      'api_product_access_private' => 1,
      'api_product_access_public' => 1,
      'refresh prepaid balance' => 0,
      'view prepaid balance' => 0,
      'view prepaid balance report' => 0,
      'edit billing details' => 0,
      'view product_bundle' => 0,
      'purchase rate_plan' => 0,
      'update purchased_plan' => 0,
      'view purchased_plan' => 0,
      'view rate_plan' => 0,
      'team_manage_members' => 0,
      'team_app_create' => 1,
      'team_app_delete' => 0,
      'team_app_update' => 1,
      'team_app_view' => 1,
      'team_app_analytics' => 1,
    ];
  }

  /**
   * Creates a team purchased plan.
   *
   * @param \Drupal\apigee_edge_teams\Entity\Team $team
   *   The team to purchase the rate plan.
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan
   *   The rate plan to purchase.
   *
   * @return \Drupal\apigee_m10n_teams\Entity\TeamsPurchasedPlanInterface
   *   The team purchased plan.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  protected function createTeamPurchasedPlan(Team $team, RatePlanInterface $rate_plan): TeamsPurchasedPlanInterface {
    $start_date = new \DateTimeImmutable('today', new \DateTimeZone($this->org_default_timezone));
    $purchased_plan = TeamsPurchasedPlan::create([
      'ratePlan' => $rate_plan,
      'company' => new Company([
        'id' => $team->id(),
        'legalName' => $team->getName(),
      ]),
      'startDate' => $start_date,
    ]);

    $this->stack->queueMockResponse(['team_purchased_plan' => ['purchased_plan' => $purchased_plan]]);
    $purchased_plan->save();

    // Warm the cache for this purchased_plan.
    $purchased_plan->set('id', $this->getRandomUniqueId());
    $this->stack->queueMockResponse(['team_purchased_plan' => ['purchased_plan' => $purchased_plan]]);
    $purchased_plan = TeamsPurchasedPlan::load($purchased_plan->id());
    $this->assertTrue($purchased_plan instanceof TeamsPurchasedPlanInterface);

    // Make sure the start date is unchanged while loading.
    static::assertEquals($start_date, $purchased_plan->decorated()->getStartDate());

    // The purchased_plan controller does not have a delete operation so there
    // is nothing to add to the cleanup queue.
    return $purchased_plan;
  }

}
