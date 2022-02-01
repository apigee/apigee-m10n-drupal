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

namespace Drupal\apigee_m10n\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Controller for subscribing to rate plans.
 */
class EntityDeveloperAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // Split the entity type and the operation.
    $requirement = $route->getRequirement('_entity_developer_access');
    [$entity_type, $operation] = explode('.', $requirement);
    $parameters = $route_match->getParameters();

    // Make sure the entity is available.
    if ($parameters->has($entity_type)) {
      // Get the entity.
      $entity = $parameters->get($entity_type);
      if ($entity instanceof EntityInterface) {
        $access = $entity->access($operation, $account, TRUE);
        $user = $route_match->getParameter('user');
        // Need to check to keep the permissions for 4g and 5g same.
        if ($entity_type == 'xrate_plan' && ($operation == 'purchase' || $operation == 'view')) {
          $entity_type = 'rate_plan';
        }
        return $access->andIf(AccessResult::allowedIf(
          $account->hasPermission("{$operation} $entity_type as anyone") ||
          ($account->hasPermission("{$operation} {$entity_type}") && $account->id() === $user->id())
        ));
      }
    }

    // No opinion, so other access checks should decide if access should be
    // allowed or not.
    return AccessResult::neutral();

  }

}
