<?php

/*
 * Copyright 2021 Google Inc.
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

namespace Drupal\Tests\apigee_m10n\Kernel\ApigeeX;

use Drupal\apigee_edge\OauthTokenFileStorage;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\apigee_m10n\Traits\ApigeeX\ApigeeMonetizationTestTrait;
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
   * Test token data.
   *
   * @var array
   */
  private $testTokenData = [];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'key',
    'file',
    'link',
    'entity',
    'apigee_edge',
    'apigee_m10n',
    'apigee_m10n_test',
    'apigee_mock_api_client',
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
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['apigee_edge', 'apigee_m10n']);

    $this->baseSetUp();

  }

  /**
   * {@inheritdoc}
   */
  protected function assertCssElementText($selector, $text) {
    $element = $this->cssSelect($selector);
    static::assertArrayHasKey(0, $element, "No match found for `{$selector}`.");
    static::assertSame(trim($element[0]), $text);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertCssElementContains($selector, $text) {
    $element = $this->cssSelect($selector);
    static::assertTrue(strpos((string) $element[0], $text) !== FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertCssElementNotContains($selector, $text) {
    $element = $this->cssSelect($selector);
    static::assertTrue(strpos((string) $element[0], $text) === FALSE);
  }

  /**
   * Stores pre-configured token storage service for testing.
   */
  protected function storeToken() {
    // Storing the token for Appigeex Hybrid Org.
    $this->testTokenData = [
      'access_token' => mb_strtolower($this->randomMachineName(32)),
      'token_type' => 'bearer',
      'expires_in' => 300,
      'refresh_token' => mb_strtolower($this->randomMachineName(32)),
      'scope' => 'create',
    ];
    $storage = $this->tokenStorage();
    // Save the token.
    $storage->saveToken($this->testTokenData);
  }

  /**
   * Returns a pre-configured token storage service for testing.
   *
   * @param bool $rebuild
   *   Enforces rebuild of the container and with the the token storage
   *   service.
   *
   * @return \Drupal\apigee_edge\OauthTokenFileStorage
   *   The configured and initialized OAuth file token storage service.
   *
   * @throws \Exception
   */
  private function tokenStorage(bool $rebuild = FALSE): OauthTokenFileStorage {
    $config = $this->config('apigee_edge.auth');
    $config->set('oauth_token_storage_location', $this->tokenDirectoryUri())->save();
    if ($rebuild) {
      $this->container->get('kernel')->rebuildContainer();
    }
    return $this->container->get('apigee_edge.authentication.oauth_token_storage');
  }

  /**
   * Returns the URI of the token directory.
   *
   * @return string
   *   Token directory URI.
   */
  private function tokenDirectoryUri(): string {
    $customTokenDir = 'oauth/token/dir';
    return $this->vfsRoot->url() . '/' . $customTokenDir;
  }

}
