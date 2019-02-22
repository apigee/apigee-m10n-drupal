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

namespace Drupal\Tests\apigee_m10n_teams\Unit;

use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_edge_teams\TeamPermissionHandlerInterface;
use Drupal\apigee_m10n_teams\Access\TeamPermissionAccessCheck;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Tests the team permission access checker.
 *
 * @group apigee_m10n
 * @group apigee_m10n_unit
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_unit
 */
class TeamPermissionAccessCheckUnitTest extends UnitTestCase {

  /**
   * The team.
   *
   * @var \Drupal\apigee_edge_teams\Entity\TeamInterface
   */
  protected $team;

  /**
   * The user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Mock the team.
    $team = $this->prophesize(TeamInterface::class);
    $team->id()->willReturn('team-a');
    $team->getCacheContexts()->willReturn([]);
    $team->getCacheTags()->willReturn(['team:team-a']);
    $team->getCacheMaxAge()->willReturn(-1);
    $this->team = $team->reveal();

    // Mock a user account.
    $account = $this->prophesize(AccountInterface::class);
    $account->getEmail()->willReturn('foo@example.com');
    $account->isAnonymous()->willReturn(FALSE);
    $account->hasPermission('administer team')->willReturn(FALSE);
    $this->account = $account->reveal();

    // Mock the cache context manager.
    $cache_manager = $this->prophesize(CacheContextsManager::class);
    $cache_manager->assertValidTokens(Argument::any())->willReturn(TRUE);
    // Mock the container.
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_contexts_manager')->willReturn($cache_manager->reveal());

    \Drupal::setContainer($container->reveal());
  }

  /**
   * Tests the `access` callback with an admin user.
   */
  public function testAdminAccess() {
    // Mock an admin account.
    $account_mock = $this->prophesize(AccountInterface::class);
    $account_mock->getEmail()->willReturn('admin@example.com');
    $account_mock->isAnonymous()->willReturn(FALSE);
    $account_mock->hasPermission('administer team')->willReturn(TRUE);
    $account = $account_mock->reveal();

    $checker = new TeamPermissionAccessCheck($this->getPermissionHandler([], NULL, $account));

    $route_mock = $this->prophesize(Route::class);
    $route_mock->getRequirement('_permission')->willReturn('access foo');
    $route = $route_mock->reveal();
    $result = $checker->access($route, $this->team, $account);

    static::assertInstanceOf(AccessResultAllowed::class, $result);
  }

  /**
   * Tests the `access` callback.
   *
   * @dataProvider accessData
   */
  public function testAccess($account_permissions, $team_permission, $expected) {
    $checker = new TeamPermissionAccessCheck($this->getPermissionHandler($account_permissions));

    $route_mock = $this->prophesize(Route::class);
    $route_mock->getRequirement('_team_permission')->willReturn($team_permission);
    $route = $route_mock->reveal();
    $result = $checker->access($route, $this->team, $this->account);

    static::assertInstanceOf($expected, $result);
  }

  /**
   * Provides data for `::testAccess`.
   *
   * @return array
   *   The data.
   */
  public function accessData() {
    return [
      // Account with no team permissions.
      [
        'account_permissions' => [],
        'team_permission' => 'access foo',
        'expected' => AccessResultNeutral::class,
      ],
      // Account with single required permission.
      [
        'account_permissions' => ['access foo'],
        'team_permission' => 'access foo',
        'expected' => AccessResultAllowed::class,
      ],
      // Account with one of two required permissions.
      [
        'account_permissions' => ['access foo'],
        'team_permission' => 'access foo,access bar',
        'expected' => AccessResultNeutral::class,
      ],
      // Account with both of the required team permissions.
      [
        'account_permissions' => ['access foo', 'access bar'],
        'team_permission' => 'access foo,access bar',
        'expected' => AccessResultAllowed::class,
      ],
      // Account with no matching permission.
      [
        'account_permissions' => [''],
        'team_permission' => 'access foo+access bar',
        'expected' => AccessResultNeutral::class,
      ],
      // Account with the first matching permission.
      [
        'account_permissions' => ['access foo'],
        'team_permission' => 'access foo+access bar',
        'expected' => AccessResultAllowed::class,
      ],
      // Account with the second matching permission.
      [
        'account_permissions' => ['access bar'],
        'team_permission' => 'access foo+access bar',
        'expected' => AccessResultAllowed::class,
      ],
      // Failure to add a permission.
      [
        'account_permissions' => ['access bar'],
        'team_permission' => NULL,
        'expected' => AccessResultNeutral::class,
      ],
    ];
  }

  /**
   * Get's a mock permission handler.
   *
   * @param array $permissions
   *   The permissions that should be returned for the team member.
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface|null $team
   *   The Apigee Edge team.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The user account.
   *
   * @return \Drupal\apigee_edge_teams\TeamPermissionHandlerInterface
   *   The mock.
   */
  private function getPermissionHandler($permissions, $team = NULL, $account = NULL) {
    // Use the test team and account by default.
    $team = $team ?? $this->team;
    $account = $account ?? $this->account;
    // Mock a permission handler.
    $permission_handler = $this->prophesize(TeamPermissionHandlerInterface::class);
    $permission_handler
      ->getDeveloperPermissionsByTeam($team, $account)
      ->willReturn($permissions);

    return $permission_handler->reveal();
  }

}
