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

namespace Drupal\Tests\apigee_m10n_add_credit\Traits;

use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\user\UserInterface;

/**
 * Helpers for testing add credit.
 */
trait AddCreditFunctionalTestTrait {

  use StoreCreationTrait;

  /**
   * Helper to create and log in a user.
   *
   * @param array|null $permissions
   *   An array of user permissions.
   *
   * @return \Drupal\user\UserInterface
   *   The logged in user.
   *
   * @throws \Exception
   */
  protected function signIn(array $permissions = []): UserInterface {
    $account = $this->createAccount($permissions);
    $this->drupalLogin($account);
    return $account;
  }

  /**
   * Helper to sign in as an admin account.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   *
   * @throws \Exception
   */
  protected function signInAsAdmin(): UserInterface {
    return $this->signIn(array_keys(\Drupal::service('user.permissions')
      ->getPermissions()));
  }

  /**
   * Helper to create a commerce store.
   *
   * @param string|null $mail
   *   The store default email.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The store entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createCommerceStore(string $mail = NULL): StoreInterface {
    $store = $this->createStore(NULL, $mail ?? $this->config('system.site')
      ->get('mail'));
    $store->save();
    return $store;
  }

  /**
   * Helper to create a commerce product.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store entity.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation entity.
   * @param string|null $title
   *   The title of the product.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The product entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createCommerceProduct(StoreInterface $store, ProductVariationInterface $variation, string $title = NULL): ProductInterface {
    $product = Product::create([
      'title' => $title ?? $this->randomMachineName(),
      'type' => 'default',
      'stores' => [$store->id()],
      'variations' => [$variation],
      AddCreditConfig::ADD_CREDIT_ENABLED_FIELD_NAME => 1,
    ]);

    $product->save();

    return $product;
  }

  /**
   * Helper to create a commerce product variation.
   *
   * @param string|null $title
   *   The title of the variation.
   * @param string|null $sku
   *   The SKU of the variation.
   * @param \Drupal\commerce_price\Price|null $price
   *   The price of the variation.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   The variation entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createCommerceProductVariation(string $title = NULL, string $sku = NULL, Price $price = NULL): ProductVariationInterface {
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $sku ?? $this->randomMachineName(),
      'title' => $title ?? $this->randomString(),
      'status' => 1,
      'price' => $price ?? new Price('12.00', 'USD'),
    ]);

    $variation->save();

    return $variation;
  }

  /**
   * Helper to create a commerce payment gateway.
   *
   * @param string|null $id
   *   The gateway id.
   * @param string|null $label
   *   The gateway label.
   * @param array|null $configuration
   *   The gateway configuration.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentGatewayInterface
   *   The gateway entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createCommercePaymentGateway(string $id = NULL, string $label = NULL, array $configuration = NULL): PaymentGatewayInterface {
    $gateway = PaymentGateway::create([
      'id' => $id ?? 'onsite',
      'label' => $label ?? 'On-site',
      'plugin' => 'example_onsite',
      'configuration' => $configuration ?? [
        'api_key' => '2342fewfsfs',
        'payment_method_types' => ['credit_card'],
      ],
    ]);

    $gateway->save();

    return $gateway;
  }

}
