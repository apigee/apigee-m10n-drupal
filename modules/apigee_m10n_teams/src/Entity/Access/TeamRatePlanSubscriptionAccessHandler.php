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

namespace Drupal\apigee_m10n_teams\Entity\Access;

use Apigee\Edge\Api\Monetization\Entity\CompanyInterface;
use Apigee\Edge\Api\Monetization\Entity\CompanyRatePlanInterface;
use Apigee\Edge\Api\Monetization\Entity\DeveloperCategoryRatePlanInterface;
use Apigee\Edge\Api\Monetization\Entity\DeveloperRatePlanInterface;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\Entity\Access\RatePlanSubscriptionAccessHandler;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Access check for subscribing an account to a rate plan, checking teams.
 */
class TeamRatePlanSubscriptionAccessHandler extends RatePlanSubscriptionAccessHandler {

  /**
   * Checks access to see if a tean can subscribe to a rate plan.
   *
   * This is different than access control, as an admin might have access to
   * view and purchase a rate plan as any team, but they might not be able
   * to subscribe to the plan in a certain team context.
   *
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan_entity
   *   The rate plan drupal entity.
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The team for which we try to determine subscription access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result specifying if subscription to the rate plan is or not allowed.
   */
  public function teamAccess(RatePlanInterface $rate_plan_entity, TeamInterface $team): AccessResultInterface {
    /** @var \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface $rate_plan */
    $rate_plan = $rate_plan_entity->decorated();

    // Test access to CompanyRatePlanInterface.
    if ($rate_plan instanceof CompanyRatePlanInterface) {
      $company = $rate_plan->getCompany();
      $team_id = $company instanceof CompanyInterface ? $company->id() : NULL;
      return AccessResult::allowedIf($team_id == $team->id());
    }

    // Test access to a DeveloperCategoryRatePlanInterface, where the team has
    // the required category.
    if ($rate_plan instanceof DeveloperCategoryRatePlanInterface) {
      $category = $rate_plan->getDeveloperCategory();
      $current_team_category = $team->decorated()->getAttributeValue('MINT_DEVELOPER_CATEGORY');
      return AccessResult::allowedIf($current_team_category && $category && ($category->id() === $current_team_category));
    }

    // A team can't subscribe to a Company rate plan.
    if ($rate_plan instanceof DeveloperRatePlanInterface) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

}
