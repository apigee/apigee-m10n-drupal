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
use Drupal\apigee_edge_teams\TeamPermissionHandlerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Access check for team permission.
 */
class TeamPermissionAccessCheck implements AccessInterface {

  /**
   * The team permission handler.
   *
   * @var \Drupal\apigee_edge_teams\TeamPermissionHandlerInterface
   */
  private $teamPermissionHandler;

  /**
   * ManageTeamMembersAccess constructor.
   *
   * @param \Drupal\apigee_edge_teams\TeamPermissionHandlerInterface $team_permission_handler
   *   The team permission handler.
   */
  public function __construct(TeamPermissionHandlerInterface $team_permission_handler) {
    $this->teamPermissionHandler = $team_permission_handler;
  }

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
  public function access(Route $route, TeamInterface $team, AccountInterface $account) {
    // Team administrators have all access.
    if ($account->hasPermission('administer team')) {
      return AccessResult::allowed();
    }

    // Make sure the team permission is set in the route.
    $permission = $route->getRequirement('_team_permission');
    if ($permission === NULL) {
      return AccessResult::neutral();
    }

    // Allow to conjunct the permissions with OR ('+') or AND (',').
    $split = explode(',', $permission);
    if (count($split) > 1) {
      return $this->allowedIfHasTeamPermissions($team, $account, $split, 'AND');
    }
    else {
      $split = explode('+', $permission);
      return $this->allowedIfHasTeamPermissions($team, $account, $split, 'OR');
    }
  }

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
  protected function allowedIfHasTeamPermissions(TeamInterface $team, AccountInterface $account, array $permissions, $conjunction = 'AND') {
    $access = FALSE;

    if ($conjunction == 'AND' && !empty($permissions)) {
      $access = TRUE;
      foreach ($permissions as $permission) {
        if (!$permission_access = $this->hasTeamPermission($team, $account, $permission)) {
          $access = FALSE;
          break;
        }
      }
    }
    else {
      foreach ($permissions as $permission) {
        if ($permission_access = $this->hasTeamPermission($team, $account, $permission)) {
          $access = TRUE;
          break;
        }
      }
    }

    // TODO: Add a `team.permissions` cache context.
    // See: `\Drupal\Core\Cache\Context\AccountPermissionsCacheContext`
    $access_result = AccessResult::allowedIf($access)
      ->addCacheableDependency($team)
      ->addCacheableDependency($account);

    if ($access_result instanceof AccessResultReasonInterface) {
      if (count($permissions) === 1) {
        $access_result->setReason("The '{$permission}' permission is required.");
      }
      elseif (count($permissions) > 1) {
        $quote = function ($s) {
          return "'$s'";
        };
        $access_result->setReason(sprintf("The following permissions are required: %s.", implode(" $conjunction ", array_map($quote, $permissions))));
      }
    }

    return $access_result;
  }

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
  protected function hasTeamPermission(TeamInterface $team, AccountInterface $account, string $permission) {

    return !$account->isAnonymous()
      && ($permissions = $this->teamPermissionHandler->getDeveloperPermissionsByTeam($team, $account))
      && in_array($permission, $permissions);
  }

}
