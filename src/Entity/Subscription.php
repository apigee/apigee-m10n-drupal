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

use Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlan;
use Apigee\Edge\Api\Monetization\Entity\DeveloperAcceptedRatePlan;
use Apigee\Edge\Api\Monetization\Entity\DeveloperInterface;
use Apigee\Edge\Api\Monetization\Entity\RatePlanInterface;
use Apigee\Edge\Entity\EntityInterface as EdgeEntityInterface;
use Drupal\apigee_edge\Entity\FieldableEdgeEntityBase;
use Drupal\apigee_m10n\Entity\RatePlanInterface as DrupalRatePlanInterface;
use Drupal\apigee_m10n\Entity\Property\EndDatePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\StartDatePropertyAwareDecoratorTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Subscription entity class.
 *
 * @\Drupal\apigee_edge\Annotation\EdgeEntityType(
 *   id             = "subscription",
 *   label          = @Translation("Subscription"),
 *   label_singular = @Translation("Subscription"),
 *   label_plural   = @Translation("Subscriptions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Subscription",
 *     plural   = "@count Subscriptions",
 *   ),
 *   handlers = {
 *     "storage"             = "Drupal\apigee_m10n\Entity\Storage\SubscriptionStorage",
 *     "access"              = "Drupal\entity\UncacheableEntityAccessControlHandler",
 *     "permission_provider" = "Drupal\apigee_m10n\Entity\Permissions\SubscriptionPermissionProvider",
 *     "list_builder"        = "Drupal\apigee_m10n\Entity\ListBuilder\SubscriptionListBuilder",
 *     "form" = {
 *       "default"     = "Drupal\apigee_m10n\Entity\Form\SubscriptionForm",
 *       "unsubscribe" = "Drupal\apigee_m10n\Entity\Form\UnsubscribeConfirmForm",
 *     },
 *   },
 *   links = {},
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   admin_permission       = "administer apigee monetization",
 *   field_ui_base_route    = "apigee_m10n.settings.subscription",
 * )
 */
class Subscription extends FieldableEdgeEntityBase implements SubscriptionInterface, EntityOwnerInterface {

  use EndDatePropertyAwareDecoratorTrait;
  use StartDatePropertyAwareDecoratorTrait;

  public const ENTITY_TYPE_ID = 'subscription';

