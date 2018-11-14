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

namespace Drupal\Tests\apigee_m10n\Functional;

use Drupal\Tests\apigee_m10n\Traits\ApigeeMonetizationTestTrait;
use Drupal\apigee_m10n\EnvironmentVariable;
use Drupal\Tests\BrowserTestBase;

/**
 * A base class for functional tests.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class MonetizationFunctionalTestBase extends BrowserTestBase {

  use ApigeeMonetizationTestTrait {
    setUp as baseSetUp;
  }

  protected static $modules = [
    'apigee_edge',
    'apigee_m10n',
    'apigee_mock_client',
    'system',
  ];

  /**
   * The mock handler stack is responsible for serving queued api responses.
   *
   * @var \Drupal\apigee_mock_client\MockHandlerStack
   */
  protected $stack;

  /**
   * Whether actual integration tests are enabled.
   *
   * @var bool
   */
  protected $integration_enabled;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->integration_enabled = !empty(getenv(EnvironmentVariable::$APIGEE_EDGE_ENDPOINT));

    // Create new Apigee Edge basic auth key.
    $this->init();
  }

}
