<?php

/*
 * Copyright 2019 Google Inc.
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

use Drupal\apigee_edge_teams\Entity\TeamRoleInterface;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n_teams\Kernel\MonetizationTeamsKernelTestBase;
use Drupal\Tests\apigee_m10n_teams\Traits\ApigeeMonetizationTeamsTestTrait;
use Drupal\Tests\apigee_m10n_teams\Traits\TeamProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
  use ApigeeMonetizationTeamsTestTrait;

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $member;
  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $non_member;

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
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);

    // Enable the Olivero theme.
    \Drupal::service('theme_installer')->install(['olivero']);
    $this->config('system.theme')->set('default', 'olivero')->save();

    $this->team_role_storage = \Drupal::entityTypeManager()->getStorage('team_role');

    // Makes sure the new user isn't root.
    $this->createAccount();
    $this->non_member = $this->createAccount();
    $this->member = $this->createAccount();
    $this->team = $this->createTeam();
    $this->addUserToTeam($this->team, $this->member);

    $this->product_bundle = $this->createProductBundle();
    $this->rate_plan = $this->createRatePlan($this->product_bundle);
    $this->purchased_plan = $this->createTeamPurchasedPlan($this->team, $this->rate_plan);

    $this->warmTnsCache();
    $this->warmTeamTnsCache($this->team);
  }

  /**
   * Test non-team member can not view purchased plans.
   *
   * @throws \Exception
   */
  public function testPurchasedRatePlanEntityNonMemberAccess() {
    $this->queueDeveloperResponse($this->non_member);
    $this->createCurrentUserSession($this->non_member);

    $url = Url::fromRoute('entity.purchased_plan.team_collection', [
      'team' => $this->team->id(),
    ]);
    static::assertSame("/teams/{$this->team->id()}/monetization/purchased-plans", $url->toString());

    $request = Request::create($url->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);

    static::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
  }

  /**
   * Test team member doesn't have permission to view purchased plans.
   *
   * @throws \Exception
   */
  public function testPurchasedRatePlanEntityNoAccess() {
    $this->createCurrentUserSession($this->member);

    $new_permissions = [
      'view purchased_plan' => 0,
      'update purchased_plan' => 0,
      'purchase rate_plan' => 0,
    ] + $this->getDefaultTeamRolePermissions();
    $this->team_role_storage->changePermissions(TeamRoleInterface::TEAM_MEMBER_ROLE, $new_permissions);

    $url = Url::fromRoute('entity.purchased_plan.team_collection', [
      'team' => $this->team->id(),
    ]);
    static::assertSame("/teams/{$this->team->id()}/monetization/purchased-plans", $url->toString());

    $request = Request::create($url->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);

    static::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
  }

  /**
   * Test team member can view purchased plans, and purchase.
   *
   * @throws \Exception
   */
  public function testPurchasedRatePlanEntityHasAccess() {
    $this->createCurrentUserSession($this->member);

    $new_permissions = [
      'view purchased_plan' => 1,
      'update purchased_plan' => 1,
      'purchase rate_plan' => 1,
      'view rate_plan' => 1,
    ] + $this->getDefaultTeamRolePermissions();
    $this->team_role_storage->changePermissions(TeamRoleInterface::TEAM_MEMBER_ROLE, $new_permissions);

    $this->memberViewPurchasedRatePlans();
    $this->memberCancelRatePlanPage();
    $this->memberPurchaseRatePlanPage();
  }

  /**
   * Test member with permission to view team purchased rate plans.
   */
  protected function memberViewPurchasedRatePlans() {
    $url = Url::fromRoute('entity.purchased_plan.team_collection', [
      'team' => $this->team->id(),
    ]);
    static::assertSame("/teams/{$this->team->id()}/monetization/purchased-plans", $url->toString());

    $this->stack
      ->queueMockResponse(['get_company_purchased_plans' => ['purchased_plans' => [$this->purchased_plan->decorated()]]]);

    $request = Request::create($url->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);

    $this->setRawContent($response->getContent());
    static::assertSame(Response::HTTP_OK, $response->getStatusCode());
    // Checking "Active and Future Purchased Plans" table columns.
    // As div generated for startdate is different for Drupal 10.1
    // hence need to check Drupal version and select the div.
    $div_to_use = floatval(\Drupal::VERSION) <= 10.0 ? '.purchased-plan-row:nth-child(1) td.purchased-plan-start-date div' : '.purchased-plan-row:nth-child(1) td.purchased-plan-start-date div time';

    $this->assertCssElementText('.purchased-plan-row:nth-child(1) td.purchased-plan-status span', 'Active');
    $this->assertCssElementText('.purchased-plan-row:nth-child(1) td.purchased-plan-rate-plan a', $this->rate_plan->getDisplayName());
    $this->assertCssElementText($div_to_use, $this->purchased_plan->getStartDate()->format('m/d/Y'));
    $this->assertCssElementText('.purchased-plan-row:nth-child(1) td.purchased-plan-end-date', '');
  }

  /**
   * Purchase rate plan page with member with permission.
   */
  protected function memberPurchaseRatePlanPage() {
    $url = Url::fromRoute('entity.rate_plan.team_purchase', [
      'team' => $this->team->id(),
      'product_bundle' => $this->product_bundle->id(),
      'rate_plan' => $this->rate_plan->id(),
    ]);
    static::assertSame("/teams/{$this->team->id()}/monetization/product-bundle/{$this->product_bundle->id()}/plan/{$this->rate_plan->id()}/purchase", $url->toString());

    $this->stack
      ->queueMockResponse(['get_company_purchased_plans' => ['purchased_plans' => [$this->purchased_plan->decorated()]]]);

    $request = Request::create($url->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);

    static::assertSame(Response::HTTP_OK, $response->getStatusCode());
  }

  /**
   * Cancel rate plan page with member with permission.
   */
  protected function memberCancelRatePlanPage() {
    $url = Url::fromRoute('entity.purchased_plan.team_cancel_form', [
      'team' => $this->team->id(),
      'purchased_plan' => $this->purchased_plan->id(),
    ]);
    static::assertSame("/teams/{$this->team->id()}/monetization/purchased-plan/{$this->purchased_plan->id()}/cancel", $url->toString());

    $request = Request::create($url->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);

    static::assertSame(Response::HTTP_OK, $response->getStatusCode());
  }

}
