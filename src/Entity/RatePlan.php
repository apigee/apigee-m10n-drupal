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
use Apigee\Edge\Api\Monetization\Entity\RatePlanRevisionInterface;
use Apigee\Edge\Api\Monetization\Entity\StandardRatePlan;
use Apigee\Edge\Api\Monetization\Structure\RatePlanDetail;
use Apigee\Edge\Entity\EntityInterface as EdgeEntityInterface;
use Drupal\apigee_edge\Entity\FieldableEdgeEntityBase;
use Drupal\apigee_m10n\Entity\Property\CurrencyPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\DescriptionPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\DisplayNamePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\EarlyTerminationFeePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\EndDatePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\FreemiumPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\IdPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\NamePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\OrganizationPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\PackagePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\PaymentDueDaysPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\RecurringFeePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\SetUpFeePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\StartDatePropertyAwareDecoratorTrait;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\Entity\User;

/**
 * Defines the rate plan entity class.
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
 *     "access" = "Drupal\apigee_m10n\Entity\Access\RatePlanAccessControlHandler",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   links = {
 *     "canonical" = "/user/{user}/monetization/product-bundle/{product_bundle}/plan/{rate_plan}",
 *     "purchase" = "/user/{user}/monetization/product-bundle/{product_bundle}/plan/{rate_plan}/purchase",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   admin_permission = "administer apigee monetization",
 *   field_ui_base_route = "apigee_m10n.settings.rate_plan",
 * )
 */
class RatePlan extends FieldableEdgeEntityBase implements RatePlanInterface {

  use CurrencyPropertyAwareDecoratorTrait;
  use DescriptionPropertyAwareDecoratorTrait;
  use DisplayNamePropertyAwareDecoratorTrait;
  use EarlyTerminationFeePropertyAwareDecoratorTrait;
  use EndDatePropertyAwareDecoratorTrait;
  use FreemiumPropertyAwareDecoratorTrait;
  use IdPropertyAwareDecoratorTrait;
  use NamePropertyAwareDecoratorTrait;
  use OrganizationPropertyAwareDecoratorTrait;
  use PackagePropertyAwareDecoratorTrait;
  use PaymentDueDaysPropertyAwareDecoratorTrait;
  use RecurringFeePropertyAwareDecoratorTrait;
  use SetUpFeePropertyAwareDecoratorTrait;
  use StartDatePropertyAwareDecoratorTrait;

  public const ENTITY_TYPE_ID = 'rate_plan';

  /**
   * The future rate plan of this rate plan.
   *
   * If this is set to FALSE, we've already checked for a future plan and there
   * isn't one.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface|false
   */
  protected $futureRatePlan;


  /**
   * The current rate plan if this is a future rate plan.
   *
   * If this is set to FALSE, this is not a future plan or we've already tried
   * to locate a previous revision that is currently available and failed.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface|false
   */
  protected $currentRatePlan;

  /**
   * Constructs a `rate_plan` entity.
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
  }

  /**
   * {@inheritdoc}
   */
  protected static function decoratedClass(): string {
    return StandardRatePlan::class;
  }

  /**
   * {@inheritdoc}
   */
  public static function idProperty(): string {
    return MonetizationRatePlan::idProperty();
  }

  /**
   * {@inheritdoc}
   */
  protected static function propertyToBaseFieldBlackList(): array {
    return array_merge(parent::propertyToBaseFieldBlackList(), ['package']);
  }