  /**
   * The rate plan this subscription belongs to.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $rate_plan;

  /**
   * The owner's user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $owner;

  /**
   * Constructs a `subscription` entity.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   * @param null|string $entity_type
   *   Type of the entity. It is optional because constructor sets its default
   *   value.
   * @param \Apigee\Edge\Entity\EntityInterface|null $decorated
   *   The SDK entity that this Drupal entity decorates.
   *
   * @throws \ReflectionException
   */
  public function __construct(array $values, ?string $entity_type = NULL, ?EdgeEntityInterface $decorated = NULL) {
    /** @var \Apigee\Edge\Api\Management\Entity\DeveloperAppInterface $decorated */
    $entity_type = $entity_type ?? static::ENTITY_TYPE_ID;
    parent::__construct($values, $entity_type, $decorated);
    // Save entity references in this class as well as the decorated instance.
    if (!empty($values['ratePlan']) && $values['ratePlan'] instanceof DrupalRatePlanInterface) {
      // TODO: Since `RatePlan::createFrom($sdk_rate_plan)` is available do we
      // need to store an extra reference here. Is the slight performance
      // benefit worth it?
      $this->setRatePlan($values['ratePlan']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function decoratedClass(): string {
    return DeveloperAcceptedRatePlan::class;
  }

  /**
   * {@inheritdoc}
   */
  public static function idProperty(): string {
    return AcceptedRatePlan::idProperty();
  }

  /**
   * {@inheritdoc}
   */
  protected static function propertyToBaseFieldTypeMap(): array {
    return [
      'startDate'            => 'apigee_datestamp',
      'endDate'              => 'apigee_datestamp',
      'created'              => 'apigee_datestamp',
      'quotaTarget'          => 'integer',
      'ratePlan'             => 'entity_reference',
      'developer'            => 'apigee_monetization_developer',
      'updated'              => 'apigee_datestamp',
      'renewalDate'          => 'apigee_datestamp',
      'nextCycleStartDate'   => 'apigee_datestamp',
      'nextRecurringFeeDate' => 'apigee_datestamp',
      'prevRecurringFeeDate' => 'apigee_datestamp',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $definitions */
    $definitions = parent::baseFieldDefinitions($entity_type);

    // The following fields should not be configurable through the UI.
    $read_only_fields = [
      'id',
      'created',
      'quotaTarget',
      'updated',
      'renewalDate',
      'nextCycleStartDate',
      'nextRecurringFeeDate',
      'prevRecurringFeeDate',
      'developer',
    ];
    // Disable the form display entry for all read only fields.
    foreach ($read_only_fields as $field_name) {
      $definitions[$field_name]->setDisplayConfigurable('form', FALSE);
    }

    $definitions['termsAndConditions']
      ->setRequired(TRUE);

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getProperties(): array {
    $properties = parent::getProperties();
    $properties['termsAndConditions'] = 'apigee_tnc';

    return $properties;
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function loadByDeveloperId(string $developer_id): array {
    return \Drupal::entityTypeManager()
      ->getStorage(static::ENTITY_TYPE_ID)
      ->loadByDeveloperId($developer_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalEntityId(): ?string {
    return $this->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): ?string {
    return $this->decorated->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function isSubscriptionActive(): bool {
    return ($this->getSubscriptionStatus() === SubscriptionInterface::STATUS_ACTIVE
      || $this->getSubscriptionStatus() === SubscriptionInterface::STATUS_FUTURE);
  }

  /**
   * {@inheritdoc}
   */
  public function getTermsAndConditions(): bool {
    $developer_email = $this->getDeveloper()->getEmail();
    // Caching is handled by the monetization service.
    return $this->monetization()->isLatestTermsAndConditionAccepted($developer_email);
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
  public function getUpdated(): ?\DateTimeImmutable {
    return $this->decorated->getUpdated();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated(): ?\DateTimeImmutable {
    return $this->decorated->getCreated();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuotaTarget(): ?int {
    return $this->decorated->getQuotaTarget();
  }

  /**
   * {@inheritdoc}
   */
  public function setQuotaTarget(int $quotaTarget): void {
    $this->decorated->setQuotaTarget($quotaTarget);
  }

  /**
   * {@inheritdoc}
   */
  public function getRatePlan(): RatePlanInterface {
    // Return the drupal entity for entity references.
    if (empty($this->rate_plan) && !empty($this->decorated()) && $sdk_rate_plan = $this->decorated()->getRatePlan()) {
      /** @var \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface $sdk_rate_plan */
      $this->rate_plan = RatePlan::createFrom($sdk_rate_plan);
    }

    return $this->rate_plan;
  }

  /**
   * {@inheritdoc}
   */
  public function setRatePlan(RatePlanInterface $ratePlan): void {
    $this->rate_plan = $ratePlan;
    $this->decorated->setRatePlan($ratePlan->decorated());
  }

  /**
   * {@inheritdoc}
   */
  public function getRenewalDate(): ?\DateTimeImmutable {
    return $this->decorated->getRenewalDate();
  }

  /**
   * {@inheritdoc}
   */
  public function getNextCycleStartDate(): ?\DateTimeImmutable {
    return $this->decorated->getNextCycleStartDate();
  }

  /**
   * {@inheritdoc}
   */
  public function getNextRecurringFeeDate(): ?\DateTimeImmutable {
    return $this->decorated->getNextRecurringFeeDate();
  }

  /**
   * {@inheritdoc}
   */
  public function getPrevRecurringFeeDate(): ?\DateTimeImmutable {
    return $this->decorated->getPrevRecurringFeeDate();
  }

  /**
   * {@inheritdoc}
   */
  public function getDeveloper(): ?DeveloperInterface {
    // TODO: Return the `apigee_edge` developer entity reference.
    return $this->decorated->getDeveloper();
  }

  /**
   * Gets the monetization service.
   *
   * This gets the monetization service as needed avoiding loading the SDK
   * connector during bootstrap.
   *
   * @return \Drupal\apigee_m10n\MonetizationInterface
   *   The monetization service.
   */
  protected function monetization() {
    return \Drupal::service('apigee_m10n.monetization');
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    if (!isset($this->owner)) {
      $owner = $this->entityTypeManager()->getStorage('user')->loadByProperties([
        'mail' => $this->getDeveloper()->getEmail(),
      ]);
      $this->owner = !empty($owner) ? reset($owner) : NULL;
    }
    return $this->owner;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return ($owner = $this->getOwner()) ? $owner->id() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    // The owner is not settable after instantiation.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    // The owner is not settable after instantiation.
    return $this;
  }

}
