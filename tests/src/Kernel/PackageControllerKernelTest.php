<?php
/**
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

use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Functional tests for the package controller.
 *
 * @package Drupal\Tests\apigee_m10n\Functional
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 *
 * @coversDefaultClass \Drupal\apigee_m10n\Controller\PackagesController
 */
class PackageControllerKernelTest extends MonetizationKernelTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface $account
   */
  protected $account;

  /**
   * (@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'apigee_edge',
    ]);

    $this->account = $this->createAccount([
      'access monetization packages',
      'access purchased monetization packages',
    ]);
    $this->setCurrentUser($this->account);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function tearDown() {
    // Prepare for deleting the developer.
    $this->queueDeveloperResponse($this->account);
    $this->queueDeveloperResponse($this->account);

    // We have to remove the developer we created so it is removed from Apigee.
    $this->account->delete();

    // Currently we can't delete users with a balance so we can expect an error
    // was logged.
    $this->assertNoClientError();

    parent::tearDown();
  }

  /**
   * Tests the redirects for accessing the current users packages.
   *
   * Some drivers like (selenium 2) don't support `getStatusCode` so this test
   * works better as a kernel test.
   *
   * @throws \Exception
   *
   * @covers ::myCatalog
   * @covers ::myPurchased
   */
  public function testMyRedirects() {
    /**
     * ## Test the packages redirect. ##
     */
    // Queue up a monetized org response.
    $this->stack->queueFromResponseFile('get_monetized_org');
    $request = Request::create('/user/monetization/packages', 'GET');

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    $response = $kernel->handle($request);

    static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    static::assertSame('http://localhost/user/'.$this->account->id().'/monetization/packages', $response->headers->get('location'));

    /**
     * ## Test the purchased packages redirect. ##
     */
    // Queue up a monetized org response.
    $request = Request::create('/user/monetization/packages/purchased', 'GET');

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    $response = $kernel->handle($request);

    static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    static::assertSame('http://localhost/user/'.$this->account->id().'/monetization/packages/purchased', $response->headers->get('location'));
  }
}
