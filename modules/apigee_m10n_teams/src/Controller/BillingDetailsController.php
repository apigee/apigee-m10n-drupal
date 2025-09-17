<?php

/**
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

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n_teams\MonetizationTeamsInterface;

/**
 * BillingDetailsController.
 */
class BillingDetailsController extends ControllerBase {

  /**
   * The teams monetization service.
   *
   * @var \Drupal\apigee_m10n_teams\MonetizationTeamsInterface
   */
  protected $team_monetization;

  /**
   * BillingDetailsController constructor.
   *
   * @param \Drupal\apigee_m10n_teams\MonetizationTeamsInterface $team_monetization
   *   Teams monetization service.
   */
  public function __construct(MonetizationTeamsInterface $team_monetization) {
    $this->team_monetization = $team_monetization;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('apigee_m10n.teams')
    );
  }

  /**
   * Displays the teams billing type.
   *
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   Appgroup entity.
   *
   * @return array
   *   The billing type array.
   */
  public function billingType(TeamInterface $team) {
    $team_billing_type = $this->team_monetization->getAppGroupBillingtype($team);
    $build = [
      '#type' => 'Page',
      '#prefix' => 'Billing Type : ',
      '#markup' => $team_billing_type ? $team_billing_type : 'Not Specified (Defaults to Postpaid)',
    ];
    return $build;
  }

}
