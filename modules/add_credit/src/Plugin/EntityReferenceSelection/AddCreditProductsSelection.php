<?php

/**
 * Copyright 2018 Google Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

namespace Drupal\apigee_m10n_add_credit\Plugin\EntityReferenceSelection;

use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides a entity selection plugin for add credit products.
 *
 * @EntityReferenceSelection(
 *   id = "apigee_m10n_add_credit:products",
 *   label = @Translation("Add credit products selection"),
 *   entity_types = {"commerce_product"},
 *   group = "apigee_m10n_add_credit",
 *   weight = 1
 * )
 */
class AddCreditProductsSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Filter add credit enabled products only.
    $query->condition(AddCreditConfig::ADD_CREDIT_ENABLED_FIELD_NAME, TRUE);

    return $query;
  }

}
