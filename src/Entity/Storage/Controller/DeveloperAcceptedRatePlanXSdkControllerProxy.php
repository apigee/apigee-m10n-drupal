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

namespace Drupal\apigee_m10n\Entity\Storage\Controller;

use Apigee\Edge\Entity\EntityInterface;
use Drupal\apigee_edge\Exception\RuntimeException;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryAwareTrait;

/**
 * The `apigee_m10n.sdk_controller_proxy.rate_plan` service class.
 *
 * Responsible for proxying calls to the appropriate rate plan controllers. Rate
 * plan controllers require a product bundle ID for instantiation so we
 * sometimes need to get a controller at runtime for a given rate plan.
 */
class DeveloperAcceptedRatePlanXSdkControllerProxy implements DeveloperAcceptedRatePlanXSdkControllerProxyInterface {

  use ApigeeSdkControllerFactoryAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function create(EntityInterface $entity): void {
    $this->doCreate($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function doCreate(EntityInterface $entity): void {

    $this->getPurchasedPlanController($entity)
      ->acceptRatePlan(
        $entity->getRatePlan()
      );
  }

  /**
   * {@inheritdoc}
   */
  public function load(string $id): EntityInterface {
    // A little secret is that the real product bundle ID is not required for
    // loading a rate plan but may be required for saving it.
    return $this->loadById('default', $id);
  }

  /**
   * {@inheritdoc}
   */
  public function update(EntityInterface $entity): void {
    $this->getPurchasedProductControllerByDeveloperId($entity->getDeveloperEmail())->updateSubscription($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $id): void {
    throw new RuntimeException('Unable to delete purchase. Update the end date to cancel.');
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

    /** @var \Apigee\Edge\Api\Apigee\Entity\DeveloperAcceptedRatePlanInterface[] $purchased_products */
    $purchased_products = [];

    // Loops through all developer emails to get their purchased products.
    foreach ($select->execute()->fetchCol() as $developer_email) {
      // Get all purchases for this developer.
      $developer_purchases = $this->loadByDeveloperId($developer_email);
      foreach ($developer_purchases as $purchased_product) {
        // Purchases are keyed by their ID.
        $purchased_products[$purchased_product->id()] = $purchased_product;
      }
    }

    return $purchased_products;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByDeveloperId(string $developer_id): array {
    // Get all purchases for this developer.
    // TODO: Cache purchased_product lists per developer.
    return $this->getPurchasedProductControllerByDeveloperId($developer_id)
      ->getAllAcceptedRatePlans();
  }

  /**
   * {@inheritdoc}
   */
  public function loadById(string $developer_id, string $id): ?EntityInterface {
    return $this->getPurchasedProductControllerByDeveloperId($developer_id)->load($id);
  }

  /**
   * Given an entity, gets the purchased_plan controller.
   *
   * @param \Apigee\Edge\Entity\EntityInterface $entity
   *   The ID of the product bundle the rate plan belongs to.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\AcceptedRatePlanControllerInterface
   *   The real rate plan controller.
   */
  protected function getPurchasedPlanController(EntityInterface $entity) {
    /** @var \Apigee\Edge\Api\Monetization\Entity\DeveloperAcceptedRatePlanInterface $entity */
    if (!($developer = $entity->getDeveloper())) {
      // If the developer ID is not set, we have no way to get the controller
      // since it depends on the developer id or email.
      throw new RuntimeException('The Developer must be set to create a purchased plan controller.');
    }
    // Get the controller.
    return $this->getPurchasedProductControllerByDeveloperId($developer->getEmail());
  }

  /**
   * Gets the purchased_product controller by developer ID.
   *
   * @param string $developer_id
   *   The developer ID or email who has accepted the rate plan.
   *
   * @return \Apigee\Edge\Api\ApigeeX\Controller\AcceptedRatePlanControllerInterface
   *   The purchased_product controller.
   */
  protected function getPurchasedProductControllerByDeveloperId($developer_id) {
    // Cache the controllers here for privacy.
    static $controller_cache = [];

    // Make sure a controller is cached.

    $controller_cache[$developer_id] = $controller_cache[$developer_id]
      ?? $this->controllerFactory()->developerAcceptedRatePlanxController($developer_id);

    return $controller_cache[$developer_id];
  }

}
