<?php

/*
 * Copyright 2021 Google Inc.
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

use Apigee\Edge\Entity\EntityInterface as EdgeEntityInterface;
use Drupal\apigee_edge\Entity\FieldableEdgeEntityBase;
use Drupal\apigee_m10n\Entity\Property\DescriptionPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\BillingPeriodPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\PaymentFundingModelPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\CurrencyCodePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\FixedFeeFrequencyPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\ConsumptionPricingTypePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\RevenueShareTypePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\StartTimePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\EndTimePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\DisplayNamePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\ApiXProductPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\IdPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\NamePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\XPackagePropertyAwareDecoratorTrait;
use Apigee\Edge\Api\Monetization\Entity\DeveloperCategoryRatePlanInterface;
use Apigee\Edge\Api\Monetization\Entity\DeveloperInterface;
use Apigee\Edge\Api\Monetization\Entity\DeveloperRatePlanInterface;
use Apigee\Edge\Api\ApigeeX\Entity\RatePlanRevisionInterface;
use Apigee\Edge\Api\ApigeeX\Structure\RatePlanXFee;
use Apigee\Edge\Api\ApigeeX\Entity\RatePlan as MonetizationRatePlan;
use Apigee\Edge\Api\ApigeeX\Entity\StandardRatePlan;
use Apigee\Edge\Api\ApigeeX\Structure\FixedRecurringFee;
use Apigee\Edge\Api\ApigeeX\Structure\ConsumptionPricingRate;
use Apigee\Edge\Api\ApigeeX\Structure\RevenueShareRates;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\Entity\User;

/**
 * Defines the xrate plan entity class.
 *
 * @\Drupal\apigee_edge\Annotation\EdgeEntityType(
 *   id = "xrate_plan",
 *   label = @Translation("XRate plan"),
 *   label_singular = @Translation("XRate plan"),
 *   label_plural = @Translation("XRate plans"),
 *   label_count = @PluralTranslation(
 *     singular = "@count XRate plan",
 *     plural = "@count XRate plans",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\apigee_m10n\Entity\Storage\XRatePlanStorage",
 *     "access" = "Drupal\apigee_m10n\Entity\Access\XRatePlanAccessControlHandler",
 *     "subscription_access" = "Drupal\apigee_m10n\Entity\Access\XRatePlanSubscriptionAccessHandler",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   links = {
 *     "canonical" = "/user/{user}/monetization/xproduct/{xproduct}/plan/{xrate_plan}",
 *     "purchase" = "/user/{user}/monetization/xproduct/{xproduct}/plan/{xrate_plan}/purchase",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   admin_permission = "administer apigee monetization",
 *   field_ui_base_route = "apigee_m10n.settings.rate_plan_x",
 * )
 */
class XRatePlan extends FieldableEdgeEntityBase implements XRatePlanInterface {

  use DescriptionPropertyAwareDecoratorTrait;
  use BillingPeriodPropertyAwareDecoratorTrait;
  use PaymentFundingModelPropertyAwareDecoratorTrait;
  use CurrencyCodePropertyAwareDecoratorTrait;
  use FixedFeeFrequencyPropertyAwareDecoratorTrait;
  use ConsumptionPricingTypePropertyAwareDecoratorTrait;
  use RevenueShareTypePropertyAwareDecoratorTrait;
  use StartTimePropertyAwareDecoratorTrait;
  use EndTimePropertyAwareDecoratorTrait;
  use IdPropertyAwareDecoratorTrait;
  use NamePropertyAwareDecoratorTrait;
  use XPackagePropertyAwareDecoratorTrait;
  use ApiXProductPropertyAwareDecoratorTrait;
  use DisplayNamePropertyAwareDecoratorTrait;

  public const ENTITY_TYPE_ID = 'xrate_plan';

  /**
   * The future xrate plan of this rate plan.
   *
   * If this is set to FALSE, we've already checked for a future plan and there
   * isn't one.
   *
   * @var \Drupal\apigee_m10n\Entity\XRatePlanInterface|false
   */
  protected $futureRatePlan;


  /**
   * The current rate plan if this is a future rate plan.
   *
   * If this is set to FALSE, this is not a future plan or we've already tried
   * to locate a previous revision that is currently available and failed.
   *
   * @var \Drupal\apigee_m10n\Entity\XRatePlanInterface|false
   */
  protected $currentRatePlan;

