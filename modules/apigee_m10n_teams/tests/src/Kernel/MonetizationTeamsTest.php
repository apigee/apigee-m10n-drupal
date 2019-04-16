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
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Prophecy\Argument;

/**
 * Tests the module affected overrides are overridden properly.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class MonetizationTeamsTest extends KernelTestBase {

  /**
   * A test team.
   *
   * @var \Drupal\apigee_edge_teams\Entity\TeamInterface
   */
  protected $team;

  public static $modules = [
    'key',
    'user',
    'apigee_edge',
    'apigee_edge_teams',
    'apigee_m10n',
    'apigee_m10n_teams',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a team Entity.
    $this->team = Team::create(['name' => strtolower($this->randomMachineName(8) . '-' . $this->randomMachineName(4))]);
  }

  /**
   * Runs all of the assertions in this test suite.
   */
  public function testAll() {
    $this->assertEntityAccess();
  }

  /**
   * Tests team entity access.
   */
  public function assertEntityAccess() {
    $this->setCurrentTeamRoute($this->team);

    // Mock an account.
    $account = $this->prophesizeAccount();
    $non_member = $this->prophesizeAccount();

    // Prophesize the `apigee_edge_teams.team_permissions` service.
    $team_handler = $this->prophesize(TeamPermissionHandlerInterface::class);
    $team_handler->getDeveloperPermissionsByTeam($this->team, $account)->willReturn([
      'view package',
      'view rate_plan',
      'subscribe rate_plan',
    ]);
    $team_handler->getDeveloperPermissionsByTeam($this->team, $non_member)->willReturn([]);
    $this->container->set('apigee_edge_teams.team_permissions', $team_handler->reveal());

    // Create an entity we can test against `entityAccess`.
    $entity_id = strtolower($this->randomMachineName(8) . '-' . $this->randomMachineName(4));
    // We are only using package here because it's easy.
    $package = Package::create(['id' => $entity_id]);

    // Test view package for a team member.
    static::assertTrue($package->access('view', $account));
    // Test view package for a non team member.
    static::assertFalse($package->access('view', $non_member));
  }

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
    $route_match = $this->prophesize(CurrentRouteMatch::class);
    $route_match->getRouteName()->willReturn('entity.team.canonical');
    $route_match->getParameter('team')->willReturn($team);
    $this->container->set('current_route_match', $route_match->reveal());
    // The `apigee_m10n_teams_entity_type_alter` will have already loaded the
    // `apigee_m10n.teams` service so we need to make sure it is reloaded.
    $this->container->set('apigee_m10n.teams', NULL);

    static::assertSame($team, $this->container->get('apigee_m10n.teams')->currentTeam());
  }

  /**
   * Prophesize a user account.
   *
   * @param array $permissions
   *   Any permissions in the account should have.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user account.
   */
  protected function prophesizeAccount($permissions = []) {
    static $uid = 1;
    return $this->prophesize(AccountInterface::class)
      ->isAnonymous()->willReturn(FALSE)->getObjectProphecy()
      ->id()->willReturn($uid++)->getObjectProphecy()
      ->hasPermission(Argument::any())->will(function ($args) use ($permissions) {
        return in_array($args[0], $permissions);
      })->getObjectProphecy()
      ->reveal();
  }

}
