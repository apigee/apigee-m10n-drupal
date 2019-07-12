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

namespace Drupal\apigee_m10n\Entity\Storage\Controller;

use Apigee\Edge\Entity\EntityInterface;
use Drupal\apigee_edge\Exception\RuntimeException;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryAwareTrait;
use Drupal\apigee_m10n\Entity\ProductBundle;

/**
 * The `apigee_m10n.sdk_controller_proxy.rate_plan` service class.
 *
 * Responsible for proxying calls to the appropriate rate plan controllers. Rate
 * plan controllers require a product bundle ID for instantiation so we
 * sometimes need to get a controller at runtime for a given rate plan.
 */
class RatePlanSdkControllerProxy implements RatePlanSdkControllerProxyInterface {

  use ApigeeSdkControllerFactoryAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function create(EntityInterface $entity): void {
    $this->getRatePlanController($entity)->create($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load(string $id): EntityInterface {
    // A little secret is that the real product bundle ID is not required for
    // loading a rate plan.
    return $this->getRatePlanControllerByProductBundleId('default')->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function update(EntityInterface $entity): void {
    $this->getRatePlanController($entity)->update($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $id): void {
    $this->getRatePlanControllerByProductBundleId('default')->delete($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadAll(): array {
    // TODO: Cache this list.
    $all_product_bundles = ProductBundle::loadAll();

    /** @var \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface[] $rate_plans */
    $rate_plans = [];

    // Loops through all product bundles to get the rate plans.
    foreach ($all_product_bundles as $product_bundle) {
      /** @var \Drupal\apigee_m10n\Entity\ProductBundleInterface $product_bundle */
      // Get all plans for this product bundle.
      $plans = $this->loadRatePlansByProductBundle($product_bundle->id());
      foreach ($plans as $plan) {
        // Key rate plans by their ID.
        $rate_plans[$plan->id()] = $plan;
      }
    }

    return $rate_plans;
  }

  /**
   * {@inheritdoc}
   */
  public function loadRatePlansByProductBundle($product_bundle_id, $include_future_plans = FALSE, $standard_only = FALSE): array {
    // Get all plans for this product bundle.
    return $this->getRatePlanControllerByProductBundleId($product_bundle_id)
      ->getEntities(!$include_future_plans, FALSE, $standard_only);
  }

  /**
   * {@inheritdoc}
   */
  public function loadById(string $product_bundle_id, string $id): EntityInterface {
    return $this->getRatePlanControllerByProductBundleId($product_bundle_id)->load($id);
  }

  /**
   * Given an entity, gets the rate plan controller.
   *
   * @param \Apigee\Edge\Entity\EntityInterface $entity
   *   The ID of the product bundle  the rate plan belongs to.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface
   *   The real rate plan controller.
   */
  protected function getRatePlanController(EntityInterface $entity) {
    /** @var \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface $entity */
    if (!($product_bundle = $entity->getPackage())) {
      // If the product bundle is not set, we have no way to get the controller
      // since it depends on the product bundle ID.
      throw new RuntimeException('The product bundle must be set to create a rate plan controller.');
    }
    // Get the controller.
    return $this->getRatePlanControllerByProductBundleId($product_bundle->id());
  }

  /**
   * Gets the rate plan controller by product bundle ID.
   *
   * @param string $product_bundle_id
   *   The ID of the product bundle the rate plan belongs to.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface
   *   The real rate plan controller.
   */
  protected function getRatePlanControllerByProductBundleId($product_bundle_id) {
    // Cache the controllers here for privacy.
    static $controller_cache = [];
    // Make sure a controller is cached.
    $controller_cache[$product_bundle_id] = $controller_cache[$product_bundle_id]
      ?? $this->controllerFactory()->ratePlanController($product_bundle_id);

    return $controller_cache[$product_bundle_id];
  }

}
