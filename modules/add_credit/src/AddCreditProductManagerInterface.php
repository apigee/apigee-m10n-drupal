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

namespace Drupal\apigee_m10n_add_credit;

use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Defines an interface for a 'add credit product manager'.
 */
interface AddCreditProductManagerInterface {

  /**
   * Helper to get the configured product for the given currency id.
   *
   * @param string $currency_id
   *   The currency id.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   A product entity if found. Otherwise null.
   */
  public function getProductForCurrency(string $currency_id): ?ProductInterface;

  /**
   * Determines if a product is add_credit enabled.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product entity.
   *
   * @return bool
   *   TRUE if the product is add credit enabled.
   */
  public function isProductAddCreditEnabled(ProductInterface $product): bool;

}
