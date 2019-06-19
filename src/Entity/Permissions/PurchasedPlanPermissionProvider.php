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
 * Provides the permissions for the `purchased_plan` entity.
 */
class PurchasedPlanPermissionProvider extends UncacheableEntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildPermissions($entity_type);

    // Change the titles for view and update actions.
    $permissions['view own purchased_plan']['title'] = $this->t('View own purchased plans');
    $permissions['view any purchased_plan']['title'] = $this->t('View any purchased plan');
    $permissions['update own purchased_plan']['title'] = $this->t('Cancel own purchased plans');
    $permissions['update any purchased_plan']['title'] = $this->t('Cancel any purchased plan');

    // Filter out permissions that aren't useful to purchased_plan entities.
    return array_diff_key($permissions, [
      'administer purchased_plan' => NULL,
      'create purchased_plan' => NULL,
      'delete any purchased_plan' => NULL,
      'delete own purchased_plan' => NULL,
    ]);
  }

}