  /**
   * Constructs a `xrate_plan` entity.
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
      'name'            => 'string',
      'setupFees'       => 'apigee_price',
      'recurringFees'   => 'apigee_price',
      'consumptionFee'  => 'apigee_price',
      'startTime'       => 'string',
      'ratePlanXFee' => 'apigee_rate_plan_xfee',
      'fixedRecurringFee' => 'apigee_rate_plan_fixed_recurringfee',
      'consumptionPricingRates' => 'apigee_rate_plan_consumption_rates',
      'revenueShareRates' => 'apigee_rate_plan_revenue_rates',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected static function getProperties(): array {
    return [
      'purchase' => 'apigee_purchase_product',
      'xproduct' => 'entity_reference',
      'apiProduct' => 'entity_reference',
      'setupFees' => 'apigee_price',
      'recurringFees' => 'apigee_price',
      'consumptionFee' => 'apigee_price',
      'feeFrequency' => 'string',
    ] + parent::getProperties();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $definitions */
    $definitions = parent::baseFieldDefinitions($entity_type);
    $definitions['name']->setLabel(t('RatePlan ID'));
    $definitions['startTime']->setLabel(t('Active on'));
    $definitions['endTime']->setLabel(t('Expire on'));
    $definitions['feeFrequency']->setLabel(t('Recurring Fee Frequency'));

    // The API products are many-to-one.
    $definitions['apiProduct']->setCardinality(1)
      ->setSetting('target_type', 'api_product')
      ->setLabel(t('Product'))
      ->setDescription(t('The API product X the rate plan belongs to.'));

    $definitions['purchase']->setLabel(t('Purchase'));
    $definitions['displayName']->setLabel(t('RatePlan'));

