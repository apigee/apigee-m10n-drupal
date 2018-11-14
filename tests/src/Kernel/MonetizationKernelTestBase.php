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

namespace Drupal\Tests\apigee_m10n\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\apigee_m10n\Traits\ApigeeMonetizationTestTrait;
use Drupal\apigee_m10n\EnvironmentVariable;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * The base class for Monetization kernel tests.
 */
class MonetizationKernelTestBase extends KernelTestBase {

  use ApigeeMonetizationTestTrait {
    setUp as baseSetUp;
  }
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'key',
    'file',
    'apigee_edge',
    'apigee_m10n',
    'apigee_mock_client',
    'user',
    'system',
  ];

  /**
   * Whether actual integration tests are enabled.
   *
   * @var bool
   */
  protected $integration_enabled;

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['apigee_edge', 'apigee_m10n']);

    $this->integration_enabled = !empty(getenv(EnvironmentVariable::$APIGEE_INTEGRATION_ENABLE));

    $this->baseSetUp();
  }

}
