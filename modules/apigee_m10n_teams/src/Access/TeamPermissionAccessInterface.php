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

namespace Drupal\apigee_m10n_teams\Access;

use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Access check for team permission.
 */
interface TeamPermissionAccessInterface extends AccessInterface {

  /**
   * Provides a generic access check for team permissions.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The Apigee Edge team.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, TeamInterface $team, AccountInterface $account);

  /**
   * Checks that a developer has a given company permission.
   *
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The Apigee Edge team.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param string $permission
   *   The permission.
   *
   * @return bool
   *   Whether or not the user has the team permission.
   */
  public function hasTeamPermission(TeamInterface $team, AccountInterface $account, string $permission);

  /**
   * Creates an allowed access result if the team permissions are present.
   *
   * See: `\Drupal\Core\Access\AccessResult::allowedIfHasPermissions()`.
   *
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The Apigee Edge team.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param array $permissions
   *   An array of permissions.
   * @param string $conjunction
   *   (optional) 'AND' if all permissions are required, 'OR' in case just one.
   *   Defaults to 'AND'.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If the account has the permissions, isAllowed() will be TRUE, otherwise
   *   isNeutral() will be TRUE.
   */
  public function allowedIfHasTeamPermissions(TeamInterface $team, AccountInterface $account, array $permissions, $conjunction = 'AND');

}
