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

namespace Drupal\apigee_m10n\Entity;

use Apigee\Edge\Api\Monetization\Entity\RatePlan as MonetizationRatePlan;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Package Rate Plan entity class.
 *
 * Rate plans don't have a unique ID in apigee and a monetization package is
 * required to load a rate plan with is sort of incompatible with the drupal
 * entity loading system. For this reason, plan IDs have to be prepended with
 * the package name for loading. i.e.
 *
 * @code
 * RatePlan::load('foo-package::bar-plan-name')
 * @endcode.
 *
 * @\Drupal\apigee_edge\Annotation\EdgeEntityType(
 *   id = "rate_plan",
 *   label = @Translation("Rate plan"),
 *   label_singular = @Translation("Rate plan"),
 *   label_plural = @Translation("Rate plans"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Rate plan",
 *     plural = "@count Rate plans",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\apigee_m10n\Entity\Storage\RatePlanStorage",
 *     "access" = "Drupal\apigee_edge\Entity\EdgeEntityAccessControlHandler",
 *     "permission_provider" = "Drupal\apigee_edge\Entity\EdgeEntityPermissionProviderBase",
 *     "form" = {
 *       "subscribe" = "Drupal\apigee_edge\Entity\Form\DeveloperAppEditForm",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   links = {},
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   permission_granularity = "entity_type",
 *   admin_permission = "administer rate_plan",
 *   field_ui_base_route = "apigee_m10n.settings.rate_plan",
 * )
 */
class RatePlan extends MonetizationRatePlan implements RatePlanInterface {

  use FieldableMonetizationEntityBaseTrait {
    getProperties as baseGetProperties;
    baseFieldDefinitions as baseBaseFieldDefinitions;
  }

  /**
   * The Drupal user ID which belongs to the developer app.
   *
   * @var null|int
   */
  protected $drupalUserId;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values = []) {
    $values = array_filter($values);
    parent::__construct($values);
    $this->entityTypeId = 'rate_plan';
  }

  /**
   * {@inheritdoc}
   */
  protected static function propertyToFieldStaticMap(): array {
    return [
      'startDate'             => 'timestamp',
      'endDate'               => 'timestamp',
      'contractDuration'      => 'integer',
      'currency'              => 'apigee_currency',
      'earlyTerminationFee'   => 'decimal',
      'freemiumDuration'      => 'integer',
      'freemiumDurationType'  => 'string',
      'freemiumUnit'          => 'integer',
      'frequencyDuration'     => 'integer',
      'organization'          => 'apigee_organization',
      'package'               => 'apigee_api_package',
      'paymentDueDays'        => 'integer',
      'ratePlanDetails'       => 'apigee_rate_plan_detail',
      'recurringFee'          => 'decimal',
      'recurringStartUnit'    => 'integer',
      'setUpFee'              => 'decimal',
    ];
  }

  /**
   * Array of properties that should not be exposed as base fields.
   *
   * @return array
   *   Array with property names.
   */
  protected static function propertyToFieldBlackList(): array {
    return ['package'];
  }

  /**
   * {@inheritdoc}
   */
  protected static function getProperties(): array {
    // The original `getProperties` doesn't account for boolean values.
    $properties = static::baseGetProperties();

    $properties = [
      'advance' => 'boolean',
      'private' => 'boolean',
      'prorate' => 'boolean',
      'published' => 'boolean',
      'keepOriginalStartDate' => 'boolean',
    ] + $properties;

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $definitions */
    $definitions = self::baseBaseFieldDefinitions($entity_type);
    // The rate plan details are many-to-one.
    $definitions['ratePlanDetails']->setCardinality(-1);

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdvance(): bool {
    return $this->isAdvance();
  }

  /**
   * {@inheritdoc}
   */
  public function getPrivate(): bool {
    return $this->isPrivate();
  }

  /**
   * {@inheritdoc}
   */
  public function getProrate(): bool {
    return $this->isProrate();
  }

  /**
   * {@inheritdoc}
   */
  public function getPublished(): bool {
    return $this->isPublished();
  }

  /**
   * {@inheritdoc}
   */
  protected function getOriginalFieldData(string $field_name) {
    $value = NULL;
    $getter = 'get' . ucfirst($field_name);
    if (method_exists($this, $getter)) {
      // In some cases, the original value is null for fields that don't allow
      // nulls. This occurs when passing values when creating an entity.
      try {
        $value = call_user_func([$this, $getter]);
      }
      catch (\TypeError $t) {
        \Drupal::service('logger.channel.apigee_m10n')
          ->error('Type Error while trying to get original value. \n\n @err', ['@err' => (string) $t]);
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultRevision($new_value = NULL) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function loadPackageRatePlans(string $package_name): array {
    return \Drupal::entityTypeManager()
      ->getStorage('rate_plan')
      ->loadPackageRatePlans($package_name);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function loadById(string $package_name, string $id): RatePlanInterface {
    return \Drupal::entityTypeManager()
      ->getStorage('rate_plan')
      ->loadById($package_name, $id);
  }

}
