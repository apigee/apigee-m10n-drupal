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
use Drupal\apigee_edge\Entity\Controller\EdgeEntityControllerInterface;

/**
 * Some additional loaders for the rate plan SDK storage controller proxy.
 */
interface RatePlanSdkControllerProxyInterface extends EdgeEntityControllerInterface {

  /**
   * Loads all rate plans for a given product bundle.
   *
   * @param string $product_bundle_id
   *   The product bundle ID.
   * @param bool $include_future_plans
   *   Whether to include future plans in the list.
   * @param bool $standard_only
   *   Whether to show standard plans only.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface[]
   *   A list of rate plans keyed by ID.
   */
  public function loadRatePlansByProductBundle($product_bundle_id, $include_future_plans = FALSE, $standard_only = FALSE): array;

  /**
   * Loads a rate plan by ID.
   *
   * @param string $product_bundle_id
   *   The product bundle ID.
   * @param string $id
   *   The rate plan ID.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\RatePlanInterface
   *   The rate plan.
   */
  public function loadById(string $product_bundle_id, string $id): EntityInterface;

}
