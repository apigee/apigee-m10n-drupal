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

/**
 * The `apigee_m10n.sdk_controller_proxy.rate_plan` service class.
 *
 * Responsible for proxying calls to the appropriate package rate plan
 * controllers. Rate plan controllers require a package ID for instantiation so
 * we sometimes need to get a controller at runtime for a given rate plan.
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
    // A little secret is that the real package ID is not required for loading
    // a rate plan.
    return $this->getRatePlanControllerByPackageId('default')->load($id);
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
    $this->getRatePlanControllerByPackageId('default')->delete($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadAll(): array {
    // Cache the package controller.
    static $package_controller;
    $package_controller = $package_controller
      ?: $this->controllerFactory()->apiPackageController();

    // TODO: Replace this with entity load once packages become drupal entities.
    $all_packages = $package_controller->getEntities();

    /** @var \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface[] $rate_plans */
    $rate_plans = [];

    // Loops through all packages to get the package plans.
    foreach ($all_packages as $package) {
      // Get all plans for this package.
      $plans = $this->loadPackageRatePlans($package->id());
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
  public function loadPackageRatePlans($package_id): array {
    // Get all plans for this package.
    return $this->getRatePlanControllerByPackageId($package_id)
      ->getEntities(TRUE, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function loadById(string $package_id, string $id): EntityInterface {
    return $this->getRatePlanControllerByPackageId($package_id)->load($id);
  }

  /**
   * Given an entity, gets the rate plan controller.
   *
   * @param \Apigee\Edge\Entity\EntityInterface $entity
   *   The ID of the package the rate plan belongs to.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface
   *   The real rate plan controller.
   */
  protected function getRatePlanController(EntityInterface $entity) {
    /** @var \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface $entity */
    if (!($package = $entity->getPackage())) {
      // If the package is not set, we have no way to get the controller since
      // it depends on the package ID.
      throw new RuntimeException('The API package must be set to create a rate plan.');
    }
    // Get the controller.
    return $this->getRatePlanControllerByPackageId($package->id());
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
  protected function getRatePlanControllerByPackageId($package_id) {
    // Cache the controllers here for privacy.
    static $controller_cache = [];
    // Make sure a controller is cached.
    $controller_cache[$package_id] = $controller_cache[$package_id]
      ?? $this->controllerFactory()->ratePlanController($package_id);

    return $controller_cache[$package_id];
  }

}
