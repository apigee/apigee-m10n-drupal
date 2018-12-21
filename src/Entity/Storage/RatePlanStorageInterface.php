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

use Drupal\apigee_m10n\Entity\RatePlanInterface;

/**
 * Defines an interface for the rate plan storage class.
 */
interface RatePlanStorageInterface {

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

}
