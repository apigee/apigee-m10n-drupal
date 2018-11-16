<?php

/**
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

use Apigee\Edge\Api\Monetization\Controller\AcceptedRatePlanControllerInterface;
use Apigee\Edge\Api\Monetization\Controller\DeveloperAcceptedRatePlanController;
use Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlan;
use Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlanInterface;
use Apigee\Edge\Api\Monetization\Entity\CompanyAcceptedRatePlan;
use Apigee\Edge\Api\Monetization\Entity\CompanyAcceptedRatePlanInterface;
use Apigee\Edge\Api\Monetization\Entity\DeveloperAcceptedRatePlan;
use Apigee\Edge\Api\Monetization\Entity\DeveloperAcceptedRatePlanInterface;
use Apigee\Edge\Api\Monetization\Entity\StandardRatePlan;
use Drupal\apigee_edge\Entity\EntityConvertAwareTrait;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\apigee_m10n\Entity\Subscription;
use Drupal\apigee_m10n\Entity\SubscriptionInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The storage controller for the `rate_plan` entity.
 */
class SubscriptionStorage extends FieldableMonetizationEntityStorageBase implements SubscriptionStorageInterface {

  /**
   * Constructs an SubscriptStorage instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger to be used.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Configuration factory.
   * @param \Drupal\Component\Datetime\TimeInterface $system_time
   *   System time.
   */
  public function __construct(EntityTypeInterface $entity_type, CacheBackendInterface $cache, LoggerInterface $logger, ConfigFactoryInterface $config, TimeInterface $system_time) {
    parent::__construct($entity_type, $cache, $logger, $system_time);
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
      $container->get('config.factory'),
      $container->get('datetime.time')
    );
  }

  public function loadById(string $developer_id, string $id): SubscriptionInterface {
    // Load, convert and return the subscription.
    return $this->convertToDrupalEntity($this->getController($developer_id)->load($id));
  }

  /**
   * {@inheritdoc}
   */
  public function loadByDeveloperId(string $developer_id): array {
    $sdk_entities =  $this->getController($developer_id)->getAllAcceptedRatePlans();

    // Convert
    $entities = array_map(function($sdk_entity) {
      return EntityConvertAwareTrait::convertToDrupalEntity($sdk_entity, Subscription::class);
    }, $sdk_entities);

    $this->setPersistentCache($entities);

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    $result = static::SAVED_UNKNOWN;

    // Convert Drupal entity back to an SDK entity and with that:
    // - prevent sending additional Drupal-only properties to Apigee Edge
    // - prevent serialization/normalization errors
    //   (CircularReferenceException) caused by TypedData objects on Drupal
    //   entities.
    $controller = $this->getController($entity->developerId);
    $rate_plan_storage = \Drupal::entityTypeManager('rate_plan')->getStorage('rate_plan');
    $rate_plan = $rate_plan_storage->convertToSdkEntity($entity->getRatePlan());

    if ($entity->isNew()) {
      // @TODO double check Subscription entity properties against the parameters accepted by `acceptRatePlan`...
      // For instance, is `nextCycleStartDate` only manageable via edge UI?
      // @see \Drupal\apigee_m10n\Entity\Subscription::propertyToFieldStaticMap
      $controller->acceptRatePlan($rate_plan, $entity->getStartDate(), $entity->getEndDate(), $entity->getQuotaTarget());
      $result = SAVED_NEW;
    }
    else {
      // @TODO make sure this works with Company Accepted rate plans as well.
      $controller->updateSubscription($this->convertDeveloperAcceptedRatePlanToSdkEntity($entity));
      $result = SAVED_UPDATED;
    }

    return $result;
  }

  /**
   * Had to override doPreSave because it tries to call self::loadMultiple which doesn't make sense without
   * a developer ID for context (since edge api provides no way of loading accepted rateplans w/o developer ID
   * even though they're using uuids so it should be possible).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The saved entity.
   *
   * @return int|string
   *   The processed entity identifier.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If the entity identifier is invalid.
   */
  protected function doPreSave(EntityInterface $entity) {
    $entity->original = $entity;

    return parent::doPreSave($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getController(string $developer_id): AcceptedRatePlanControllerInterface {
    // Cache controllers per developer.
    static $controllers = [];

    if (!isset($controllers[$developer_id])) {
      $controllers[$developer_id] = $this->controllerFactory()->developerAcceptedRatePlanController($developer_id);
    }

    /** @var \Apigee\Edge\Api\Monetization\Controller\DeveloperAcceptedRatePlanController */
    return $controllers[$developer_id];
  }

  /**
   * {@inheritdoc}
   */
  public function controllerFactory(): ApigeeSdkControllerFactoryInterface {
    // Use static caching.
    static $factory;

    if (!isset($factory)) {
      // Load the factory service.
      $factory = \Drupal::service('apigee_m10n.sdk_controller_factory');
    }

    return $factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    throw new EntityStorageException('Unable to load subscriptions directly. Use `::loadSubscriptionsByDeveloperEmail`.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFromStorage(array $ids = NULL) {
    throw new EntityStorageException('Unable to load subscriptions directly. Use `::loadSubscriptionsByDeveloperEmail`.');
  }

  /**
   * Converts a drupal entity to it's SDK entity.
   *
   * @param \Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlanInterface $drupal_entity
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlanInterface
   */
  protected function convertDeveloperAcceptedRatePlanToSdkEntity(AcceptedRatePlanInterface $drupal_entity): AcceptedRatePlanInterface {
    return EntityConvertAwareTrait::convertToSdkEntity($drupal_entity, DeveloperAcceptedRatePlan::class);
  }

  /**
   * Converts a drupal entity to it's SDK entity.
   *
   * @param \Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlanInterface $drupal_entity
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlanInterface
   */
  protected function convertCompanyAcceptedRatePlanToSdkEntity(AcceptedRatePlanInterface $drupal_entity): AcceptedRatePlanInterface {
    return EntityConvertAwareTrait::convertToSdkEntity($drupal_entity, CompanyAcceptedRatePlan::class);
  }

  /**
   * Converts a SDK entity to a drupal entity.
   *
   * @param \Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlanInterface $sdk_entity
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlanInterface
   */
  protected function convertToDrupalEntity(AcceptedRatePlanInterface $sdk_entity): AcceptedRatePlanInterface {
    return EntityConvertAwareTrait::convertToDrupalEntity($sdk_entity, Subscription::class);
  }

}
