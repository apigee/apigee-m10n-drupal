<?php

/*
 * Copyright 2025 Google Inc.
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
use Drupal\apigee_m10n\Controller\BuyApiController;
use Drupal\apigee_m10n\Entity\XRatePlan;

/**
 * Generates the buy apis page.
 */
class TeamBuyApiController extends BuyApiController {

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
    // Get the active rate plans of the organization.
    $all_ratePlans = XRatePlan::loadAll();

    foreach ($all_ratePlans as $rate_plan) {
      $rate_plans["{$rate_plan->id()}"] = $rate_plan;
    }
    return $this->buildPage($rate_plans);
  }

}
