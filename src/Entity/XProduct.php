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

use Apigee\Edge\Api\ApigeeX\Entity\ApiProduct;
use Apigee\Edge\Entity\EntityInterface as EdgeEntityInterface;
use Drupal\apigee_edge\Entity\FieldableEdgeEntityBase;
use Drupal\apigee_m10n\Entity\Property\DescriptionPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\DisplayNamePropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\IdPropertyAwareDecoratorTrait;
use Drupal\apigee_m10n\Entity\Property\NamePropertyAwareDecoratorTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the `xproduct` entity class.
 *
 * @\Drupal\apigee_edge\Annotation\EdgeEntityType(
 *   id = "xproduct",
 *   label = @Translation("XProduct"),
 *   label_singular = @Translation("XProduct"),
 *   label_plural = @Translation("XProduct"),
 *   label_count = @PluralTranslation(
 *     singular = "@count XProduct",
 *     plural = "@count XProducts",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\apigee_m10n\Entity\Storage\XProductStorage",
 *     "access" = "Drupal\entity\EntityAccessControlHandlerBase",
 *     "list_builder" = "Drupal\apigee_m10n\Entity\ListBuilder\XProductListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\apigee_m10n\Entity\Routing\MonetizationEntityRouteProvider",
 *     }
 *   },
 *   links = {
 *     "canonical" = "/monetization/xproduct/{xproduct}",
 *     "developer" = "/user/{user}/monetization/xproduct/{xproduct}",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   admin_permission = "administer apigee monetization",
 *   field_ui_base_route = "apigee_m10n.settings.xproduct",
 * )
 */
class XProduct extends FieldableEdgeEntityBase implements XProductInterface {

  use DisplayNamePropertyAwareDecoratorTrait;
  use DescriptionPropertyAwareDecoratorTrait;
  use IdPropertyAwareDecoratorTrait;
  use NamePropertyAwareDecoratorTrait;

  public const ENTITY_TYPE_ID = 'xproduct';

  /**
   * Rate plans available for this xproduct.
   *
   * @var \Drupal\apigee_m10n\Entity\XRatePlanInterface[]
   */
  protected $ratePlans;

  /**
   * Constructs a `xproduct` entity.
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
    return ApiProduct::class;
  }

  /**
   * {@inheritdoc}
   */
  public static function idProperty(): string {
    return ApiProduct::idProperty();
  }

  /**
   * {@inheritdoc}
   */
  protected static function propertyToBaseFieldTypeMap(): array {
    return [
      'ratePlans' => 'entity_reference',
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

    // The rate plans are many-to-one.
    $definitions['ratePlans']->setCardinality(-1);
    $definitions['ratePlans']->setSetting('target_type', 'xrate_plan');
    $definitions['ratePlans']->setLabel(t('Available rate plans'));

    // Fix some labels because these show up in the UI.
    $definitions['id']->setLabel(t('ID'));
    $definitions['displayName']->setLabel(t('Product'));

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
  public static function getAvailablexProductByDeveloper($developer_id) {
    return \Drupal::entityTypeManager()
      ->getStorage(static::ENTITY_TYPE_ID)
      ->getAvailablexProductByDeveloper($developer_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadAll() {
    return \Drupal::entityTypeManager()
      ->getStorage(static::ENTITY_TYPE_ID)
      ->loadAll();
  }

  /**
   * {@inheritdoc}
   */
  public function setApiProducts($api_products) {
    // Modifying xproduct isn't actually allowed but `setPropertyValue()`
    // from `\Drupal\apigee_edge\Entity\FieldableEdgeEntityBase` will try to set
    // the property on the `obChange` TypedData event.
  }

  /**
   * Gets the rate plans for this xproduct.
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
      $admin_access = \Drupal::currentUser()->hasPermission('administer apigee monetization');

      $rate_plans = XRatePlan::loadRatePlansByProduct($this->id());
      // Load plans for each xproduct.
      if (!$admin_access) {
        // Check access for each rate plan since the user is not an admin.
        $rate_plans = array_filter($rate_plans, function ($rate_plan) use ($rate_plan_access_handler) {
          return $rate_plan_access_handler->access($rate_plan, 'view');
        });
      }
      $this->ratePlans = array_values($rate_plans);
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
   *   The native monetization API Products associated with this product bundle.
   */
  public function getMonetizationApiProducts(): array {
    return $this->decorated()->getApiProducts();
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

    // Check for developer context.
    $rel = (($user_id = $this->getUserId()) && $rel === 'canonical') ? 'developer' : $rel;

    // Build the URL.
    $url = parent::toUrl($rel, $options);

    // Add the user if this is a developer URL.
    if ($rel == 'developer' &&  !empty($user_id)) {
      $url->setRouteParameter('user', $user_id);
    }

    return $url;
  }

  /**
   * Get the user ID from the current route match.
   *
   * @return string
   *   Returns the user ID.
   */
  private function getUserId(): ?string {
    // The route parameters still need to be set.
    $user = \Drupal::routeMatch()->getParameter('user');
    // Sometimes the param converter has converted the user to an entity.
    return $user instanceof UserInterface ? $user->id() : $user;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return array_merge(['url.developer'], parent::getCacheContexts());
  }

}
