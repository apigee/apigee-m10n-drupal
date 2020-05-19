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

namespace Drupal\apigee_m10n\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'apigee_datestamp' entity field type.
 *
 * @FieldType(
 *   id = "apigee_datestamp",
 *   label = @Translation("Apigee Date"),
 *   description = @Translation("An entity field containing a date."),
 *   category = @Translation("Apigee"),
 *   no_ui = TRUE,
 *   default_widget = "apigee_datestamp",
 *   default_formatter = "apigee_datestamp"
 * )
 */
class DatestampFieldItem extends TimestampItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(t('Datetime value'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // When an item is saved, it's value will be an integer, we need to convert
    // it back to `\DateTimeImmutable`.
    if (isset($values) && is_array($values)) {
      $keys = array_keys($this->definition->getPropertyDefinitions());
      if (is_int($values[$keys[0]])) {
        $datetime = new \DateTime();
        $datetime->setTimestamp($values[$keys[0]]);
        $values[$keys[0]] = \DateTimeImmutable::createFromMutable($datetime);
      }
    }

    parent::setValue($values, $notify);
  }

}
