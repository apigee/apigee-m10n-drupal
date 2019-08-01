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

namespace Drupal\apigee_m10n\Entity\Property;

/**
 * Trait ApiProductsPropertyAwareDecoratorTrait.
 */
trait ApiProductsPropertyAwareDecoratorTrait {

  /**
   * {@inheritdoc}
   */
  public function getApiProducts(): array {
    // Convert products to entity references.
    return array_map(function ($sdk_api_product) {
      return ['target_id' => $sdk_api_product->getName()];
    }, $this->decorated()->getApiProducts());
  }

}
