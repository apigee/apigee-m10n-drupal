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

use Drupal\apigee_edge\Entity\Developer;
use Drupal\apigee_edge_teams\Entity\Team;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_edge_teams\Entity\TeamRole;
use Drupal\apigee_edge_teams\Entity\TeamRoleInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
use Drupal\user\UserInterface;

/**
 * The base class for Monetization teams kernel tests.
 */
class MonetizationTeamsKernelTestBase extends MonetizationKernelTestBase {

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

    $this->installEntitySchema('team_member_role');
    $this->installConfig(['apigee_edge_teams']);
    $this->initTeamMemberRole();
  }

  /**
   * Create a new team.
   *
   * @return \Drupal\apigee_edge_teams\Entity\TeamInterface
   *   The team.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createTeam(): TeamInterface {
    $team = Team::create([
      'name' => $this->randomMachineName(),
      'displayName' => "{$this->getRandomGenerator()->word(8)} {$this->getRandomGenerator()->word(4)}",
    ]);

    $this->stack->queueMockResponse(['company' => ['company' => $team]]);
    $this->stack->queueMockResponse(['company' => ['company' => $team]]);
    $team->save();

    $this->stack->queueMockResponse(['company' => ['company' => $team]]);
    return Team::load($team->getName());
  }

  /**
   * Creates a user session for a user and sets it as the current user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The developer.
   */
  public function createCurrentUserSession(UserInterface $user) {
    $this->setCurrentUser($this->createUserSession($user));
  }

  /**
   * Creates a user session for a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The developer.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   A user session.
   */
  public function createUserSession(UserInterface $user) {
    return new UserSession([
      'uid' => $user->id(),
      'name' => $user->getAccountName(),
      'roles' => $user->getRoles(),
      'mail' => $user->getEmail(),
      'timezone' => date_default_timezone_get(),
    ]);
  }

  /**
   * Adds a user to a team.
   *
   * Adding a team to a user will add the team as long as the developer entity
   * is loaded from cache.
   *
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The team.
   * @param \Drupal\user\UserInterface $user
   *   A drupal user.
   *
   * @return \Drupal\apigee_edge\Entity\DeveloperInterface
   *   The developer entity.
   */
  public function addUserToTeam(TeamInterface $team, UserInterface $user) {
    $context['developer'] = $user;
    $context['org_name'] = $this->sdk_connector->getOrganization();
    $context['companies'] = [$team->id()];

    $this->stack->queueMockResponse(['get_developer' => $context]);

    $teams = \Drupal::service('apigee_edge_teams.team_membership_manager')->getTeams($user->getEmail());
    static::assertSame([$team->id()], $teams);

    return Developer::load($user->getEmail());
  }

  /**
   * Helper to initialize team member permissions.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function initTeamMemberRole() {
    $role = TeamRole::load(TeamRoleInterface::TEAM_MEMBER_ROLE);

    $default_member_permissions = [
      'refresh prepaid balance',
      'view prepaid balance',
      'view prepaid balance report',
      'edit billing details',
      'view package',
      'subscribe rate_plan',
      'update subscription',
      'view subscription',
      'view rate_plan',
    ];

    foreach ($default_member_permissions as $default_member_permission) {
      $role->grantPermission($default_member_permission);
    }

    $role->save();
  }

}
