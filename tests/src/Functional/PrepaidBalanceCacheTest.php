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

use Drupal\apigee_m10n\Controller\BillingController;
use Drupal\apigee_m10n\Form\BillingConfigForm;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Traits\PrepaidBalanceReportsDownloadFormTestTrait;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Functional test for prepaid balance cache.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class PrepaidBalanceCacheTest extends MonetizationFunctionalTestBase {

  use AssertPageCacheContextsAndTagsTrait;
  use PrepaidBalanceReportsDownloadFormTestTrait;

  /**
   * The max age for the prepaid balance cache.
   */
  const CACHE_MAX_AGE = 60;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * {@inheritdoc}
   */
  protected $developer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->developer = $this->createAccount([
      'view mint prepaid reports',
      'refresh prepaid balance reports',
      'download prepaid balance reports',
    ]);

    $this->drupalLogin($this->developer);
    $this->queueOrg();
    $this->queueResponses();

    // Enable prepaid balance cache.
    $this->config(BillingConfigForm::CONFIG_NAME)
      ->set('prepaid_balance.cache_max_age', static::CACHE_MAX_AGE)
      ->save();

    $this->cacheBackend = $this->container->get('cache.default');
  }

  /**
   * User without proper permissions cannot refresh balance reports.
   *
   * @throws \Exception
   */
  public function testPrepaidBalanceRefreshPermission() {
    // Given that we have a user with no 'refresh prepaid balance reports'
    // permissions.
    $user = $this->createAccount(['view mint prepaid reports']);
    $this->drupalLogin($user);
    $this->queueOrg();

    // Access denied on refresh route.
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing_refresh', [
      'user' => $user->id(),
    ]));
    $this->assertSession()->responseContains('Access denied');

    // The user cannot see the refresh button on the billing page.
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $user->id(),
    ]));
    $this->assertSession()->responseNotContains('Refresh');
  }

  /**
   * A user can only refresh own balance.
   */
  public function testPrepaidBalanceOwnBalanceRefresh() {
    // Access denied on refresh route for another user.
    $other_user = $this->createAccount([
      'view mint prepaid reports',
      'refresh prepaid balance reports',
    ]);
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing_refresh', [
      'user' => $other_user->id(),
    ]));
    $this->assertSession()->responseContains('Access denied');

    // Access allowed on refresh route for own account.
    $this->queueResponses();
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing_refresh', [
      'user' => $this->developer->id(),
    ]));
    $this->assertSession()->responseNotContains('Access denied');

    // The user can see the refresh button on the billing page.
    $this->queueResponses();
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->developer->id(),
    ]));
    $this->assertSession()->responseContains('Refresh');
  }

  /**
   * Test if the prepaid balances are properly cached.
   */
  public function testPrepaidBalanceCache() {
    $cache_ids = [
      BillingController::getCacheId($this->developer, 'prepaid_balances'),
      BillingController::getCacheId($this->developer, 'supported_currencies'),
      BillingController::getCacheId($this->developer, 'billing_documents'),
    ];

    // Visit the prepaid balance page.
    $this->queueResponses();
    $expected_expiration_time = time() + static::CACHE_MAX_AGE;
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->developer->id(),
    ]));
    $this->assertCacheIdsExist($cache_ids);

    // Check if max age is properly set.
    $this->assertCacheIdsExpire($cache_ids, $expected_expiration_time);

    // Check if caches are rebuilt when refresh button is clicked.
    $this->assertCacheIdsRebuilt($cache_ids, function () {
      $this->queueResponses();

      $this->clickLink('Refresh');

      $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
        'user' => $this->developer->id(),
      ]));
    });
  }

  /**
   * Tests the response cache tags.
   */
  public function testPrepaidBalanceCacheTags() {
    $this->checkDriverHeaderSupport();

    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->developer->id(),
    ]));

    $expected_tags = Cache::mergeTags(BillingController::getCacheTags($this->developer), ['rendered']);
    $this->assertCacheTags($expected_tags);
  }

  /**
   * Test if cache can be disabled.
   */
  public function testPrepaidBalanceCacheDisable() {
    // Disable the cache.
    $this->config(BillingConfigForm::CONFIG_NAME)
      ->set('prepaid_balance.cache_max_age', 0)
      ->save();

    $cache_ids = [
      BillingController::getCacheId($this->developer, 'prepaid_balances'),
      BillingController::getCacheId($this->developer, 'supported_currencies'),
      BillingController::getCacheId($this->developer, 'billing_documents'),
    ];

    // Visit the prepaid balance page.
    $this->queueResponses();
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->developer->id(),
    ]));
    $this->assertCacheIdsNotExist($cache_ids);
  }

  /**
   * Helper to queue mock responses.
   */
  protected function queueResponses() {
    $this->queueMockResponses([
      'get-prepaid-balances',
      'get-supported-currencies',
      'get-billing-documents-months',
    ]);
  }

  /**
   * Check if cache with given IDs exist.
   *
   * @param array $cids
   *   An array of cache IDs.
   */
  protected function assertCacheIdsExist(array $cids) {
    foreach ($cids as $cid) {
      $this->assertTrue($this->cacheBackend->get($cid));
    }
  }

  /**
   * Check if cache with given IDs do not exist.
   *
   * @param array $cids
   *   An array of cache IDs.
   */
  protected function assertCacheIdsNotExist(array $cids) {
    foreach ($cids as $cid) {
      $this->assertFalse($this->cacheBackend->get($cid));
    }
  }

  /**
   * Check the expiration time for cache with given IDs.
   *
   * @param array $cids
   *   An array of cache IDs.
   * @param int $expected
   *   The expected cache expiration time.
   */
  protected function assertCacheIdsExpire(array $cids, int $expected) {
    foreach ($cids as $cid) {
      if ($cache = $this->cacheBackend->get($cid)) {
        // The cache expiration must be greater or equal to the expected time.
        $this->assertGreaterThanOrEqual($expected, $cache->expire);
      }
    }
  }

  /**
   * Checks if cache with given IDs have been rebuilt.
   *
   * @param array $cids
   *   An array of cache IDs.
   * @param callable $callback
   *   The callback that invalidates the cache.
   */
  protected function assertCacheIdsRebuilt(array $cids, callable $callback) {
    foreach ($cids as $cid) {
      $cache_before = $this->cacheBackend->get($cid);
      $callback();
      $cache_after = $this->cacheBackend->get($cid);
      $this->assertGreaterThan($cache_before->created, $cache_after->created);
    }
  }

}
