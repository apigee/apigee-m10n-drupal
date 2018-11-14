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

namespace Drupal\apigee_m10n\Entity;

use Apigee\Edge\Api\Monetization\Entity\RatePlanInterface as MonetizationRatePlanInterface;
use Drupal\apigee_edge\Entity\FieldableEdgeEntityInterface;

/**
 * Defines the interface for package rate plan entity objects.
 */
interface RatePlanInterface extends MonetizationRatePlanInterface, FieldableEdgeEntityInterface {

  /**
   * Getter for the `isAdvance` call.
   *
   * @return bool
   *   Whether or not this is an advance rate plan.
   */
  public function getAdvance(): bool;

  /**
   * Getter for the `isPrivate` call.
   *
   * @return bool
   *   Whether or not this is a private rate plan.
   */
  public function getPrivate(): bool;

  /**
   * Getter for the `isProrate` call.
   *
   * @return bool
   *   Whether or not this is a prorate rate plan.
   */
  public function getProrate(): bool;

  /**
   * Getter for the `isPublished` call.
   *
   * @return bool
   *   Whether or not this rate plan is published.
   */
  public function getPublished(): bool;

  /**
   * Loads rate plans by package name.
   *
   * @param string $package_name
   *   The name of the API package.
   *
   * @return \Drupal\apigee_m10n\Entity\RatePlanInterface[]
   *   An array of rate plans for a given package.
   */
  public static function loadPackageRatePlans(string $package_name): array;

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
  public static function loadById(string $package_name, string $id): RatePlanInterface;

}