  /**
   * {@inheritdoc}
   */
  protected static function propertyToBaseFieldTypeMap(): array {
    return [
      'startDate'             => 'timestamp',
      'endDate'               => 'timestamp',
      'contractDuration'      => 'integer',
      'currency'              => 'apigee_currency',
      'earlyTerminationFee'   => 'apigee_price',
      'freemiumDuration'      => 'integer',
      'freemiumDurationType'  => 'string',
      'freemiumUnit'          => 'integer',
      'frequencyDuration'     => 'integer',
      'futurePlanStartDate'   => 'timestamp',
      'organization'          => 'apigee_organization',
      'paymentDueDays'        => 'integer',
      'ratePlanDetails'       => 'apigee_rate_plan_details',
      'recurringFee'          => 'apigee_price',
      'recurringStartUnit'    => 'integer',
      'setUpFee'              => 'apigee_price',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected static function getProperties(): array {
    return [
      'purchase'            => 'apigee_purchase',
      'futurePlanLinks'     => 'link',
      'futurePlanStartDate' => 'timestamp',
      'productBundle'       => 'entity_reference',
      'products'            => 'entity_reference',
    ] + parent::getProperties();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $definitions */
    $definitions = parent::baseFieldDefinitions($entity_type);
    // The rate plan details are many-to-one.
    $definitions['ratePlanDetails']->setCardinality(-1);
    $definitions['purchase']->setLabel(t('Purchase'));

    // The API products are many-to-one.
    $definitions['productBundle']->setCardinality(1)
      ->setSetting('target_type', 'product_bundle')
      ->setLabel(t('Product bundle'))
      ->setDescription(t('The API product bundle the rate plan belongs to.'));

    // The API products are many-to-one.
    $definitions['products']->setCardinality(-1)
      ->setSetting('target_type', 'api_product')
      ->setLabel(t('Products'))
      ->setDescription(t('Products included in the product bundle.'));

    return $definitions;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function loadRatePlansByProductBundle(string $product_bundle): array {
    return \Drupal::entityTypeManager()
      ->getStorage(static::ENTITY_TYPE_ID)
      ->loadRatePlansByProductBundle($product_bundle);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function loadById(string $product_bundle_id, string $id): RatePlanInterface {
    return \Drupal::entityTypeManager()
      ->getStorage(static::ENTITY_TYPE_ID)
      ->loadById($product_bundle_id, $id);
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalEntityId(): ?string {
    return $this->decorated->id();
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    // The string formatter assumes entities are revisionable.
    $rel = ($rel === 'revision') ? 'canonical' : $rel;
    // Build the URL.
    $url = parent::toUrl($rel, $options);
    $url->setRouteParameter('user', $this->getUser()->id());
    $url->setRouteParameter('product_bundle', $this->getProductBundleId());

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldValue(string $field_name) {
    // Add the price value to the field name for price items.
    $field_name = in_array($field_name, [
      'earlyTerminationFee',
      'recurringFee',
      'setUpFee',
    ]) ? "{$field_name}PriceValue" : $field_name;

    return parent::getFieldValue($field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchase():? array {
    return [
      'user' => $this->getUser(),
    ];
  }

  /**
   * Get user from route parameter and fall back to current user if empty.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   Returns user entity.
   */
  private function getUser() {
    // The route parameters still need to be set.
    $route_user = \Drupal::routeMatch()->getParameter('user');
    // Sometimes the param converter hasn't converted the user.
    if (is_string($route_user)) {
      $route_user = User::load($route_user);
    }
    return $route_user ?: \Drupal::currentUser();
  }

  /**
   * {@inheritdoc}
   */
  public function getFuturePlanStartDate(): ?\DateTimeImmutable {
    return ($future_plan = $this->getFutureRatePlan()) ? $future_plan->getStartDate() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFuturePlanLinks() {
    // Display the current plan link if this is a future rate plan.
    if ($this->isFutureRatePlan() && ($current = $this->getCurrentRatePlan())) {
      return [
        [
          'title' => t('Current rate plan'),
          'uri' => $current->toUrl()->toUriString(),
          'options' => ['attributes' => ['class' => ['rate-plan-current-link']]],
        ],
        [
          'title' => t('Future rate plan'),
          'uri' => $this->toUrl()->toUriString(),
          'options' => ['attributes' => ['class' => ['is-active', 'rate-plan-future-link']]],
        ],
      ];
    }
    // Display the future plan link if one exists.
    if ($future_plan = $this->getFutureRatePlan()) {
      return [
        [
          'title' => t('Current rate plan'),
          'uri' => $this->toUrl()->toUriString(),
          'options' => ['attributes' => ['class' => ['is-active', 'rate-plan-current-link']]],
        ],
        [
          'title' => t('Future rate plan'),
          'uri' => $future_plan->toUrl()->toUriString(),
          'options' => ['attributes' => ['class' => ['rate-plan-future-link']]],
        ],
      ];
    }

    return NULL;
  }

  /**
   * Gets whether or not this is a future plan.
   *
   * @return bool
   *   Whether or not this is a future plan.
   */
  protected function isFutureRatePlan(): bool {
    $start_date = $this->getStartDate();
    $today = new \DateTime('today', $start_date->getTimezone());
    // This is a future rate plan if it is a revision and the start date is in
    // the future.
    return $this->decorated() instanceof RatePlanRevisionInterface && $today < $start_date;
  }

  /**
   * Gets the future plan for this rate plan if one exists.
   *
   * @return \Drupal\apigee_m10n\Entity\RatePlanInterface|null
   *   The future rate plan or null.
   */
  protected function getFutureRatePlan(): ?RatePlanInterface {
    if (!isset($this->futureRatePlan)) {
      // Use the entity storage to load the future rate plan.
      $this->futureRatePlan = ($future_plan = \Drupal::entityTypeManager()->getStorage($this->entityTypeId)->loadFutureRatePlan($this)) ? $future_plan : FALSE;
    }

    return $this->futureRatePlan ?: NULL;
  }

  /**
   * Gets the current plan if this is a future plan.
   *
   * @return \Drupal\apigee_m10n\Entity\RatePlanInterface|null
   *   The current rate plan or null.
   */
  protected function getCurrentRatePlan(): ?RatePlanInterface {
    if (!isset($this->currentRatePlan)) {
      // Check whether or not this plan has a parent plan.
      if (($decorated = $this->decorated()) && $decorated instanceof RatePlanRevisionInterface) {
        // Create a date to compare whether a plan is current. Current means the
        // plan has already started and is a parent revision of this plan.
        $today = new \DateTimeImmutable('today', $this->getStartDate()->getTimezone());
        // The previous revision is our starting point.
        $parent_plan = $decorated->getPreviousRatePlanRevision();
        // Loop through parents until the current plan is found.
        while ($parent_plan && ($today < $parent_plan->getStartDate() || $today > $parent_plan->getEndDate())) {
          // Get the next parent if it exists.
          $parent_plan = $parent_plan instanceof RatePlanRevisionInterface ? $parent_plan->getPreviousRatePlanRevision() : NULL;
        }
        // If the $parent_plan is currently available, it is our current plan.
        $this->currentRatePlan = ($parent_plan->getStartDate() < $today && $parent_plan->getEndDate() > $today)
          ? RatePlan::createFrom($parent_plan)
          : FALSE;
      }
      else {
        $this->currentRatePlan = FALSE;
      }
    }

    return $this->currentRatePlan ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeTags(parent::getCacheContexts(), ['url.developer']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ($entity = $this->get('productBundle')->entity) ? $entity->getCacheTags() : []);
  }

  /**
   * {@inheritdoc}
   */
  public function isAdvance(): bool {
    return $this->decorated->isAdvance();
  }

  /**
   * {@inheritdoc}
   */
  public function setAdvance(bool $advance): void {
    $this->decorated->setAdvance($advance);
  }

  /**
   * {@inheritdoc}
   */
  public function getContractDuration(): ?int {
    return $this->decorated->getContractDuration();
  }

  /**
   * {@inheritdoc}
   */
  public function setContractDuration(int $contractDuration): void {
    $this->decorated->setContractDuration($contractDuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getContractDurationType(): ?string {
    return $this->decorated->getContractDurationType();
  }

  /**
   * {@inheritdoc}
   */
  public function setContractDurationType(string $contractDurationType): void {
    $this->decorated->setContractDurationType($contractDurationType);
  }

  /**
   * {@inheritdoc}
   */
  public function getFrequencyDuration(): ?int {
    return $this->decorated->getFrequencyDuration();
  }

  /**
   * {@inheritdoc}
   */
  public function setFrequencyDuration(int $frequencyDuration): void {
    $this->decorated->setFrequencyDuration($frequencyDuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getFrequencyDurationType(): ?string {
    return $this->decorated->getFrequencyDurationType();
  }

  /**
   * {@inheritdoc}
   */
  public function setFrequencyDurationType(string $frequencyDurationType): void {
    $this->decorated->setFrequencyDurationType($frequencyDurationType);
  }

  /**
   * {@inheritdoc}
   */
  public function isPrivate(): bool {
    return $this->decorated->isPrivate();
  }

  /**
   * {@inheritdoc}
   */
  public function setPrivate(bool $private): void {
    $this->decorated->setPrivate($private);
  }

  /**
   * {@inheritdoc}
   */
  public function isProrate(): bool {
    return $this->decorated->isProrate();
  }

  /**
   * {@inheritdoc}
   */
  public function setProrate(bool $prorate): void {
    $this->decorated->setProrate($prorate);
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished(): bool {
    return $this->decorated->isPublished();
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished(bool $published): void {
    $this->decorated->setPublished($published);
  }

  /**
   * {@inheritdoc}
   */
  public function getRatePlanDetails(): array {
    return $this->decorated->getRatePlanDetails();
  }

  /**
   * {@inheritdoc}
   */
  public function setRatePlanDetails(RatePlanDetail ...$ratePlanDetails): void {
    $this->decorated->setRatePlanDetails(...$ratePlanDetails);
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurringStartUnit(): ?int {
    return $this->decorated->getRecurringStartUnit();
  }

  /**
   * {@inheritdoc}
   */
  public function setRecurringStartUnit(int $recurringStartUnit): void {
    $this->decorated->setRecurringStartUnit($recurringStartUnit);
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurringType(): string {
    return $this->decorated->getRecurringType();
  }

  /**
   * {@inheritdoc}
   */
  public function setRecurringType(string $recurringType): void {
    $this->decorated->setRecurringType($recurringType);
  }

  /**
   * {@inheritdoc}
   */
  public function getProductBundle() {
    return ['target_id' => $this->getProductBundleId()];
  }

  /**
   * {@inheritdoc}
   */
  public function getProductBundleId() {
    return ($package = $this->decorated()->getPackage()) ? $package->id() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getProducts() {
    return $this
      ->get('productBundle')
      ->first()
      ->get('entity')
      ->getValue()
      ->getApiProducts();
  }

}
