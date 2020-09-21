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

namespace Drupal\apigee_m10n_teams\Entity\ParamConverter;

use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\Entity\ParamConverter\PurchasedPlanConverter;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Parameter converter for up-casting team purchased plans.
 *
 * {@inheritdoc}
 */
class TeamPurchasedPlanConverter extends PurchasedPlanConverter {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    /** @var \Drupal\apigee_m10n_teams\Entity\Storage\TeamPurchasedPlanStorage $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    // Get the team ID.
    $team = $defaults['team'] ?? NULL;
    $team_id = $team instanceof TeamInterface ? $team->id() : $team;

    if (!empty($team_id)) {
      $entity = $storage->loadTeamPurchasedPlanById($team_id, $value);
    }
    else {
      // Get the developer ID.
      $user = $defaults['user'] ?? FALSE;
      // Load the user if it is still a string.
      $user = (!$user || $user instanceof UserInterface) ? $user : User::load($user);
      $developer_id = $user instanceof UserInterface ? $user->getEmail() : FALSE;

      $entity = !empty($developer_id) ? $storage->loadById($developer_id, $value) : NULL;
    }

    return $entity;
  }

}
