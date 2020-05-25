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
use Drupal\apigee_m10n\Entity\Storage\Controller\DeveloperAcceptedRatePlanSdkControllerProxyInterface;
use Drupal\apigee_m10n\Entity\PurchasedPlanInterface;
use Drupal\apigee_m10n\Exception\UnexpectedValueException;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The storage controller for the `purchased_plan` entity.
 */
class PurchasedPlanStorage extends EdgeEntityStorageBase implements PurchasedPlanStorageInterface {

  /**
   * The controller proxy.
   *
   * The purchased_plan controller typically requires a developer ID in the
   * constructor so we use a proxy that can handle instantiating controllers as
   * needed.
   *
   * @var \Drupal\apigee_edge\Entity\Controller\EdgeEntityControllerInterface
   */
  protected $controller_proxy;

  /**
   * Constructs an PurchasedPlanStorage instance.
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
      $container->get('apigee_m10n.sdk_controller_proxy.purchased_plan')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function loadByDeveloperId(string $developer_id): array {
    $entities = [];

    $this->withController(function (DeveloperAcceptedRatePlanSdkControllerProxyInterface $controller) use ($developer_id, &$entities) {
      // Load the purchases for this developer.
      $sdk_entities = $controller->loadByDeveloperId($developer_id);
      // Convert the SDK entities to drupal entities.
      foreach ($sdk_entities as $id => $entity) {
        $drupal_entity = $this->createNewInstance($entity);
        $entities[$drupal_entity->id()] = $drupal_entity;
      }
      $this->invokeStorageLoadHook($entities);
      $this->setPersistentCache($entities);
    });

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    parent::doPostSave($entity, $update);

    // Rebuild the purchased plans cache on create or update.
    if ($developer = $entity->getDeveloper()) {
      \Drupal::cache()->delete("apigee_m10n:dev:purchased_plans:{$developer->getEmail()}");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    $result = static::SAVED_UNKNOWN;
    $this->withController(function (DeveloperAcceptedRatePlanSdkControllerProxyInterface $controller) use ($id, $entity, &$result) {
      /** @var \Drupal\apigee_m10n\Entity\PurchasedPlanInterface $entity */
      if ($entity->isNew()) {
        $controller->doCreate($entity->decorated(), $entity->getSuppressWarning());
        $result = SAVED_NEW;
      }
      else {
        $controller->update($entity->decorated());
        $result = SAVED_UPDATED;
      }
    });
    return $result;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function loadById(string $developer_id, string $id): ?PurchasedPlanInterface {
    // Load from cache.
    $ids = [$id];
    $purchased_plans = $this->getFromPersistentCache($ids);
    // Return the cached entity.
    if (isset($purchased_plans[$id])) {
      return $purchased_plans[$id];
    }

    $entity = NULL;
    $this->withController(function (DeveloperAcceptedRatePlanSdkControllerProxyInterface $controller) use ($developer_id, $id, &$entity) {
      $drupal_entity = ($sdk_entity = $controller->loadById($developer_id, $id))
        ? $this->createNewInstance($sdk_entity)
        : FALSE;

      // Make sure the result is for the requested developer.
      if ($developer_id !== $drupal_entity->getDeveloper()->getEmail()) {
        throw new UnexpectedValueException($drupal_entity->decorated(), 'develoepr', $developer_id, $drupal_entity->getDeveloper()->getEmail());
      }

      if ($drupal_entity) {
        $entities = [$drupal_entity->id() => $drupal_entity];
        $this->invokeStorageLoadHook($entities);
        $this->setPersistentCache($entities);

        $entity = $drupal_entity;
      }
    });

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function entityController(): EdgeEntityControllerInterface {
    return $this->controller_proxy;
  }

}
