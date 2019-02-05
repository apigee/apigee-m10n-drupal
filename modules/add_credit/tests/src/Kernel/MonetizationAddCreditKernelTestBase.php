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

namespace Drupal\Tests\apigee_m10n_add_credit\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * The base class for Monetization Add credit kernel tests.
 */
class MonetizationAddCreditKernelTestBase extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'address',
    'datetime',
    'entity',
    'options',
    'inline_entity_form',
    'views',
    'commerce',
    'commerce_price',
    'commerce_store',
    'path',

    'commerce_product',
    'commerce_order',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'key',
    'apigee_edge',
    'apigee_m10n',
    'apigee_m10n_add_credit',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);
    $this->installEntitySchema('commerce_product');
  }

}
