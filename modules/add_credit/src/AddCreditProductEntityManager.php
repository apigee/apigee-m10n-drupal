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

use Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\TopUpAmountItem;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Service to handle Apigee add credit products.
 *
 * @package Drupal\apigee_m10n_add_credit
 */
class AddCreditProductEntityManager implements AddCreditProductEntityManagerInterface {

  use LoggerChannelTrait;

  /**
   * {@inheritdoc}
   */
  public function getApigeeTopUpAmountField(FieldableEntityInterface $entity): ?TopUpAmountItem {
    try {
      foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
        if ($definition->getType() === 'apigee_top_up_amount') {
          return !$entity->get($field_name)
            ->isEmpty() ? $entity->get($field_name)->first() : NULL;
        }
      }
    }
    catch (MissingDataException $exception) {
      $this->getLogger('apigee_m10n_add_credit')
        ->notice($exception->getMessage());
    }

    return NULL;
  }

}
