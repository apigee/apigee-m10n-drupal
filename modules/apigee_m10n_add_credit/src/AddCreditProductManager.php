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
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a service for managing add credit products.
 */
class AddCreditProductManager implements AddCreditProductManagerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Local cache of products keyed by the currency ID..
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface[]
   */
  protected $productByCurrency = [];

  /**
   * AddCreditProductManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductForCurrency(string $currency_id): ?ProductInterface {
    if (!isset($this->productByCurrency[$currency_id])) {
      $this->productByCurrency[$currency_id] = ($product_id = $this->configFactory->get(AddCreditConfig::CONFIG_NAME)->get("products.$currency_id.product_id"))
        && ($product = $this->entityTypeManager->getStorage('commerce_product')->load($product_id)) ? $product : NULL;
    }
    return $this->productByCurrency[$currency_id];
  }

  /**
   * {@inheritdoc}
   */
  public function isProductAddCreditEnabled(ProductInterface $product): bool {
    return $product->hasField(AddCreditConfig::ADD_CREDIT_ENABLED_FIELD_NAME) && $product->get(AddCreditConfig::ADD_CREDIT_ENABLED_FIELD_NAME)->value;
  }

}
