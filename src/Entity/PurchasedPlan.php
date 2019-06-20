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
 * Defines the `purchased_plan` entity class.
 *
 * The label was changed to purchased plan after the entity was created because
 * it was decided it was a better match.
 *
 * @\Drupal\apigee_edge\Annotation\EdgeEntityType(
 *   id             = "purchased_plan",
 *   label          = @Translation("Purchased Plan"),
 *   label_singular = @Translation("Purchased Plan"),
 *   label_plural   = @Translation("Purchased Plans"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Purchased Plan",
 *     plural   = "@count Purchased Plans",
 *   ),
 *   handlers = {
 *     "storage"             = "Drupal\apigee_m10n\Entity\Storage\PurchasedPlanStorage",
 *     "access"              = "Drupal\entity\UncacheableEntityAccessControlHandler",
 *     "permission_provider" = "Drupal\apigee_m10n\Entity\Permissions\PurchasedPlanPermissionProvider",
 *     "list_builder"        = "Drupal\apigee_m10n\Entity\ListBuilder\PurchasedPlanListBuilder",
 *     "form" = {
 *       "default" = "Drupal\apigee_m10n\Entity\Form\PurchasedPlanForm",
 *       "cancel"  = "Drupal\apigee_m10n\Entity\Form\CancelPurchaseConfirmForm",
 *     },
 *   },
 *   links = {
 *     "developer_collection" = "/user/{user}/monetization/purchased-plans",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   admin_permission       = "administer apigee monetization",
 *   field_ui_base_route    = "apigee_m10n.settings.purchased_plan",
 * )
 */
class PurchasedPlan extends FieldableEdgeEntityBase implements PurchasedPlanInterface, EntityOwnerInterface {

  use EndDatePropertyAwareDecoratorTrait {
    getEndDate as traitGetEndDate;
  }
  use StartDatePropertyAwareDecoratorTrait;

  public const ENTITY_TYPE_ID = 'purchased_plan';

  /**
   * Suppress errors if rate plan that overlaps another accepted rate plan.
   *
   * @var bool
   */
  protected $suppressWarning;

  /**
   * The rate plan this purchased_plan belongs to.
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
   * Constructs a `purchased_plan` entity.
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

    // Do not suppress warnings by default.
    $this->suppressWarning = FALSE;
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
  public function toUrl($rel = 'canonical', array $options = []) {
    // The string formatter assumes entities are revisionable.
    $rel = ($rel === 'revision') ? 'canonical' : $rel;
    // Use the developer collection as the default collection.
    $rel = ($rel === 'collection') ? 'developer_collection' : $rel;
    // Build the URL.
    $url = parent::toUrl($rel, $options);
    // Add the user parameter to any routes that require it.
    if ($rel == 'developer_collection') {
      // Strip the `purchased_plan` parameter from the collection.
      $url->setRouteParameters(array_diff_key($url->getRouteParameters(), ['purchased_plan' => NULL]));
      // Set the developer's user ID.
      $url->setRouteParameter('user', $this->getOwnerId());
    }

    return $url;
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
  public function isActive(): bool {
    return ($this->getStatus() === PurchasedPlanInterface::STATUS_ACTIVE
      || $this->getStatus() === PurchasedPlanInterface::STATUS_FUTURE);
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
  public function getStatus(): string {
    $org_timezone = $this->getRatePlan()->getOrganization()->getTimezone();
    $today = new \DateTime('today', $org_timezone);

    // If rate plan ended before today, the status is ended.
    $plan_end_date = $this->getRatePlan()->getEndDate();
    if (!empty($plan_end_date) && $plan_end_date < $today) {
      return PurchasedPlanInterface::STATUS_ENDED;
    }
    // If the developer ended the plan before today, the plan has ended.
    $developer_plan_end_date = $this->getEndDate();
    if (!empty($developer_plan_end_date) && $developer_plan_end_date < $today) {
      return PurchasedPlanInterface::STATUS_ENDED;
    }

    // If the start date is later than today, it is a future plan.
    $developer_plan_start_date = $this->getStartDate();
    if (!empty($developer_plan_start_date) && $developer_plan_start_date > $today) {
      return PurchasedPlanInterface::STATUS_FUTURE;
    }

    return PurchasedPlanInterface::STATUS_ACTIVE;
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
   * {@inheritdoc}
   */
  public function setSuppressWarning(bool $value): PurchasedPlanInterface {
    $this->suppressWarning = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuppressWarning(): bool {
    return $this->suppressWarning;
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

  /**
   * {@inheritdoc}
   */
  public function getEndDate(): ?\DateTimeImmutable {
    return $this->getAdjustedEndDate();
  }

  /**
   * Adjust the date so it never ends before it starts.
   *
   * @return \DateTimeImmutable|null
   *   The adjusted end date.
   *
   * @throws \Exception
   */
  protected function getAdjustedEndDate() : ?\DateTimeImmutable {
    $today = new \DateTime('today', $this->getRatePlan()->getOrganization()->getTimezone());
    // If the plan has ended, make sure the end date doesn't come before the
    // start date.
    if (($end_date = $this->traitGetEndDate())
      && ($start_date = $this->getStartDate())
      && $start_date <= $today
      && $end_date < $start_date
    ) {
      return $start_date;
    }
    else {
      return $end_date;
    }
  }

}
