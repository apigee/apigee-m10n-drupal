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
use Drupal\apigee_edge\Entity\FieldableEdgeEntityBaseTrait;
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
 *     "form" = {
 *       "unsubscribe" = "Drupal\apigee_m10n\Entity\Form\UnsubscribeForm",
 *     },
 *   },
 *   links = {
 *     "collection-by-developer" = "/user/{user}/monetization/subscriptions",
 *     "unsubscribe-form" = "/user/{user}/monetization/subscription/{subscription}/unsubscribe",
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
    set as traitSet;
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
      'startDate'            => 'timestamp',
      'endDate'              => 'timestamp',
      'created'              => 'timestamp',
      'quotaTarget'          => 'integer',
      'ratePlan'             => 'entity_reference',
      'updated'              => 'timestamp',
      'renewalDate'          => 'timestamp',
      'nextCycleStartDate'   => 'timestamp',
      'nextRecurringFeeDate' => 'timestamp',
      'prevRecurringFeeDate' => 'timestamp',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $definitions */
    $definitions = self::traitBaseFieldDefinitions($entity_type);
    return $definitions;
  }

  /**
   * @param string $field_name
   * @param mixed $value
   * @param bool $notify
   *
   * @return $this|\Drupal\apigee_m10n\Entity\SubscriptionInterface
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function set($field_name, $value, $notify = TRUE) {
    if ($field_name === 'ratePlan') {
      $rate_plan_entity = RatePlan::loadById($value->getPackage()->id(), $value->id());
      $this->ratePlan = $rate_plan_entity;
    }
    else {
      $this->traitSet($field_name, $value, $notify);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $values = [];

    foreach ($this->getFields() as $name => $property) {
      if ($property->getFieldDefinition()->getType() === 'entity_reference') {
        $values[$name] = \Drupal::entityTypeManager()->getStorage('rate_plan')->convertToSdkEntity($property->entity);
      }
      else {
        /** @var \Drupal\Core\Field\FieldItemListInterface $property */
        $values[$name] = $property->value;
      }
    }

    // Keep the original values and only add new values from fields.
    // This order is important because for example the array from traitToArray
    // contains date properties as proper DateTimeImmutable objects but the new
    // one contains them as timestamps. Because this function called by
    // DrupalEntityControllerAwareTrait::convertToSdkEntity() that missmatch
    // could cause errors.
    $return = array_merge($values, $this->traitToArray());
    $return['ratePlan'] = $values['ratePlan'];
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionStatus(): string {
    $org_timezone = $this->getRatePlan()->getOrganization()->getTimezone();
    $today = new \DateTime('today', $org_timezone);

    // If rate plan ended before today, the status is ended.
    $plan_end_date = $this->getRatePlan()->getEndDate();
    if (!empty($plan_end_date) && $plan_end_date < $today) {
      return SubscriptionInterface::STATUS_ENDED;
    }
    // If the developer ended the plan before today, the plan has ended.
    $developer_plan_end_date = $this->getEndDate();
    if (!empty($developer_plan_end_date) && $developer_plan_end_date < $today) {
      return SubscriptionInterface::STATUS_ENDED;
    }

    // If the start date is later than today, it is a future plan.
    $developer_plan_start_date = $this->getStartDate();
    if (!empty($developer_plan_start_date) && $developer_plan_start_date > $today) {
      return SubscriptionInterface::STATUS_FUTURE;
    }

    return SubscriptionInterface::STATUS_ACTIVE;
  }

  /**
   * {@inheritdoc}
   */
  public function isSubscriptionActive(): bool {
    return $this->getSubscriptionStatus() === SubscriptionInterface::STATUS_ACTIVE;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultRevision($new_value = NULL) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadRatePlansByDeveloperEmail(string $developer_email): array {
    // TODO: Implement loadRatePlansByDeveloperEmail() method.
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

}
