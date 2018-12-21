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

namespace Drupal\Tests\apigee_m10n\Kernel\Controller;

use Drupal\apigee_m10n\Controller\PackagesController;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Functional tests for the package controller.
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
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
    ]);

    $this->developer = $this->createAccount([
      'access monetization packages',
      'view subscription',
    ]);
    $this->setCurrentUser($this->developer);
  }

  /**
   * Tests the redirects for accessing the current users packages.
   *
   * Some drivers like (selenium 2) don't support `getStatusCode` so this test
   * works better as a kernel test.
   *
   * @throws \Exception
   */
  public function testMyRedirects() {
    // Test the packages redirect.
    // Queue up a monetized org response.
    $this->stack->queueMockResponse('get_monetized_org');
    $request = Request::create(Url::fromRoute('apigee_monetization.my_packages')->toString(), 'GET');

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    $response = $kernel->handle($request);

    static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    static::assertSame('http://localhost/user/' . $this->developer->id() . '/monetization/packages', $response->headers->get('location'));

    // Test the purchased packages redirect.
    // Queue up a monetized org response.
    $request = Request::create(Url::fromRoute('apigee_monetization.my_subscriptions')->toString(), 'GET');

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    $response = $kernel->handle($request);

    static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    static::assertSame('http://localhost/user/' . $this->developer->id() . '/monetization/subscriptions', $response->headers->get('location'));
  }

  /**
   * Tests the `monetization-packages` response.
   *
   * @throws \Exception
   * @throws \Http\Client\Exception
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function testPackageResponse() {
    /** @var \Apigee\Edge\Api\Monetization\Entity\ApiPackage[] $packages */
    $packages = [
      $this->createPackage(),
      $this->createPackage(),
    ];

    $this->stack
      ->queueMockResponse(['get_monetization_packages' => ['packages' => $packages]]);

    $response = (string) $this->sdk_connector
      ->getClient()
      ->get("/mint/organizations/{$this->sdk_connector->getOrganization()}/monetization-packages")
      ->getBody();

    foreach ($packages as $package) {
      static::assertContains($package->id(), $response);
      static::assertContains($package->getDisplayName(), $response);
      static::assertContains($package->getName(), $response);
      static::assertContains($package->getDescription(), $response);
    }

    $data = json_decode($response, TRUE);
    static::assertNotEmpty($data);
  }

  /**
   * Tests the `monetization-packages` response.
   *
   * @throws \Exception
   */
  public function testPackageController() {

    $packages = [
      $this->createPackage(),
      $this->createPackage(),
    ];
    $plans = [];
    foreach ($packages as $package) {
      $plans[$package->id()][] = $this->createPackageRatePlan($package);
    }

    $page_controller = PackagesController::create($this->container);

    $this->stack
      ->queueMockResponse(['get_monetization_packages' => ['packages' => $packages]]);

    foreach ($packages as $package) {
      $this->stack
        ->queueMockResponse(['get_monetization_package_plans' => ['plans' => $plans[$package->id()]]]);
    }

    $renderable = $page_controller->catalogPage($this->developer);

    self::assertArrayHasKey($packages[0]->id(), $renderable["package_list"]["#package_list"]);
    self::assertArrayHasKey($packages[0]->id(), $renderable["package_list"]["#plan_list"]);
    self::assertArrayHasKey($plans[$packages[0]->id()][0]->id(), $renderable["package_list"]["#plan_list"][$packages[0]->id()]);
    self::assertArrayHasKey($packages[1]->id(), $renderable["package_list"]["#package_list"]);
    self::assertArrayHasKey($packages[1]->id(), $renderable["package_list"]["#plan_list"]);
    self::assertArrayHasKey($plans[$packages[1]->id()][0]->id(), $renderable["package_list"]["#plan_list"][$packages[1]->id()]);
  }

}
