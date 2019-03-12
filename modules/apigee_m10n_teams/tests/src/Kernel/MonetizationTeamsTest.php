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
use Drupal\apigee_edge_teams\TeamPermissionHandlerInterface;
use Drupal\apigee_m10n\Entity\Package;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
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
    'user',
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

    $this->setCurrentTeamRoute($team);

    static::assertSame($team, $this->container->get('apigee_m10n.teams')->currentTeam());
  }

  /**
   * Tests the current team is retrieved.
   */
  public function testEntityAccess() {
    // Create a team Entity.
    $team_id = strtolower($this->randomMachineName(8) . '-' . $this->randomMachineName(4));
    $team = Team::create(['name' => $team_id]);
    $this->setCurrentTeamRoute($team);

    // Mock an account.
    $account = $this->prophesize(AccountInterface::class)->reveal();
    // Prophesize the `apigee_edge_teams.team_permissions` service.
    $team_handler = $this->prophesize(TeamPermissionHandlerInterface::class);
    $team_handler->getDeveloperPermissionsByTeam($team, $account)->willReturn(['view package']);
    $this->container->set('apigee_edge_teams.team_permissions', $team_handler->reveal());

    /** @var \Drupal\apigee_m10n_teams\MonetizationTeamsInterface $team_service */
    $team_service = $this->container->get('apigee_m10n.teams');

    // Create an eneity we can test against `entityAccess`.
    $entity_id = strtolower($this->randomMachineName(8) . '-' . $this->randomMachineName(4));
    // We are only using package here because it's easy.
    $entity = Package::create(['id' => $entity_id]);

    // Test view entity.
    static::assertInstanceOf(AccessResultAllowed::class, $team_service->entityAccess($entity, 'view', $account));
    // Test update entity.
    $update_result = $team_service->entityAccess($entity, 'update', $account);
    static::assertInstanceOf(AccessResultForbidden::class, $update_result);
    static::assertSame("The 'update package' permission is required.", $update_result->getReason());
    // Test delete entity.
    $delete_result = $team_service->entityAccess($entity, 'delete', $account);
    static::assertInstanceOf(AccessResultForbidden::class, $delete_result);
    static::assertSame("The 'delete package' permission is required.", $delete_result->getReason());
  }

  /**
   * Sets the current routematch to a mock that returns a team.
   *
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The team.
   */
  protected function setCurrentTeamRoute($team) {
    // Set the current route match with a mock.
    $route_match = $this->prophesize(CurrentRouteMatch::class);
    $route_match->getRouteName()->willReturn('entity.team.canonical');
    $route_match->getParameter('team')->willReturn($team);
    $this->container->set('current_route_match', $route_match->reveal());
    // The `apigee_m10n_teams_entity_type_alter` will have already loaded the
    // `apigee_m10n.teams` service so we need to make sure it is reloaded.
    $this->container->set('apigee_m10n.teams', NULL);
  }

}
