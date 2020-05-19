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

namespace Drupal\apigee_m10n_teams\Entity\Controller;

use Apigee\Edge\Api\Monetization\Entity\Company;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\Entity\Controller\PurchaseRatePlanController;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\apigee_m10n\Entity\PurchasedPlan;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Controller for subscribing to rate plans.
 */
class TeamPurchaseRatePlanController extends PurchaseRatePlanController {

  /**
   * Page callback to create a new team purchased_plan.
   *
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The team purchasing the rate plan.
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan
   *   The rate plan.
   *
   * @return array
   *   A purchase form render array.
   *
   * @throws \Exception
   */
  public function teamPurchaseForm(TeamInterface $team, RatePlanInterface $rate_plan) {
    // Create a purchased_plan to pass to the purchased_plan edit form.
    $purchased_plan = PurchasedPlan::create([
      'ratePlan' => $rate_plan,
      'company' => new Company(['id' => $team->id()]),
      'startDate' => new \DateTimeImmutable(),
    ]);

    // Return the purchase form with the label set.
    return $this->entityFormBuilder->getForm($purchased_plan, 'default', [
      'save_label' => $this->t('Purchase'),
    ]);
  }

  /**
   * Gets the title for the purchase page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan
   *   The rate plan.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title.
   */
  public function teamTitle(RouteMatchInterface $route_match, RatePlanInterface $rate_plan) {
    return $this->title($route_match, NULL, $rate_plan);
  }

}
