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
use Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlanInterface;
use Apigee\Edge\Api\Monetization\Entity\StandardRatePlan;
use Drupal\apigee_edge\Entity\EntityConvertAwareTrait;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\apigee_m10n\Entity\Subscription;
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

  /**
   * @param string $developer_email
   *
   * @return array
   */
  public function loadSubscriptionsByDeveloperEmail(string $developer_email): array {
    // TODO: Implement loadSubscriptionsByDeveloperEmail() method.
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
    throw new \Exception('Not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function getController(string $developer_email): AcceptedRatePlanControllerInterface {
    // Cache controllers per developer.
    static $controllers = [];

    if (!isset($controllers[$developer_email])) {
      $controllers[$developer_email] = $this->controllerFactory()->developerAcceptedRatePlanController($developer_email);
    }

    return $controllers[$developer_email];
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
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface $drupal_entity
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlanInterface
   */
  protected function convertToSdkEntity(RatePlanInterface $drupal_entity): AcceptedRatePlanInterface {
    return EntityConvertAwareTrait::convertToSdkEntity($drupal_entity, AcceptedRatePlanInterface::class);
  }

  /**
   * Converts a SDK entity to a drupal entity.
   *
   * @param \Apigee\Edge\Api\Monetization\Entity\StandardRatePlan $sdk_entity
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\AcceptedRatePlanInterface
   */
  protected function convertToDrupalEntity(StandardRatePlan $sdk_entity): AcceptedRatePlanInterface {
    return EntityConvertAwareTrait::convertToDrupalEntity($sdk_entity, Subscription::class);
  }

}
