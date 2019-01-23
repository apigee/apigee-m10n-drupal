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

use Drupal\apigee_m10n\Controller\PrepaidBalanceController;
use Drupal\apigee_m10n\Form\PrepaidBalanceConfigForm;
use Drupal\apigee_m10n\Form\PrepaidBalanceRefreshForm;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Traits\PrepaidBalanceReportsDownloadFormTestTrait;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\user\Entity\Role;
use Drupal\user\UserInterface;

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
      'download prepaid balance reports',
    ]);
    $this->drupalLogin($this->developer);

    // Enable prepaid balance cache.
    $this->config(PrepaidBalanceConfigForm::CONFIG_NAME)
      ->set('cache.max_age', static::CACHE_MAX_AGE)
      ->save();

    $this->cacheBackend = $this->container->get('cache.default');
  }

  /**
   * Test user with no refresh prepaid balance permission..
   *
   * @throws \Exception
   */
  public function testNoPermission() {
    // User cannot refresh prepaid balance.
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->developer->id(),
    ]));
    $this->assertSession()->responseContains('Prepaid balance');
    $this->assertSession()->responseNotContains('Refresh');
  }

  /**
   * Test user with "refresh own prepaid balance" permission.
   */
  public function testRefreshOwnPrepaidBalancePermission() {
    // Given a user with the 'refresh own prepaid balance permission'.
    $user_roles = $this->developer->getRoles();
    $this->grantPermissions(Role::load(reset($user_roles)), ['refresh own prepaid balance']);
    $this->queueOrg();
    $this->queueResponses();

    // User can refresh own account.
    $this->assertRefreshPrepaidBalanceForUser($this->developer);
  }

  /**
   * Test user with "refresh any prepaid balance" permission.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testRefreshAnyPrepaidBalancePermission() {
    // Given a user with "refresh any prepaid balance" permission.
    // TODO: Update permissions when 'view any prepaid balance' permission is added.
    $user_roles = $this->developer->getRoles();
    $this->grantPermissions(Role::load(reset($user_roles)), ['refresh any prepaid balance']);
    $this->queueOrg();
    $this->queueResponses();

    // User can refresh own account.
    $this->assertRefreshPrepaidBalanceForUser($this->developer);

    // User can refresh another user account.
    $other_user = $this->createAccount(['view mint prepaid reports']);
    $this->assertRefreshPrepaidBalanceForUser($other_user);
  }

  /**
   * Test if the prepaid balances Ids are properly cached.
   */
  public function testPrepaidBalanceCacheIds() {
    $user_roles = $this->developer->getRoles();
    $this->grantPermissions(Role::load(reset($user_roles)), ['refresh own prepaid balance']);
    $this->queueOrg();
    $this->queueResponses();

    $cache_ids = [
      PrepaidBalanceController::getCacheId($this->developer, 'prepaid_balances'),
      PrepaidBalanceController::getCacheId($this->developer, 'supported_currencies'),
      PrepaidBalanceController::getCacheId($this->developer, 'billing_documents'),
    ];

    // Visit the prepaid balance page.
    $expected_expiration_time = time() + static::CACHE_MAX_AGE;
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $this->developer->id(),
    ]));
    $this->assertCacheIdsExist($cache_ids);

    // Check if max age is properly set.
    $this->assertCacheIdsExpire($cache_ids, $expected_expiration_time);

    // Check if caches are rebuilt when refresh form is submitted.
    $this->assertCacheIdsRebuilt($cache_ids, function () {
      $this->submitForm([], 'Refresh');
      $this->assertSession()->responseContains(PrepaidBalanceRefreshForm::SUCCESS_MESSAGE);
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
    $expected_tags = Cache::mergeTags(PrepaidBalanceController::getCacheTags($this->developer), ['rendered']);
    $this->assertCacheTags($expected_tags);
  }

  /**
   * Test if cache can be disabled.
   */
  public function testPrepaidBalanceCacheDisable() {
    // Disable the cache.
    $this->config(PrepaidBalanceConfigForm::CONFIG_NAME)
      ->set('cache.max_age', 0)
      ->save();

    $cache_ids = [
      PrepaidBalanceController::getCacheId($this->developer, 'prepaid_balances'),
      PrepaidBalanceController::getCacheId($this->developer, 'supported_currencies'),
      PrepaidBalanceController::getCacheId($this->developer, 'billing_documents'),
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
   * Asserts current user can refresh account for given user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertRefreshPrepaidBalanceForUser(UserInterface $user) {
    $this->drupalGet(Url::fromRoute('apigee_monetization.billing', [
      'user' => $user->id(),
    ]));
    $this->assertSession()->responseContains('Prepaid balance');
    $this->submitForm([], 'Refresh');
    $this->assertSession()->responseContains(PrepaidBalanceRefreshForm::SUCCESS_MESSAGE);
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
