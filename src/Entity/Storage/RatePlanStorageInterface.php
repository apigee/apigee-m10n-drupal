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

use Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface;
use Apigee\Edge\Api\Monetization\Entity\StandardRatePlan;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;
use Drupal\apigee_m10n\Entity\RatePlan;
use Drupal\apigee_m10n\Entity\RatePlanInterface;

/**
 * Defines an interface for the rate plan storage class.
 */
interface RatePlanStorageInterface extends FieldableMonetizationEntityStorageInterface {

  public const DRUPAL_ENTITY_INTERFACE = RatePlan::class;
  public const SDK_ENTITY_INTERFACE = StandardRatePlan::class;

  /**
   * Loads rate plans by package name.
   *
   * @param string $package_name
   *   The name of the API package.
   *
   * @return \Drupal\apigee_m10n\Entity\RatePlanInterface[]
   *   An array of rate plans for a given package.
   */
  public function loadPackageRatePlans(string $package_name): array;

  /**
   * Load an individual package rate plan by package_id and rate plan ID.
   *
   * @param string $package_name
   *   The package the rate plan belongs to.
   * @param string $id
   *   The rate plan ID.
   *
   * @return \Drupal\apigee_m10n\Entity\RatePlanInterface
   *   The rate plan.
   */
  public function loadById(string $package_name, string $id): RatePlanInterface;

  /**
   * Get a package controller.
   *
   * @param string $api_package_id
   *   The Package ID.
   *   This is required in order to instantiate a package rate plan controller.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\RatePlanController
   *   The package rate plan sdk controller.
   */
  public function getController(string $api_package_id): RatePlanControllerInterface;

  /**
   * Get the controller factory.
   *
   * Since entity storage classes are used during install, this is used instead
   * of dependency injection to avoid an issue with the sdk controller
   * credentials not yet being available when the entity storage controller is
   * accessed.
   *
   * @return \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface
   *   The controller factory.
   */
  public function controllerFactory(): ApigeeSdkControllerFactoryInterface;

}
