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

namespace Drupal\Tests\apigee_m10n_teams\Traits;

use Apigee\Edge\Api\Monetization\Entity\Company;
use Drupal\apigee_edge_teams\Entity\Team;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\apigee_m10n\EnvironmentVariable;
use Drupal\apigee_m10n_teams\Entity\TeamsPurchasedPlanInterface;

/**
 * Setup helpers for monetization team tests.
 */
trait ApigeeMonetizationTeamsTestTrait {

  /**
   * Gets the default array of team permissions for the member role.
   *
   * @return array
   *   An array of permissions.
   */
  public function getDefaultTeamRolePermissions():array {
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
  public function createTeamPurchasedPlan(Team $team, RatePlanInterface $rate_plan): TeamsPurchasedPlanInterface {
    $purchased_plan_storage = \Drupal::entityTypeManager()->getStorage('purchased_plan');
    $start_date = new \DateTimeImmutable('today', new \DateTimeZone($this->org_default_timezone));
    $purchased_plan = $purchased_plan_storage->create([
      'ratePlan' => $rate_plan,
      'company' => new Company([
        'id' => $team->id(),
        'legalName' => $team->getName(),
      ]),
      'startDate' => $start_date,
    ]);

    $this->stack->queueMockResponse(['team_purchased_plan' => ['purchased_plan' => $purchased_plan->decorated()]]);
    $purchased_plan->save();

    // Warm the cache for this purchased_plan.
    $integration_enabled = !empty(getenv(EnvironmentVariable::APIGEE_INTEGRATION_ENABLE));
    if (!$integration_enabled) {
      // Only set the ID if using the mock client, otherwise the previous
      // save() call should have set the ID.
      $purchased_plan->set('id', $this->getRandomUniqueId());
    }
    $this->stack->queueMockResponse(['team_purchased_plan' => ['purchased_plan' => $purchased_plan->decorated()]]);
    $purchased_plan = $purchased_plan_storage->load($purchased_plan->id());
    $this->assertTrue($purchased_plan instanceof TeamsPurchasedPlanInterface);

    // Make sure the start date is unchanged while loading.
    static::assertEquals($start_date, $purchased_plan->decorated()->getStartDate());

    // The purchased_plan controller does not have a delete operation so there
    // is nothing to add to the cleanup queue.
    return $purchased_plan;
  }

}
