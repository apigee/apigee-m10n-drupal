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

namespace Drupal\apigee_m10n_add_credit;

/**
 * Permissions provider for add credit.
 */
class AddCreditPermissions implements AddCreditPermissionsInterface {

  /**
   * {@inheritdoc}
   */
  public function permissions(): array {
    $permissions = [];

    // Build permissions for add credit entity types.
    foreach (AddCreditConfig::getEntityTypes() as $entity_type_id => $config) {
      $entity_type_id = $config['edge_entity_type'] ?? $entity_type_id;
      $permissions["add credit to own $entity_type_id prepaid balance"] = [
        'title' => "Add credit to own $entity_type_id prepaid balance",
      ];

      $permissions["add credit to any $entity_type_id prepaid balance"] = [
        'title' => "Add credit to any $entity_type_id prepaid balance",
      ];
    }

    return $permissions;
  }

}
