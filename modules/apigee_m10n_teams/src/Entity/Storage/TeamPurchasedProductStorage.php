<?php

/*
 * Copyright 2025 Google Inc.
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

use Apigee\Edge\Api\ApigeeX\Entity\AppGroupAcceptedRatePlanInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\apigee_m10n\Entity\Storage\PurchasedProductStorage;
use Drupal\apigee_m10n_teams\Entity\Storage\Controller\TeamAcceptedRatePlanXSdkControllerProxyInterface;
use Drupal\apigee_m10n_teams\Entity\TeamsPurchasedProductInterface;

/**
 * Overridden storage controller for the `purchased_product` entity for teams.
 */
class TeamPurchasedProductStorage extends PurchasedProductStorage implements TeamPurchasedProductStorageInterface {

  /**
   * {@inheritdoc}
   *
   * `\Drupal\Core\Entity\EntityStorageBase::doPreSave` is going to try to try
   * to load the original entity but `::loadUnchanged()` isn't aware of team
   * context since it only takes the entity ID as a parameter. We can avoid the
   * issue be setting the original while we still have context.
   */
  protected function doPreSave(EntityInterface $entity) {
    // Check for team context.
    if (!$entity->isNew()
      && $entity->decorated() instanceof AppGroupAcceptedRatePlanInterface
      && ($team_id = $entity->decorated()->getAppGroup()->id())
    ) {
      // Reset the static and persistent cache so we can load unchanged.
      $this->resetControllerCache([$entity->id()]);
      $this->resetCache([$entity->id()]);
      // Load the unchanged entity from the API.
      $entity->original = $this->loadTeamPurchasedProductById($team_id, $entity->id());
    }

    return parent::doPreSave($entity);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function loadByTeamId(string $team_id): array {
    $entities = [];

    $this->withController(function (TeamAcceptedRatePlanXSdkControllerProxyInterface $controller) use ($team_id, &$entities) {
      // Load the purchases for this team.
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
  public function loadTeamPurchasedProductById(string $team_id, string $id): ?TeamsPurchasedProductInterface {
    // Load from cache.
    $ids = [$id];
    $purchased_product = $this->getFromPersistentCache($ids);
    // Return the cached entity.
    if (isset($purchased_product[$id])) {
      return $purchased_product[$id];
    }

    $entity = NULL;
    $this->withController(function (TeamAcceptedRatePlanXSdkControllerProxyInterface $controller) use ($team_id, $id, &$entity) {
      $drupal_entity = ($sdk_entity = $controller->loadTeamPurchasedProductById($team_id, $id))
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
