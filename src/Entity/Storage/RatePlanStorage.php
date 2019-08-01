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

use Apigee\Edge\Api\Monetization\Entity\RatePlanRevisionInterface;
use Drupal\apigee_edge\Entity\Controller\EdgeEntityControllerInterface;
use Drupal\apigee_edge\Entity\Storage\EdgeEntityStorageBase;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\apigee_m10n\Entity\Storage\Controller\RatePlanSdkControllerProxyInterface;
use Drupal\apigee_m10n\Exception\InvalidRatePlanIdException;
use Drupal\apigee_m10n\Exception\SdkEntityLoadException;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The storage controller for the `rate_plan` entity.
 */
class RatePlanStorage extends EdgeEntityStorageBase implements RatePlanStorageInterface {

  /**
   * The controller proxy.
   *
   * The rate plan controller requires a product bundle ID in the constructor
   * so we use a proxy that can handle instantiating controllers as needed.
   *
   * @var \Drupal\apigee_edge\Entity\Controller\EdgeEntityControllerInterface
   */
  protected $controller_proxy;

  /**
   * Static cache for future rate plans.
   *
   * Loading future rate plans for a given rate plan is expensive as it
   * retrieves all rate plans for the current plan's product bundle and loops
   * through them to determine which one is a future revision. For that reason,
   * we cache the results here.
   *
   * @var array
   */
  protected $future_rate_plans = [];

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
      $container->get('apigee_m10n.sdk_controller_proxy.rate_plan')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function loadRatePlansByProductBundle(string $product_bundle_id, $include_future_plans = FALSE): array {
    $entities = [];

    $this->withController(function (RatePlanSdkControllerProxyInterface $controller) use ($product_bundle_id, $include_future_plans, &$entities) {
      // Load the  rate plans for this product bundle.
      $sdk_entities = $controller->loadRatePlansByProductBundle($product_bundle_id, $include_future_plans);
      // Convert the SDK entities to drupal entities.
      /** @var \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface $entity */
      foreach ($sdk_entities as $id => $entity) {
        try {
          // Filter out rate plans with invalid Ids.
          if ($this->isValidId($entity->id())) {
            $drupal_entity = $this->createNewInstance($entity);
            $entities[$drupal_entity->id()] = $drupal_entity;
          }
        }
        catch (InvalidRatePlanIdException $exception) {
          watchdog_exception('apigee_m10n', $exception);
        }
      }
      $this->invokeStorageLoadHook($entities);
      $this->setPersistentCache($entities);
    });

    return $entities;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function loadById(string $product_bundle_id, string $id): RatePlanInterface {
    // Load from cache.
    $ids = [$id];
    $rate_plans = $this->getFromPersistentCache($ids);
    // Return the cached entity.
    if (isset($rate_plans[$id])) {
      return $rate_plans[$id];
    }

    $entity = NULL;
    $this->withController(function (RatePlanSdkControllerProxyInterface $controller) use ($product_bundle_id, $id, &$entity) {
      $drupal_entity = ($sdk_entity = $controller->loadById($product_bundle_id, $id))
        ? $this->createNewInstance($sdk_entity)
        : FALSE;

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
  public function loadFutureRatePlan(RatePlanInterface $ratePlan): ?RatePlanInterface {
    if (!isset($this->future_rate_plans[$ratePlan->id()])) {
      // Future plans haven't started as of today.
      $today = new \DateTimeImmutable('today', $ratePlan->getStartDate()->getTimezone());
      // Load all plans for this product bundle.
      $all_plans = $this->loadRatePlansByProductBundle($ratePlan->getProductBundleId(), TRUE);
      // Loop through to see if any future previous revisions are the given
      // plan.
      foreach ($all_plans as $future_candidate) {
        if ($future_candidate->getStartDate() > $today
          && ($decorated = $future_candidate->decorated())
          && $decorated instanceof RatePlanRevisionInterface
          && $decorated->getPreviousRatePlanRevision()->id() === $ratePlan->id()
        ) {
          $future_rate_plan = $future_candidate;
          continue;
        }
      }
      // Set the static cache value to FALSE if no future plan has been found.
      $this->future_rate_plans[$ratePlan->id()] = $future_rate_plan ?? FALSE;
    }

    // Returns NULL if the cached value is false.
    return ($plan = $this->future_rate_plans[$ratePlan->id()]) ? $plan : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidId(string $id): bool {
    if (preg_match('/[\/]+/', $id)) {
      throw new InvalidRatePlanIdException(sprintf('Invalid rate plan Id "%s"', $id));
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function entityController(): EdgeEntityControllerInterface {
    return $this->controller_proxy;
  }

}
