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

use Drupal\apigee_edge\Entity\Controller\EdgeEntityControllerInterface;
use Drupal\apigee_edge\Entity\Storage\EdgeEntityStorageBase;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryAwareTrait;
use Drupal\apigee_m10n\Entity\Storage\Controller\ProductBundleEntityControllerProxy;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The storage controller for the `product_bundle` entity.
 */
class ProductBundleStorage extends EdgeEntityStorageBase implements ProductBundleStorageInterface {

  /**
   * The controller proxy.
   *
   * @var \Drupal\apigee_edge\Entity\Controller\EdgeEntityControllerInterface
   */
  protected $controller_proxy;

  /**
   * Static cache for all product bundles.
   *
   * @var array
   */
  protected $all_product_bundles;

  /**
   * Static cache for product bundles by developer.
   *
   * @var array
   */
  protected $product_bundles_by_developer = [];

  /**
   * Constructs an RatePlanStorage instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to be used.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache.
   * @param \Drupal\Component\Datetime\TimeInterface $system_time
   *   The system time.
   * @param \Drupal\apigee_edge\Entity\Controller\EdgeEntityControllerInterface $controller_proxy
   *   The controller proxy.
   */
  public function __construct(EntityTypeInterface $entity_type, CacheBackendInterface $cache_backend, MemoryCacheInterface $memory_cache, TimeInterface $system_time, EdgeEntityControllerInterface $controller_proxy) {
    parent::__construct($entity_type, $cache_backend, $memory_cache, $system_time);

    $this->controller_proxy = $controller_proxy;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('cache.apigee_edge_entity'),
      $container->get('entity.memory_cache'),
      $container->get('datetime.time'),
      $container->get('apigee_m10n.sdk_controller_proxy.product_bundle')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function entityController(): EdgeEntityControllerInterface {
    return $this->controller_proxy;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableProductBundlesByDeveloper($developer_id) {
    $entities = [];

    // Check for a cached list.
    if (isset($this->product_bundles_by_developer[$developer_id])) {
      return $this->loadMultiple($this->product_bundles_by_developer[$developer_id]);
    }

    $this->withController(function (EdgeEntityControllerInterface $controller) use ($developer_id, &$entities) {
      // Get product bundles from the PHP API client.
      $sdk_product_bundles = $controller->getAvailableProductBundlesByDeveloper($developer_id, TRUE, TRUE);

      // Returned entities are SDK entities and not Drupal entities,
      // what if the id is used in Drupal is different than what
      // SDK uses? (ex.: developer)
      foreach ($sdk_product_bundles as $id => $entity) {
        $entities[$id] = $this->createNewInstance($entity);
      }
      $this->invokeStorageLoadHook($entities);
      $this->setPersistentCache($entities);

      // TODO: Consider caching this list in the DB.
      // Set static cache.
      $this->product_bundles_by_developer[$developer_id] = array_map(function ($entity) {
        return $entity->id();
      }, $entities);
    });

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function loadAll() {
    $entities = [];

    // Check for a cached list.
    if (isset($this->all_product_bundles)) {
      return $this->loadMultiple($this->all_product_bundles);
    }

    $this->withController(function (EdgeEntityControllerInterface $controller) use (&$entities) {
      // Get all product bundles from the PHP API client.
      $sdk_product_bundles = $controller->getEntities();

      // Returned entities are SDK entities and not Drupal entities,
      // what if the id is used in Drupal is different than what
      // SDK uses? (ex.: developer)
      foreach ($sdk_product_bundles as $id => $entity) {
        $entities[$id] = $this->createNewInstance($entity);
      }
      $this->invokeStorageLoadHook($entities);
      $this->setPersistentCache($entities);

      // TODO: Consider caching this list in the DB.
      // Set static cache.
      $this->all_product_bundles = array_map(function ($entity) {
        return $entity->id();
      }, $entities);
    });

    return $entities;
  }

}
