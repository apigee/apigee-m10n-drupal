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
 * Defines the interface for rate plan entity objects.
 */
interface RatePlanInterface extends MonetizationRatePlanInterface, FieldableEdgeEntityInterface {

  /**
   * Gets the ID of the product bundle this rate plan belongs to.
   *
   * @return string
   *   The product bundle ID.
   */
  public function getProductBundleId();

  /**
   * Loads rate plans by product bundle id.
   *
   * @param string $product_bundle
   *   The name of the product bundle.
   *
   * @return \Drupal\apigee_m10n\Entity\RatePlanInterface[]
   *   An array of rate plans for a given product bundle.
   */
  public static function loadRatePlansByProductBundle(string $product_bundle): array;

  /**
   * Load an individual rate plan by product_bundle_id and rate plan ID.
   *
   * @param string $product_bundle_id
   *   The product bundle the rate plan belongs to.
   * @param string $id
   *   The rate plan ID.
   *
   * @return \Drupal\apigee_m10n\Entity\RatePlanInterface
   *   The rate plan.
   */
  public static function loadById(string $product_bundle_id, string $id): RatePlanInterface;

  /**
   * Get's data for the `apigee_purchase` field formatter.
   *
   * @return array|null
   *   An array with data to build a link or form to purchase a rate plan.
   */
  public function getPurchase():? array;

  /**
   * Gets the start date of the future plan for if a future plan exists.
   *
   * @return \DateTimeImmutable|null
   *   The future start date or NULL if there is no future plan.
   */
  public function getFuturePlanStartDate(): ?\DateTimeImmutable;

  /**
   * Gets a link to the future plan if a future plan exists.
   *
   * @return \Drupal\link\LinkItemInterface|null
   *   A link to the future plan.
   */
  public function getFuturePlanLinks();

}
