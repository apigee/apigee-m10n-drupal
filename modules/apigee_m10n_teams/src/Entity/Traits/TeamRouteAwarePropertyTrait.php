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

namespace Drupal\apigee_m10n_teams\Entity\Traits;

use Drupal\apigee_edge_teams\Entity\Team;
use Drupal\apigee_edge_teams\Entity\TeamInterface;

/**
 * Provides team route related helpers.
 */
trait TeamRouteAwarePropertyTrait {

  /**
   * Get the team ID from the current route match.
   *
   * @return string
   *   Returns the team ID.
   */
  private function getTeamId(): ?string {
    // Get the team from the route match.
    $team = \Drupal::routeMatch()->getParameter('team');
    // Sometimes the param converter has converted the team to an entity.
    return $team instanceof TeamInterface ? $team->id() : $team;
  }

  /**
   * Get the team from the current route match.
   *
   * @return \Drupal\apigee_edge_teams\Entity\TeamInterface
   *   Returns the team.
   */
  private function getTeam(): ?TeamInterface {
    // The route parameters still need to be set.
    $team = \Drupal::routeMatch()->getParameter('team');
    // Sometimes the param converter has converted the team to an entity.
    return $team instanceof TeamInterface
      ? $team
      : (!empty($team) ? Team::load($team) : NULL);
  }

}
