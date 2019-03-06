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

use Drupal\apigee_edge\Entity\Controller\EdgeEntityControllerInterface;
use Drupal\apigee_m10n\Entity\Storage\PackageStorage;

/**
 * Overridden storage controller for the `package` entity.
 */
class TeamPackageStorage extends PackageStorage implements TeamPackageStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getAvailableApiPackagesByTeam($team_id) {
    $entities = [];

    $this->withController(function (EdgeEntityControllerInterface $controller) use ($team_id, &$entities) {
      $sdk_packages = $controller->getAvailableApiPackagesByCompany($team_id, TRUE, TRUE);

      // Returned entities are SDK entities and not Drupal entities,
      // what if the id is used in Drupal is different than what
      // SDK uses? (ex.: developer)
      foreach ($sdk_packages as $id => $entity) {
        $entities[$id] = $this->createNewInstance($entity);
      }
      $this->invokeStorageLoadHook($entities);
      $this->setPersistentCache($entities);
    });

    return $entities;
  }

}
