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

namespace Drupal\Tests\apigee_m10n_teams\Traits;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Tests the team permission access checker.
 */
trait TeamProphecyTrait {

  /**
   * Sets the current routematch to a mock that returns a team.
   *
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The team.
   *
   * @throws \Exception
   */
  protected function setCurrentTeamRoute($team) {
    // Set the current route match with a mock.
    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getRouteName()->willReturn('entity.team.canonical');
    $route_match->getParameter('team')->willReturn($team);
    $route_match->getParameter('user')->willReturn(NULL);
    $this->container->set('current_route_match', $route_match->reveal());
    // The `apigee_m10n_teams_entity_type_alter` will have already loaded the
    // `apigee_m10n.teams` service so we need to make sure it is reloaded.
    $this->container->set('apigee_m10n.teams', NULL);

    static::assertSame($team, $this->container->get('apigee_m10n.teams')->currentTeam());
  }

}
