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

use Apigee\Edge\Api\Management\Entity\ApiProductInterface;
use Apigee\Edge\Api\Monetization\Entity\ApiPackage;
use Apigee\Edge\Api\Monetization\Entity\ApiProduct;
use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'apigee_api_package' field type.
 *
 * @FieldType(
 *   id = "apigee_api_package",
 *   label = @Translation("Apigee API Package field item"),
 *   description = @Translation("Apigee API Package."),
 *   default_formatter = "apigee_api_package"
 * )
 */
class ApiPackageFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('value'))
      ->setDescription(new TranslatableMarkup('The API package object.'))
      ->setComputed(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = new ApiPackage([
      'name'        => $random->name(),
      'description' => $random->sentences(3),
      'displayName' => $random->word(16),
      'apiProducts' => [
        new ApiProduct([
          'id'            => strtolower($random->name()),
          'name'          => $random->name(),
          'description'   => $random->sentences(3),
          'displayName'   => $random->word(16),
          'approvalType'  => ApiProductInterface::APPROVAL_TYPE_AUTO,
        ]),
      ],
      'status'      => 'CREATED',
    ]);

    return $values;
  }

}
