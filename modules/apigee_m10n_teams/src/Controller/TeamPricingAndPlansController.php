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

namespace Drupal\apigee_m10n_teams\Controller;

use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\Controller\PricingAndPlansController;
use Drupal\apigee_m10n_teams\Entity\TeamProductBundle;
use Drupal\Core\Access\AccessResult;

/**
 * Generates the pricing and plans page.
 */
class TeamPricingAndPlansController extends PricingAndPlansController {

  /**
   * Gets a list of available plans for this user.
   *
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The drupal user/developer.
   *
   * @return array
   *   The plans render array.
   */
  public function teamCatalogPage(TeamInterface $team) {
    $rate_plans = [];
    $subscription_handler = \Drupal::entityTypeManager()->getHandler('rate_plan', 'subscription_access');

    // Load rate plans for each product bundle.
    foreach (TeamProductBundle::getAvailableProductBundlesByTeam($team->id()) as $product_bundle) {
      /** @var \Drupal\apigee_m10n\Entity\ProductBundleInterface $product_bundle */
      foreach ($product_bundle->get('ratePlans') as $rate_plan) {

        /** @var \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan_entity */
        $rate_plan_entity = $rate_plan->entity;
        if ($subscription_handler->teamAccess($rate_plan_entity, $team) == AccessResult::allowed()) {
          $rate_plans["{$product_bundle->id()}:{$rate_plan->target_id}"] = $rate_plan->entity;
        }
      };
    }

    return $this->buildPage($rate_plans);
  }

}
