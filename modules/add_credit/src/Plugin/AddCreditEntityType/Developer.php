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

namespace Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityType;

use Drupal\apigee_edge\Entity\DeveloperInterface;
use Drupal\apigee_m10n_add_credit\Annotation\AddCreditEntityType;
use Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a developer add credit entity type plugin.
 *
 * @AddCreditEntityType(
 *   id = "developer",
 *   label = "Developer",
 * )
 */
class Developer extends AddCreditEntityTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getRouteEntityTypeId(): string {
    // Developer entity type uses /user/{user} in the route.
    // We override it here so that the user param is properly converted.
    return 'user';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityId(EntityInterface $entity): string {
    /** @var \Drupal\user\UserInterface $entity */
    return $entity->getEmail();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntities(AccountInterface $account): array {
    $ids = [];
    if ($account->hasPermission('add credit to any developer prepaid balance')) {
      // Use NULL such that the storage loads all entities.
      $ids = NULL;
    }
    elseif ($account->hasPermission('add credit to own developer prepaid balance')) {
      $ids = [$account->getEmail()];
    }

    // Filter out developers with no user accounts.
    return array_filter($this->entityTypeManager->getStorage('developer')->loadMultiple($ids), function (DeveloperInterface $developer) {
      return $developer->getOwnerId();
    });
  }

}
