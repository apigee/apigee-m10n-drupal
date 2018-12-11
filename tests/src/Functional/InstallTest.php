<?php

/**
 * @file
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

use Behat\Behat\HelperContainer\Exception\ServiceNotFoundException;
use Drupal\apigee_edge\Exception\AuthenticationKeyException;
use Drupal\Tests\apigee_m10n\Traits\ApigeeMonetizationTestTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests apigee_m10n installation.
 *
 * @package Drupal\Tests\apigee_m10n\Functional
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class InstallTest extends BrowserTestBase {

  use ApigeeMonetizationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests installation when Apigee Edge is not available.
   */
  public function testInstallWhenApigeeEdgeNotAvailable() {
    try {
      $this->container->get('module_installer')->install(['apigee_m10n']);
    }
    catch (\Exception $exception) {
      $this->assertInstanceOf(ServiceNotFoundException::class, $exception);
      $this->assertSession()->pageTextContains('Apigee Edge authentication is not properly configured. A working Apigee Edge connection is required to enable Apigee Monetization.');
      $this->assertFalse($this->container->get('module_handler')->moduleExists('apigee_m10n'));
    }
  }

  /**
   * Tests installation when Apigee Edge is enabled but not configured.
   */
  public function testInstallWhenApigeeEdgeNotConfigured() {
    $module_installer = $this->container->get('module_installer');

    // Install apigee_edge.
    $module_installer->install(['apigee_edge']);

    try {
      $module_installer->install(['apigee_m10n']);
    }
    catch (\Exception $exception) {
      $this->assertInstanceOf(AuthenticationKeyException::class, $exception);
      $this->assertSession()->pageTextContains('Apigee Edge authentication is not properly configured. A working Apigee Edge connection is required to enable Apigee Monetization.');
      $this->assertFalse($this->container->get('module_handler')->moduleExists('apigee_m10n'));
    }
  }

  /**
   * Tests installation.
   */
  public function testInstallation() {
    $module_installer = $this->container->get('module_installer');

    // Install apigee_edge.
    $module_installer->install(['apigee_edge']);

    // Configure connection.
    $module_installer->install(['apigee_m10n_test', 'apigee_mock_client']);
    $this->rebuildContainer();
    $this->initAuth();

    // Install apigee_m10n.
    $module_installer->install(['apigee_m10n']);

    $this->assertTrue($this->container->get('module_handler')->moduleExists('apigee_m10n'));
  }

}
