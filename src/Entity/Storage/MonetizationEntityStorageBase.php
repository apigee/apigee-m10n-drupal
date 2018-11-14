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

use Apigee\Edge\Api\Monetization\Entity\EntityInterface as MonetizationEntityInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base class for monetization entity storage.
 */
abstract class MonetizationEntityStorageBase extends EntityStorageBase {

  /**
   * Initial status for saving a item to Apigee Edge.
   *
   * Similar to SAVED_NEW and SAVED_UPDATED. If this is returned then
   * something probably went wrong.
   */
  public const SAVED_UNKNOWN = 0;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The SDK Connector service.
   *
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  protected $sdkConnector;

  /**
   * Number of seconds until an entity can be served from cache.
   *
   * -1 is also an allowed, which means the item should never be removed unless
   * explicitly deleted.
   *
   * @var int
   */
  protected $cacheExpiration = CacheBackendInterface::CACHE_PERMANENT;

  /**
   * The system time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $systemTime;

  /**
   * Constructs an EdgeEntityStorageBase instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger to be used.
   * @param \Drupal\Component\Datetime\TimeInterface $system_time
   *   The system time.
   */
  public function __construct(EntityTypeInterface $entity_type, CacheBackendInterface $cache, LoggerInterface $logger, TimeInterface $system_time) {
    parent::__construct($entity_type);
    $this->cacheBackend = $cache;
    $this->logger = $logger;
    $this->systemTime = $system_time;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container->get('logger.channel.apigee_edge');
    return new static(
      $entity_type,
      $container->get('cache.apigee_edge_entity'),
      $logger,
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    // Attempt to load entities from the persistent cache. This will remove IDs
    // that were loaded from $ids.
    $entities_from_cache = $this->getFromPersistentCache($ids);
    return $entities_from_cache + $this->getFromStorage($ids);
  }

  /**
   * Invokes hook_entity_storage_load().
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   List of entities, keyed on the entity ID.
   */
  protected function invokeStorageLoadHook(array &$entities) {
    if (!empty($entities)) {
      // Call hook_entity_storage_load().
      foreach ($this->moduleHandler()->getImplementations('entity_storage_load') as $module) {
        $function = $module . '_entity_storage_load';
        $function($entities, $this->entityTypeId);
      }
      // Call hook_TYPE_storage_load().
      foreach ($this->moduleHandler()->getImplementations($this->entityTypeId . '_storage_load') as $module) {
        $function = $module . '_' . $this->entityTypeId . '_storage_load';
        $function($entities);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    return !$entity->isNew();
  }

  /**
   * Gets entities from the persistent cache backend.
   *
   * @param array|null &$ids
   *   If not empty, return entities that match these IDs. IDs that were found
   *   will be removed from the list.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Array of entities from the persistent cache.
   */
  protected function getFromPersistentCache(array &$ids = NULL) {
    if (!$this->entityType->isPersistentlyCacheable() || empty($ids)) {
      return [];
    }
    $entities = [];
    // Build the list of cache entries to retrieve.
    $cid_map = [];
    foreach ($ids as $id) {
      $cid_map[$id] = $this->buildCacheId($id);
    }
    $cids = array_values($cid_map);
    if ($cache = $this->cacheBackend->getMultiple($cids)) {
      // Get the entities that were found in the cache.
      foreach ($ids as $index => $id) {
        $cid = $cid_map[$id];
        if (isset($cache[$cid])) {
          $entities[$id] = $cache[$cid]->data;
          unset($ids[$index]);
        }
      }
    }
    return $entities;
  }

  /**
   * Gets entities from the storage.
   *
   * @param array|null $ids
   *   If not empty, return entities that match these IDs. Return all entities
   *   when NULL.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of entities from the storage.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  abstract protected function getFromStorage(array $ids = NULL);

  /**
   * Stores entities in the persistent cache backend.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Entities to store in the cache.
   */
  protected function setPersistentCache(array $entities) {
    if (!$this->entityType->isPersistentlyCacheable()) {
      return;
    }

    foreach ($entities as $id => $entity) {
      $this->cacheBackend->set($this->buildCacheId($id), $entity, $this->getPersistentCacheExpiration(), $this->getPersistentCacheTags($entity));
    }
  }

  /**
   * Number of seconds after a cache item expires.
   *
   * So our "persistent cache" implementation could be actually not a persistent
   * one but we kept using this naming convention by hoping that the persistent
   * caching features becomes decoupled from ContentEntityStorageBase and we
   * could build on the top of that solution with as less pain as possible.
   *
   * @return int
   *   Number of seconds after a cache item expires.
   */
  protected function getPersistentCacheExpiration() {
    if ($this->cacheExpiration !== CacheBackendInterface::CACHE_PERMANENT) {
      return $this->systemTime->getCurrentTime() + $this->cacheExpiration;
    }
    return $this->cacheExpiration;
  }

  /**
   * Generates cache tags for entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   *
   * @return array
   *   Array of cache tags.
   */
  protected function getPersistentCacheTags(EntityInterface $entity) {
    $cacheTags = [
      "{$this->entityTypeId}",
      "{$this->entityTypeId}:values",
      "{$this->entityTypeId}:{$entity->id()}",
      "{$this->entityTypeId}:{$entity->id()}:values",
    ];
    return $cacheTags;
  }

  /**
   * Builds the cache ID for the passed in entity ID.
   *
   * @param int $id
   *   Entity ID for which the cache ID should be built.
   *
   * @return string
   *   Cache ID that can be passed to the cache backend.
   */
  protected function buildCacheId($id) {
    return "values:{$this->entityTypeId}:{$id}";
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    if ($this->entityType->isStaticallyCacheable() && $ids) {
      $cids = [];
      foreach ($ids as $id) {
        $cid = $this->buildCacheId($id);
        $cids[] = $cid;
        $this->memoryCache->delete($cid);
      }
      if ($this->entityType->isPersistentlyCacheable()) {
        $this->cacheBackend->deleteMultiple($cids);
      }
    }
    else {
      $this->memoryCache->invalidateTags([$this->memoryCacheTag]);
      $this->entities = [];
      if ($this->entityType->isPersistentlyCacheable()) {
        Cache::invalidateTags([$this->entityTypeId . ':values']);
      }
    }
  }

  /**
   * Copies all properties to $destination from $source.
   *
   * @param \Apigee\Edge\Api\Monetization\Entity\EntityInterface $source
   *   The source SDK entity object.
   * @param \Drupal\Core\Entity\EntityInterface $destination
   *   The destination Drupal entity object.
   *
   * @throws \ReflectionException
   */
  protected function applyChanges(MonetizationEntityInterface $source, EntityInterface $destination) {
    $roDst = new \ReflectionObject($destination);
    $roSrc = new \ReflectionObject($source);
    foreach ($roDst->getProperties() as $property) {
      $setter = 'set' . ucfirst($property->getName());
      if ($roDst->hasMethod($setter)) {
        $rm = new \ReflectionMethod($destination, $setter);
        $value = NULL;
        $getter = 'get' . ucfirst($property->getName());
        $isser = 'is' . ucfirst($property->getName());
        if ($roSrc->hasMethod($getter)) {
          $value = $source->{$getter}();
        }
        elseif ($roSrc->hasMethod($isser)) {
          $value = $source->{$isser}();
        }

        // Ignore problematic null values.
        if ($value !== NULL) {
          $rm->invoke($destination, $value);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'entity.query.edge';
  }

  /**
   * {@inheritdoc}
   */
  public function loadRevision($revision_id) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision($revision_id) {
    return NULL;
  }

}
