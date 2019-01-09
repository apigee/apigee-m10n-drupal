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

use Apigee\Edge\Api\Monetization\Entity\ApiPackage;
use Apigee\Edge\Entity\EntityInterface as EdgeEntityInterface;
use Drupal\apigee_edge\Entity\FieldableEdgeEntityBase;
use Drupal\apigee_m10n\Entity\Property\ApiProductsPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\DescriptionPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\DisplayNamePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\IdPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\NamePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\OrganizationPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\StatusPropertyAwareDecoratorTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\Entity\User;

/**
 * Defines the `package` entity class.
 *
 * @\Drupal\apigee_edge\Annotation\EdgeEntityType(
 *   id = "package",
 *   label = @Translation("Package"),
 *   label_singular = @Translation("Package"),
 *   label_plural = @Translation("Packages"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Package",
 *     plural = "@count Packages",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\apigee_m10n\Entity\Storage\PackageStorage",
 *     "access" = "Drupal\apigee_edge\Entity\EdgeEntityAccessControlHandler",
 *     "permission_provider" = "Drupal\apigee_edge\Entity\EdgeEntityPermissionProviderBase",
 *     "list_builder" = "Drupal\apigee_m10n\Entity\ListBuilder\PackageListBuilder",
 *   },
 *   links = {
 *     "canonical" = "/user/{user}/monetization/package/{package}",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   permission_granularity = "entity_type",
 *   admin_permission = "administer package",
 *   field_ui_base_route = "entity.package.collection",
 * )
 */
class Package extends FieldableEdgeEntityBase implements PackageInterface {

  use ApiProductsPropertyAwareDecoratorTrait;
  use DisplayNamePropertyAwareDecoratorTrait;
  use DescriptionPropertyAwareDecoratorTrait;
  use IdPropertyAwareDecoratorTrait;
  use NamePropertyAwareDecoratorTrait;
  use OrganizationPropertyAwareDecoratorTrait;
  use StatusPropertyAwareDecoratorTrait;

  public const ENTITY_TYPE_ID = 'package';

  /**
   * Rate plans available for this package.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface[]
   */
  protected $ratePlans;

  /**
   * Constructs a `package` entity.
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
    return ApiPackage::class;
  }

  /**
   * {@inheritdoc}
   */
  public static function idProperty(): string {
    return ApiPackage::idProperty();
  }

  /**
   * {@inheritdoc}
   */
  protected static function propertyToBaseFieldTypeMap(): array {
    return [
      'apiProducts' => 'entity_reference',
      'organization' => 'apigee_organization',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected static function getProperties(): array {
    $properties = parent::getProperties();
    $properties['ratePlans'] = 'entity_reference';

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $definitions */
    $definitions = parent::baseFieldDefinitions($entity_type);
    // The API products are many-to-one.
    $definitions['apiProducts']->setCardinality(-1);
    $definitions['apiProducts']->setSetting('target_type', 'api_product');
    $definitions['apiProducts']->setLabel(t('Included products'));

    // The rate plans are many-to-one.
    $definitions['ratePlans']->setCardinality(-1);
    $definitions['ratePlans']->setSetting('target_type', 'rate_plan');
    $definitions['ratePlans']->setLabel(t('Available rate plans'));

    // Fix some labels because these show up in the UI.
    $definitions['id']->setLabel(t('ID'));
    $definitions['displayName']->setLabel(t('Plan name'));

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function convertFieldValueToPropertyValue(string $field_name) {
    $value = parent::convertFieldValueToPropertyValue($field_name);

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $definition */
    $definition = $this->getFieldDefinition($field_name);
    if ($definition->getFieldStorageDefinition()->getCardinality() !== 1
      && $definition->getType() === 'entity_reference'
    ) {
      $value = [];
      foreach ($this->get($field_name) as $index => $item) {
        $value[$index] = $item->entity;
      }

    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function getAvailableApiPackagesByDeveloper($developer_id) {
    return \Drupal::entityTypeManager()
      ->getStorage(static::ENTITY_TYPE_ID)
      ->getAvailableApiPackagesByDeveloper($developer_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setApiProducts($api_products) {
    // Modifying packages isn't actually allowed but the `setPropertyValue()`
    // from `\Drupal\apigee_edge\Entity\FieldableEdgeEntityBase` will try to set
    // the property on the `obChange` TypedData event.
  }

  /**
   * Gets the rate plans for this package.
   *
   * @return array
   *   An array of rate plan entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRatePlans() {
    if (!isset($this->ratePlans)) {
      // Get the access control handler for rate plans.
      $rate_plan_access_handler = $this->entityTypeManager()->getAccessControlHandler('rate_plan');
      $admin_access = \Drupal::currentUser()->hasPermission('administer rate_plan');

      $package_rate_plans = RatePlan::loadPackageRatePlans($this->id());
      // Load plans for each package.
      if (!$admin_access) {
        // Check access for each rate plan since the user is not an admin.
        $package_rate_plans = array_filter($package_rate_plans, function ($rate_plan) use ($rate_plan_access_handler) {
          return $rate_plan_access_handler->access($rate_plan, 'view');
        });
      }

      $this->ratePlans = array_values($package_rate_plans);
    }

    return $this->ratePlans;
  }

  /**
   * Get's the ApiProducts from the decorated object.
   *
   * The `ApiProductsPropertyAwareDecoratorTrait` trait will return API products
   * drupal entities but if you need the raw monetization `ApiProducts` you can
   * get them with this method.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\ApiProduct[]
   *   The native monetization API Products associated with this package.
   */
  public function getMonetizationApiProducts(): array {
    return $this->decorated()->getApiProducts();
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

    return $url;
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

}
