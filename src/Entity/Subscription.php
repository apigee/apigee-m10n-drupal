<?php

/**
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

use Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlan;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Subscription entity class.
 *
 * @\Drupal\apigee_edge\Annotation\EdgeEntityType(
 *   id = "subscription",
 *   label = @Translation("Subscription"),
 *   label_singular = @Translation("Subscription"),
 *   label_plural = @Translation("Subscriptions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Subscription",
 *     plural = "@count Subscriptions",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\apigee_m10n\Entity\Storage\SubscriptionStorage",
 *     "access" = "Drupal\apigee_edge\Entity\EdgeEntityAccessControlHandler",
 *     "permission_provider" = "Drupal\apigee_edge\Entity\EdgeEntityPermissionProviderBase",
 *     "list_builder" = "Drupal\apigee_m10n\Entity\ListBuilder\SubscriptionListBuilder",
 *   },
 *   links = {
 *     "collection-by-developer" = "/user/{user}/monetization/subscriptions",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   permission_granularity = "entity_type",
 *   admin_permission = "administer subscriptions",
 *   field_ui_base_route = "apigee_m10n.settings.subscriptions",
 * )
 */
class Subscription extends AcceptedRatePlan implements SubscriptionInterface {

  use FieldableMonetizationEntityBaseTrait {
    baseFieldDefinitions as traitBaseFieldDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values = []) {
    $values = array_filter($values);
    parent::__construct($values);
    $this->entityTypeId = 'subscription';
  }

  /**
   * {@inheritdoc}
   */
  protected static function propertyToFieldStaticMap(): array {
    return [
      'startDate' => 'timestamp',
      'endDate' => 'timestamp',
      'created' => 'timestamp',
      'quotaTarget' => 'integer',
      'ratePlan' => 'decimal',
      'updated' => 'timestamp',
      'renewalDate' => 'timestamp',
      'nextCycleStartDate' => 'timestamp',
      '$nextRecurringFeeDate' => 'timestamp',
      '$prevRecurringFeeDate' => 'timestamp',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $definitions */
    $definitions = self::traitBaseFieldDefinitions($entity_type);
    // The rate plan details are many-to-one.
    $definitions['ratePlanDetails']->setCardinality(-1);

    return $definitions;
  }

  /**
   * Array of properties that should not be exposed as base fields.
   *
   * @return array
   *   Array with property names.
   */
  protected static function propertyToFieldBlackList(): array {
    return [];
  }

  public function isDefaultRevision($new_value = NULL) {
    return TRUE;
  }

  public static function loadRatePlansByDeveloperEmail(string $developer_email): array {
    // TODO: Implement loadRatePlansByDeveloperEmail() method.
  }

}