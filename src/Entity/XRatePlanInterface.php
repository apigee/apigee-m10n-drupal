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

namespace Drupal\apigee_m10n\Entity;

use Apigee\Edge\Api\ApigeeX\Entity\RatePlanInterface as MonetizationXRatePlanInterface;
use Drupal\apigee_edge\Entity\FieldableEdgeEntityInterface;

/**
 * Defines the interface for xrate plan entity objects.
 */
interface XRatePlanInterface extends MonetizationXRatePlanInterface, FieldableEdgeEntityInterface {

  /**
   * Gets the ID of the xproduct this rate plan belongs to.
   *
   * @return string
   *   The product ID.
   */
  public function getProductId();

  /**
   * Loads rate plans by product bundle id.
   *
   * @param string $product_bundle
   *   The name of the product bundle.
   *
   * @return \Drupal\apigee_m10n\Entity\XRatePlanInterface[]
   *   An array of rate plans for a given product bundle.
   */
  public static function loadRatePlansByProduct(string $product_bundle): array;

  /**
   * Load an individual rate plan by product_bundle_id and rate plan ID.
   *
   * @param string $product_bundle_id
   *   The product bundle the rate plan belongs to.
   * @param string $id
   *   The rate plan ID.
   *
   * @return \Drupal\apigee_m10n\Entity\XRatePlanInterface
   *   The rate plan.
   */
  public static function loadById(string $product_bundle_id, string $id): XRatePlanInterface;

  /**
   * Get's data for the `apigee_purchase_product` field formatter.
   *
   * @return array|null
   *   An array with data to build a link or form to purchase a rate plan.
   */
  public function getPurchase():? array;

}
