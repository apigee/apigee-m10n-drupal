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

namespace Drupal\Tests\apigee_m10n_add_credit\FunctionalJavascript;

use Drupal\Tests\apigee_m10n\FunctionalJavascript\MonetizationFunctionalJavascriptTestBase;
use Drupal\Tests\apigee_m10n_add_credit\Traits\AddCreditFunctionalTestTrait;

/**
 * Base class for monetization add credit functional javascript tests.
 */
class AddCreditFunctionalJavascriptTestBase extends MonetizationFunctionalJavascriptTestBase {

  use AddCreditFunctionalTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Base modules.
    'key',
    'file',
    'apigee_edge',
    'apigee_m10n',
    'apigee_mock_client',
    'system',
    // Modules for this test.
    'apigee_m10n_add_credit',
    'commerce_order',
    'commerce_price',
    'commerce_cart',
    'commerce_checkout',
    'commerce_product',
    'commerce_payment',
    'commerce_payment_test',
    'commerce_store',
    'commerce',
    'user',
  ];

}
