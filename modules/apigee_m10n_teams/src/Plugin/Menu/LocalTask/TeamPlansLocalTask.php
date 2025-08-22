<?php

namespace Drupal\apigee_m10n_teams\Plugin\Menu\LocalTask;

use Drupal\Core\Menu\LocalTaskDefault;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides dynamic tabs for team plans.
 *
 * @LocalTask(
 *   id = "apigee_m10n_teams.team_plans",
 *   route_name = "apigee_monetization.team_plans",
 *   base_route = "entity.team.canonical",
 *   parent_id = "apigee_m10n_teams.balance_and_plans",
 *   weight = 1
 * )
 * @LocalTask(
 *   id = "apigee_m10n_teams.team_xplans",
 *   route_name = "apigee_monetization.team_xplans",
 *   base_route = "entity.team.canonical",
 *   parent_id = "apigee_m10n_teams.balance_and_plans",
 *   weight = 1
 * )
 */
class TeamPlansLocalTask extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle(?Request $request = NULL): string {
    $monetization = \Drupal::service('apigee_m10n.monetization');
    if ($monetization->isOrganizationApigeeXorHybrid()) {
      return 'Buy API';
    }
    else {
      return 'Pricing & plans';
    }
  }

}
