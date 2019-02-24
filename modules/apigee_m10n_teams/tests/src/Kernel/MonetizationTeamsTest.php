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

namespace Drupal\Tests\apigee_m10n_teams\Kernel;

use Drupal\apigee_edge_teams\Entity\Team;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the module affected overrides are overridden properly.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class MonetizationTeamsTest extends KernelTestBase {

  public static $modules = [
    'key',
    'apigee_edge',
    'apigee_edge_teams',
    'apigee_m10n',
    'apigee_m10n_teams',
  ];

  /**
   * Tests the current team is retrieved.
   */
  public function testCurrentTeam() {
    $team_id = strtolower($this->randomMachineName(8) . '-' . $this->randomMachineName(4));
    $team = Team::create(['name' => $team_id]);

    // Set the current route match with a mock.
    $route_match = $this->prophesize(CurrentRouteMatch::class);
    $route_match->getRouteName()->willReturn('entity.team.canonical');
    $route_match->getParameter('team')->willReturn($team);
    $this->container->set('current_route_match', $route_match->reveal());
    // The `apigee_m10n_teams_entity_type_alter` will have already loaded the
    // `apigee_m10n.teams` service so we need to make sure it is reloaded.
    $this->container->set('apigee_m10n.teams', NULL);

    static::assertSame($team, $this->container->get('apigee_m10n.teams')->currentTeam());
  }

}
