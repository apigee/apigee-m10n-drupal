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
   * Loads rate plans by product bundle id.
   *
   * @param string $product_bundle_id
   *   The product bundle ID.
   * @param bool $include_future_plans
   *   Whether to include future plans in the list.
   *
   * @return \Drupal\apigee_m10n\Entity\RatePlanInterface[]
   *   An array of rate plans for a given product bundle.
   */
  public function loadRatePlansByProductBundle(string $product_bundle_id, $include_future_plans = FALSE): array;

  /**
   * Load an individual rate plan by product bundle id and rate plan ID.
   *
   * @param string $product_bundle_id
   *   The product bundle the rate plan belongs to.
   * @param string $id
   *   The rate plan ID.
   *
   * @return \Drupal\apigee_m10n\Entity\RatePlanInterface
   *   The rate plan.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the rate plan doesn't exist.
   */
  public function loadById(string $product_bundle_id, string $id): RatePlanInterface;

  /**
   * Gets the future rate plan of a rate plan.
   *
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface $ratePlan
   *   The "current" rate plan.
   *
   * @return \Drupal\apigee_m10n\Entity\RatePlanInterface|null
   *   The future rate plan or NULL if none were found.
   */
  public function loadFutureRatePlan(RatePlanInterface $ratePlan): ?RatePlanInterface;

  /**
   * Validates a rate plan ID.
   *
   * @param string $id
   *   The rate plan ID.
   *
   * @return bool
   *   TRUE if the rate plan ID is valid.
   */
  public function isValidId(string $id): bool;

}
