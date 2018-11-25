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

use Apigee\Edge\Entity\EntityInterface;
use Drupal\apigee_edge\Exception\RuntimeException;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryAwareTrait;
use Drupal\user\Entity\User;

/**
 * The `apigee_m10n.sdk_controller_proxy.rate_plan` service class.
 *
 * Responsible for proxying calls to the appropriate package rate plan
 * controllers. Rate plan controllers require a package ID for instantiation so
 * we sometimes need to get a controller at runtime for a given rate plan.
 */
class DeveloperAcceptedRatePlanSdkControllerProxy implements DeveloperAcceptedRatePlanSdkControllerProxyInterface {

  use ApigeeSdkControllerFactoryAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function create(EntityInterface $entity): void {
    $this->getSubscriptionController($entity)->create($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load(string $id): EntityInterface {
    // A little secret is that the real package ID is not required for loading
    // a rate plan.
    return $this->getSubscriptionControllerByDeveloperId('default')->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function update(EntityInterface $entity): void {
    $this->getSubscriptionController($entity)->update($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $id): void {
    $this->getSubscriptionControllerByDeveloperId('default')->delete($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadAll(): array {
    // Get all active user emails.
    $select = \Drupal::database()->select('users_field_data', 'u')
      ->fields('u', ['mail']);
    $select->condition('status', 1);
    $select->condition('uid', [0, 1], 'NOT IN');

    /** @var \Apigee\Edge\Api\Monetization\Entity\DeveloperAcceptedRatePlanInterface[] $subscriptions */
    $subscriptions = [];

    // Loops through all developer emails to get their subscriptions.
    foreach ($select->execute()->fetchCol() as $developer_email) {
      // Get all subscriptions for this developer.
      $developer_subscriptions = $this->loadByDeveloperId($developer_email);
      foreach ($developer_subscriptions as $developer_subscription) {
        // Subscriptions are keyed by their ID.
        $subscriptions[$developer_subscription->id()] = $developer_subscription;
      }
    }

    return $subscriptions;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByDeveloperId(string $developer_id): array {
    // Get all plans for this package.
    return $this->getSubscriptionControllerByDeveloperId($developer_id)
      ->getEntities(TRUE, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function loadById(string $developer_id, string $id): EntityInterface {
    return $this->getSubscriptionControllerByDeveloperId($developer_id)->load($id);
  }

  /**
   * Given an entity, gets the subscription controller.
   *
   * @param \Apigee\Edge\Entity\EntityInterface $entity
   *   The ID of the package the rate plan belongs to.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\AcceptedRatePlanControllerInterface
   *   The real rate plan controller.
   */
  protected function getSubscriptionController(EntityInterface $entity) {
    /** @var \Apigee\Edge\Api\Monetization\Entity\DeveloperAcceptedRatePlanInterface $entity */
    if (!($developer = $entity->getDeveloper())) {
      // If the developer ID is not set, we have no way to get the controller
      // since it depends on the developer id or email.
      throw new RuntimeException('The Developer must be set to create a subscription controller.');
    }
    // Get the controller.
    return $this->getSubscriptionControllerByDeveloperId($developer->getEmail());
  }

  /**
   * Gets the rate plan controller by package ID.
   *
   * @param string $package_id
   *   The ID of the package the rate plan belongs to.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface
   *   The real rate plan controller.
   */
  protected function getSubscriptionControllerByDeveloperId($package_id) {
    // Cache the controllers here for privacy.
    static $controller_cache = [];
    // Make sure a controller is cached.
    $controller_cache[$package_id] = $controller_cache[$package_id]
      ?? $this->controllerFactory()->packageRatePlanController($package_id);

    return $controller_cache[$package_id];
  }

}
