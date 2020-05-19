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

namespace Drupal\apigee_m10n_teams\Entity\ListBuilder;

use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\Entity\ListBuilder\PurchasedPlanListBuilder;
use Drupal\apigee_m10n\Entity\PurchasedPlanInterface;
use Drupal\apigee_m10n_teams\Entity\TeamsPurchasedPlan;
use Drupal\Core\Url;

/**
 * Entity list builder for team purchased plans.
 */
class TeamPurchasedPlanListBuilder extends PurchasedPlanListBuilder {

  /**
   * The team that is used to load purchases.
   *
   * @var \Drupal\apigee_edge_teams\Entity\TeamInterface
   */
  protected $team;

  /**
   * {@inheritdoc}
   */
  public function render(TeamInterface $team = NULL) {
    // From this point forward `$this->user` is a safe assumption.
    $this->team = $team;

    return parent::render();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function load() {
    return TeamsPurchasedPlan::loadByTeamId($this->team->id());
  }

  /**
   * {@inheritdoc}
   */
  protected function cancelUrl(PurchasedPlanInterface $purchased_plan) {
    return $this->ensureDestination(Url::fromRoute('entity.purchased_plan.team_cancel_form', [
      'team' => $this->team->id(),
      'purchased_plan' => $purchased_plan->id(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  protected function ratePlanUrl(PurchasedPlanInterface $purchased_plan) {
    return $this->ensureDestination(Url::fromRoute('entity.rate_plan.team', [
      'team' => $this->team->id(),
      'product_bundle' => $purchased_plan->getRatePlan()->getProductBundleId(),
      'rate_plan' => $purchased_plan->getRatePlan()->id(),
    ]));
  }

}
