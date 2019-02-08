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

namespace Drupal\apigee_m10n_teams\Entity\Storage;

use Drupal\apigee_m10n\Entity\Storage\SubscriptionStorage;
use Drupal\apigee_m10n_teams\Entity\Storage\Controller\TeamAcceptedRatePlanSdkControllerProxyInterface;
use Drupal\apigee_m10n_teams\Entity\TeamRouteAwareSubscriptionInterface;

/**
 * Overridden storage controller for the `subscription` entity for teams.
 */
class TeamSubscriptionStorage extends SubscriptionStorage implements TeamSubscriptionStorageInterface {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function loadByTeamId(string $team_id): array {
    $entities = [];

    $this->withController(function (TeamAcceptedRatePlanSdkControllerProxyInterface $controller) use ($team_id, &$entities) {
      // Load the subscriptions for this developer.
      $sdk_entities = $controller->loadByTeamId($team_id);
      // Convert the SDK entities to drupal entities.
      foreach ($sdk_entities as $id => $entity) {
        $drupal_entity = $this->createNewInstance($entity);
        $entities[$drupal_entity->id()] = $drupal_entity;
      }
      $this->invokeStorageLoadHook($entities);
      $this->setPersistentCache($entities);
    });

    return $entities;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function loadTeamSubscriptionById(string $team_id, string $id): ?TeamRouteAwareSubscriptionInterface {
    // Load from cache.
    $ids = [$id];
    $subscriptions = $this->getFromPersistentCache($ids);
    // Return the cached entity.
    if (isset($subscriptions[$id])) {
      return $subscriptions[$id];
    }

    $entity = NULL;
    $this->withController(function (TeamAcceptedRatePlanSdkControllerProxyInterface $controller) use ($team_id, $id, &$entity) {
      $drupal_entity = ($sdk_entity = $controller->loadTeamSubscriptionById($team_id, $id))
        ? $this->createNewInstance($sdk_entity)
        : FALSE;

      if ($drupal_entity) {
        $entities = [$drupal_entity->id() => $drupal_entity];
        $this->invokeStorageLoadHook($entities);
        $this->setPersistentCache($entities);

        $entity = $drupal_entity;
      }
    });

    return $entity;
  }

}
