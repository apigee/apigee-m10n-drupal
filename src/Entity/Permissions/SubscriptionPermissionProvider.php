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

namespace Drupal\apigee_m10n\Entity\Permissions;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\UncacheableEntityPermissionProvider;

/**
 * Provides the permissions for the `subscription` entity.
 */
class SubscriptionPermissionProvider extends UncacheableEntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildPermissions($entity_type);

    // Change the titles for view and update actions.
    $permissions['view own subscription']['title'] = $this->t('View own purchased plans');
    $permissions['view any subscription']['title'] = $this->t('View any purchased plan');
    $permissions['update own subscription']['title'] = $this->t('Cancel own purchased plans');
    $permissions['update any subscription']['title'] = $this->t('Cancel any purchased plan');

    // Filter out permissions that aren't useful to subscription entities.
    return array_diff_key($permissions, [
      'administer subscription' => NULL,
      'create subscription' => NULL,
      'delete any subscription' => NULL,
      'delete own subscription' => NULL,
    ]);
  }

}
