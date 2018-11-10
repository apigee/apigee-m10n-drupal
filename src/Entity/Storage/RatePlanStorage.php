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

namespace Drupal\apigee_m10n\Entity\Storage;

use Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface;
use Apigee\Edge\Api\Monetization\Entity\StandardRatePlan;
use Apigee\Edge\Api\Monetization\Entity\RatePlanInterface as ApigeeRatePlanInterface;
use Drupal\apigee_edge\Entity\EntityConvertAwareTrait;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;
use Drupal\apigee_m10n\Entity\RatePlan;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The storage controller for the `rate_plan` entity.
 */
class RatePlanStorage extends FieldableMonetizationEntityStorageBase implements RatePlanStorageInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs an DeveloperAppStorage instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger to be used.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Configuration factory.
   * @param \Drupal\Component\Datetime\TimeInterface $system_time
   *   System time.
   */
  public function __construct(EntityTypeInterface $entity_type, CacheBackendInterface $cache, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config, TimeInterface $system_time) {
    parent::__construct($entity_type, $cache, $logger, $system_time);
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheExpiration = $config->get('apigee_edge.common_app_settings')->get('cache_expiration');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container->get('logger.channel.apigee_m10n');
    return new static(
      $entity_type,
      $container->get('cache.apigee_edge_entity'),
      $logger,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadPackageRatePlans(string $package_id): array {
    // Load all public rate plans.
    $sdk_entities = $this->getController($package_id)
      ->getEntities(TRUE, FALSE);

    // Convert the SDK entities entity to drupal entities.
    $entities = array_map([$this, 'convertToDrupalEntity'], $sdk_entities);

    $this->setPersistentCache($entities);

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function loadById(string $package_name, string $id): RatePlanInterface {
    // Load, convert and return the Package rate plan.
    return $this->convertToDrupalEntity($this->getController($package_name)->load($id));
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    foreach ($entities as $entity) {
      $controller = $this->getController($entity->getPackage()->id());
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $controller->delete($entity->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    if (!($package = $entity->getPackage()) || !($package_id = $package->id())) {
      throw new \InvalidArgumentException('A package is required to save a rate plan.');
    }

    $controller = $this->getController($package_id);

    // Convert Drupal entity back to an SDK entity and with that:
    // - prevent sending additional Drupal-only properties to Apigee Edge
    // - prevent serialization/normalization errors
    //   (CircularReferenceException) caused by TypedData objects on Drupal
    //   entities.
    $sdkEntity = $this->convertToSdkEntity($entity);
    if ($entity->isNew()) {
      $controller->create($sdkEntity);
      $this->applyChanges($sdkEntity, $entity);
      $result = SAVED_NEW;
    }
    else {
      $controller->update($sdkEntity);
      $this->applyChanges($sdkEntity, $entity);
      $result = SAVED_UPDATED;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getController(string $api_package_id): RatePlanControllerInterface {
    // Cache controllers per package.
    static $controllers = [];

    if (!isset($controllers[$api_package_id])) {
      $controllers[$api_package_id] = $this->controllerFactory()->packageRatePlanController($api_package_id);
    }

    return $controllers[$api_package_id];
  }

  /**
   * {@inheritdoc}
   */
  public function controllerFactory(): ApigeeSdkControllerFactoryInterface {
    return \Drupal::service('apigee_m10n.sdk_controller_factory');
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated Use `::loadById` instead.
   */
  public function load($id) {
    // @todo discuss if we should even be parsing `package::ID`.
    if ((list($package_id, $reate_plan_id) = explode('::', $id)) && !empty($package_id) && !empty($reate_plan_id)) {
      return $this->loadById($package_id, $reate_plan_id);
    }
    else {
      throw new \InvalidArgumentException('A package id is required to load a package rate plan. Use ');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    throw new EntityStorageException('Unable to load rate plans directly. Use `::loadPackageRatePlans`.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFromStorage(array $ids = NULL) {
    throw new EntityStorageException('Unable to load rate plans directly. Use a package specific or developer loader.');
  }

  /**
   * Converts a drupal entity to it's SDK entity.
   *
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface $drupal_entity
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface
   */
  public function convertToSdkEntity(RatePlanInterface $drupal_entity): ApigeeRatePlanInterface {
    return EntityConvertAwareTrait::convertToSdkEntity($drupal_entity, StandardRatePlan::class);
  }

  /**
   * Converts a SDK entity to a drupal entity.
   *
   * @param \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface $sdk_entity
   *   The SDK entity as it would  be loaded from the SDK entity controller.
   *
   * @return \Drupal\apigee_m10n\Entity\RatePlanInterface
   *   A drupal rate plan entity.
   */
  protected function convertToDrupalEntity(ApigeeRatePlanInterface $sdk_entity): RatePlanInterface {
    return EntityConvertAwareTrait::convertToDrupalEntity($sdk_entity, RatePlan::class);
  }

}
