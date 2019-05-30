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
use Apigee\Edge\Api\Monetization\Entity\StandardRatePlan;
use Apigee\Edge\Api\Monetization\Structure\RatePlanDetail;
use Apigee\Edge\Entity\EntityInterface as EdgeEntityInterface;
use Drupal\apigee_edge\Entity\FieldableEdgeEntityBase;
use Drupal\apigee_m10n\Entity\Property\CurrencyPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\DescriptionPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\DisplayNamePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\EndDatePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\FreemiumPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\IdPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\NamePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\OrganizationPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\PackagePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\PaymentDueDaysPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\StartDatePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Form\SubscriptionConfigForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\Entity\User;

/**
 * Defines the Package Rate Plan entity class.
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
 *     "access" = "Drupal\entity\EntityAccessControlHandlerBase",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   links = {
 *     "canonical" = "/user/{user}/monetization/package/{package}/plan/{rate_plan}",
 *     "subscribe" = "/user/{user}/monetization/package/{package}/plan/{rate_plan}/subscribe",
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
  use EndDatePropertyAwareDecoratorTrait;
  use FreemiumPropertyAwareDecoratorTrait;
  use IdPropertyAwareDecoratorTrait;
  use NamePropertyAwareDecoratorTrait;
  use OrganizationPropertyAwareDecoratorTrait;
  use PackagePropertyAwareDecoratorTrait;
  use PaymentDueDaysPropertyAwareDecoratorTrait;
  use StartDatePropertyAwareDecoratorTrait;

  public const ENTITY_TYPE_ID = 'rate_plan';

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
  protected static function propertyToBaseFieldTypeMap(): array {
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
      'ratePlanDetails'       => 'apigee_rate_plan_details',
      'recurringFee'          => 'decimal',
      'recurringStartUnit'    => 'integer',
      'setUpFee'              => 'decimal',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected static function getProperties(): array {
    return [
      'subscribe' => 'apigee_subscribe',
      'packageEntity' => 'entity_reference',
      'packageProducts' => 'entity_reference',
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
    // Allow the package to be accessed as a field but not rendered because
    // rendering the package within a rate plan would cause recursion.
    $definitions['package']->setDisplayConfigurable('view', FALSE);
    // If the subscription label setting is available, use it.
    $subscribe_label = \Drupal::config(SubscriptionConfigForm::CONFIG_NAME)->get('subscribe_label');
    // `$subscribe_label` is not translated, use `config_translation` instead.
    $definitions['subscribe']->setLabel($subscribe_label ?? t('Purchase'));

    // The API products are many-to-one.
    $definitions['packageEntity']->setCardinality(1)
      ->setSetting('target_type', 'package')
      ->setLabel(t('Package'))
      ->setDescription(t('The API package the rate plan belongs to.'));

    // The API products are many-to-one.
    $definitions['packageProducts']->setCardinality(-1)
      ->setSetting('target_type', 'api_product')
      ->setLabel(t('Products'))
      ->setDescription(t('Products included in the API package.'));

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
  public static function loadPackageRatePlans(string $package_name): array {
    return \Drupal::entityTypeManager()
      ->getStorage(static::ENTITY_TYPE_ID)
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
      ->getStorage(static::ENTITY_TYPE_ID)
      ->loadById($package_name, $id);
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
    $url->setRouteParameter('package', $this->getPackage()->id());

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscribe():? array {
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
  public function getCacheContexts() {
    return array_merge(['url.developer'], parent::getCacheContexts());
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
  public function getEarlyTerminationFee(): ?float {
    return $this->decorated->getEarlyTerminationFee();
  }

  /**
   * {@inheritdoc}
   */
  public function setEarlyTerminationFee(float $earlyTerminationFee): void {
    $this->decorated->setEarlyTerminationFee($earlyTerminationFee);
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
  public function getRecurringFee(): ?float {
    return $this->decorated->getRecurringFee();
  }

  /**
   * {@inheritdoc}
   */
  public function setRecurringFee(float $recurringFee): void {
    $this->decorated->setRecurringFee($recurringFee);
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
  public function getSetUpFee(): float {
    return $this->decorated->getSetUpFee();
  }

  /**
   * {@inheritdoc}
   */
  public function setSetUpFee(float $setUpFee): void {
    $this->decorated->setSetUpFee($setUpFee);
  }

  /**
   * {@inheritdoc}
   */
  public function getPackageEntity() {
    return ['target_id' => $this->getPackage()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getPackageProducts() {
    return $this
      ->get('packageEntity')
      ->first()
      ->get('entity')
      ->getValue()
      ->getApiProducts();
  }

}