    return $definitions;
  }

  /**
   * Filter all the rate plans according to startTime/endTime.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private static function filterActiveRatePlans(array $rateplans) {
    $activeRatePlan = [];
    // Get Current UTC timestamp & Transform timeRange to the required format and time zone.
    $utc = new \DateTimeZone('UTC');
    $currentTimestamp = (new \DateTimeImmutable())->setTimezone($utc);
    $currentTimestamp = (int) ($currentTimestamp->getTimestamp() . $currentTimestamp->format('v'));

    /** @var \Apigee\Edge\Api\ApigeeX\Entity\RatePlanInterface $entity */
    foreach ($rateplans as $id => $entity) {
      try {
        $startTime = (int) $entity->decorated()->getStartTime();
        $endTime = (int) $entity->decorated()->getEndTime() ?? 0;

        if ($startTime <= $currentTimestamp && ($endTime === 0 || $currentTimestamp <= $endTime)) {
          $activeRatePlan[] = $rateplans[$id];
        }
      }
      catch (InvalidRatePlanIdException $exception) {
        watchdog_exception('apigee_m10n', $exception);
      }
    }

    return $activeRatePlan;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function loadRatePlansByProduct(string $product_bundle): array {
    return XRatePlan::filterActiveRatePlans(\Drupal::entityTypeManager()
      ->getStorage(static::ENTITY_TYPE_ID)
      ->loadRatePlansByProduct($product_bundle));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function loadById(string $product_bundle_id, string $id): XRatePlanInterface {
    return \Drupal::entityTypeManager()
      ->getStorage(static::ENTITY_TYPE_ID)
      ->loadById($product_bundle_id, $id);
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalEntityId(): ?string {
    // When the page cache is cleared , ID of the rate plan is lost.
    // return the name of the rate plan ie ID.
    return $this->decorated->id() ?? $this->decorated->getName();

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
    $url->setRouteParameter('xproduct', $this->getProductId());

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldValue(string $field_name) {
    // Add the price value to the field name for price items.
    $field_name = in_array($field_name, [
      'setupFees',
      'recurringFees',
      'consumptionFee',
    ]) ? "{$field_name}PriceValue" : $field_name;

    $field_name = in_array($field_name, [
      'feeFrequency',
      'startTime',
      'endTime',
    ]) ? "{$field_name}Format" : $field_name;

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
   * Get's user id.
   *
   * @return string
   *   Returns user id.
   */
  public function getOwnId() {
    return $this->getUser()->id();
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
    return Cache::mergeTags(parent::getCacheTags(), ($entity = $this->get('xproduct')->entity) ? $entity->getCacheTags() : []);
  }

  /**
   * {@inheritdoc}
   */
  public function getRatePlanxFee(): array {
    return $this->decorated->getRatePlanxFee();
  }

  /**
   * {@inheritdoc}
   */
  public function setRatePlanxFee(RatePlanXFee ...$ratePlanXFee): void {
    $this->decorated->setRatePlanxFee(...$ratePlanXFee);
  }

  /**
   * {@inheritdoc}
   */
  public function getFixedRecurringFee(): array {
    return $this->decorated->getFixedRecurringFee();
  }

  /**
   * {@inheritdoc}
   */
  public function setFixedRecurringFee(FixedRecurringFee ...$fixedRecurringFee): void {
    $this->decorated->setFixedRecurringFee(...$fixedRecurringFee);
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumptionPricingRates(): array {
    return $this->decorated->getConsumptionPricingRates();
  }

  /**
   * {@inheritdoc}
   */
  public function setConsumptionPricingRates(ConsumptionPricingRate ...$consumptionPricingRates): void {
    $this->decorated->setConsumptionPricingRates(...$consumptionPricingRates);
  }

  /**
   * {@inheritdoc}
   */
  public function getRevenueShareRates(): array {
    return $this->decorated->getRevenueShareRates();
  }

  /**
   * {@inheritdoc}
   */
  public function setRevenueShareRates(RevenueShareRates ...$revenueShareRates): void {
    $this->decorated->setRevenueShareRates(...$revenueShareRates);
  }

  /**
   * {@inheritdoc}
   */
  public function getProductBundle() {
    return ['target_id' => $this->getProductId()];
  }

  /**
   * {@inheritdoc}
   */
  public function getProductId() {
    return $this->decorated()->getApiProduct() ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    if ($this->decorated instanceof DeveloperRatePlanInterface) {
      return XRatePlanInterface::TYPE_DEVELOPER;
    }
    elseif ($this->decorated instanceof DeveloperCategoryRatePlanInterface) {
      return XRatePlanInterface::TYPE_DEVELOPER_CATEGORY;
    }

    return XRatePlanInterface::TYPE_STANDARD;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeveloper(): ?DeveloperInterface {
    if ($this->decorated instanceof DeveloperRatePlanInterface) {
      return $this->decorated->getDeveloper();
    }

    return NULL;
  }

  /**
   * Gets the Setup fee values as used by a field item from RateplanXFee.
   *
   * @return array
   *   The Setup fee price value.
   */
  public function getSetupFeesPriceValue() {
    $setupfee_rate = [];
    $setupfee_details = $this->getRatePlanxFee();

    foreach ($setupfee_details as $key => $value) {
      $currencyCode = $value->getCurrencyCode();
      $nanos = $value->getNanos() ?? 0;
      $units = $value->getUnits() ?? 0;
      $setup_rate = $units + $nanos;
      $setupfee_rate[$key]['amount'] = $setup_rate;
      $setupfee_rate[$key]['currency_code'] = $currencyCode;
    }
    return $setupfee_rate;
  }

  /**
   * Gets the fixed recurring fee values as used by a field item from FixedRecurringFee.
   *
   * @return array
   *   The fixed recurring fee price value.
   */
  public function getRecurringFeesPriceValue() {
    $recurringfee_rate = [];
    $recurringfee_details = $this->getFixedRecurringFee();

    foreach ($recurringfee_details as $key => $value) {
      $currencyCode = $value->getCurrencyCode();
      $nanos = $value->getNanos() ?? "";
      $units = $value->getUnits() ?? 0;
      $setup_rate = $units + $nanos;

      $recurringfee_rate[$key]['amount'] = $setup_rate;
      $recurringfee_rate[$key]['currency_code'] = $currencyCode;
    }
    return $recurringfee_rate;
  }

  /**
   * Gets the consumption Pricing rates fee values as used by a field item from consumptionPricingRates.
   *
   * @return array
   *   The consumption Pricing rates fee price value.
   */
  public function getConsumptionFeePriceValue() {
    $fee_rate = [];
    $consumptionfee_details = $this->getConsumptionPricingRates();
    foreach ($consumptionfee_details as $key => $value) {
      $fee_details = $value->getFee();

      $fee_currency_code = $fee_details->getCurrencyCode();
      $fee_units = $fee_details->getUnits() ?? 0;
      $fee_nanos = $fee_details->getNanos() ?? 0;
      $setup_rate = $fee_units + $fee_nanos;

      $fee_rate[$key]['amount'] = $setup_rate;
      $fee_rate[$key]['currency_code'] = $fee_currency_code;
    }
    return $fee_rate;
  }

  /**
   * Gets the fee frequency and billing cycle from billingPeriod and fixedFeeFrequency.
   *
   * @return string
   *   The Fee frequency,Billing period value in "$# every 1 month(s)" this format.
   */
  public function getfeeFrequencyFormat() {

    $billingPeriod = $this->getbillingPeriod();
    $fixedFeeFrequency = $this->getfixedFeeFrequency();

    if ($billingPeriod === "MONTHLY") {
      $billingPeriod = "month(s)";
    }
    $feeFrequency = "every {$fixedFeeFrequency} {$billingPeriod}";

    return $feeFrequency;
  }

  /**
   * Converts the startTime milliseconds timestamp to Datetime.
   *
   * @return string
   *   The startTime timestamp to F j, Y format.
   */
  public function getstartTimeFormat() {
    $startTime_milliseconds = $this->getstartTime();
    if ($startTime_milliseconds) {
      $startTime_seconds = (int) ($startTime_milliseconds / 1000);
      $activeOn = \Drupal::service('date.formatter')->format($startTime_seconds, 'custom', 'F j, Y', date_default_timezone_get());
    }

    return $activeOn ?? NULL;
  }

  /**
   * Converts the endTime milliseconds timestamp to Datetime.
   *
   * @return string
   *   The endTime timestamp to F j, Y format.
   */
  public function getendTimeFormat() {
    $endTime_milliseconds = $this->getendTime();
    if ($endTime_milliseconds) {
      $endTime_seconds = (int) ($endTime_milliseconds / 1000);
      $endOn = \Drupal::service('date.formatter')->format($endTime_seconds, 'custom', 'F j, Y', date_default_timezone_get());
    }

    return $endOn ?? "Never";
  }

}
