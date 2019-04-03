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

namespace Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemList;

/**
 * Class AddCreditTargetEntityFieldItemList.
 */
class AddCreditTargetEntityFieldItemList extends FieldItemList implements EntityReferenceFieldItemListInterface {

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    if ($this->isEmpty()) {
      return [];
    }

    /** @var \Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\AddCreditTargetItem $item */
    $target_entities = $ids = [];
    foreach ($this->list as $delta => $item) {
      $ids[$item->target_type][$delta] = $item->target_id;
    }

    if (count($ids)) {
      foreach ($ids as $target_type => $entity_ids) {
        if ($entities = \Drupal::entityTypeManager()
          ->getStorage($target_type)
          ->loadMultiple($entity_ids)) {
          foreach ($entity_ids as $delta => $target_id) {
            if (isset($entities[$target_id])) {
              $target_entities[$delta] = $entities[$target_id];
            }
          }
        }
      }
    }

    return $target_entities;
  }

}
