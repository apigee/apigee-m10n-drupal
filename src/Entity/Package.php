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
 *     "canonical" = "/user/{user}/monetization/packages/{package}",
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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $definitions */
    $definitions = parent::baseFieldDefinitions($entity_type);
    // The rate plan details are many-to-one.
    $definitions['apiProducts']->setCardinality(-1);
    $definitions['apiProducts']->setSetting('target_type', 'api_product');

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
      ->getStorage('package')
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
