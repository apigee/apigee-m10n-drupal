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
use Drupal\apigee_m10n\Entity\Storage\Controller\ApiPackageEntityControllerProxy;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The storage controller for the `package` entity.
 */
class PackageStorage extends EdgeEntityStorageBase implements PackageStorageInterface {

  /**
   * The controller proxy.
   *
   * @var \Drupal\apigee_edge\Entity\Controller\EdgeEntityControllerInterface
   */
  protected $controller_proxy;

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
      $container->get('apigee_m10n.sdk_controller_proxy.package')
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
  public function getAvailableApiPackagesByDeveloper($developer_id) {
    $entities = [];

    $this->withController(function (EdgeEntityControllerInterface $controller) use ($developer_id, &$entities) {
      $sdk_packages = $controller->getAvailableApiPackagesByDeveloper($developer_id, TRUE, TRUE);

      // Returned entities are SDK entities and not Drupal entities,
      // what if the id is used in Drupal is different than what
      // SDK uses? (ex.: developer)
      foreach ($sdk_packages as $id => $entity) {
        $entities[$id] = $this->createNewInstance($entity);
      }
      $this->invokeStorageLoadHook($entities);
      $this->setPersistentCache($entities);
    });

    return $entities;
  }

}
