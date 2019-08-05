<?php

/**
 * Copyright 2019 Google Inc.
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

use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ProductBundleAdminListingTest.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class ProductBundleAdminListingTest extends MonetizationKernelTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticated;

  /**
   * Drupal user.
   *
   * @var \Drupal\apigee_m10n\Entity\ProductBundleInterface[]
   */
  protected $product_bundles;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);

    $this->warmOrganizationCache();

    // Create user 1 account - will not be used for tests.
    $this->createAccount([]);

    $this->authenticated = $this->createAccount();
    $this->admin = $this->createAccount([
      'administer apigee monetization',
    ]);

    $this->product_bundles = [
      $this->createProductBundle(),
      $this->createProductBundle(),
    ];
  }

  /**
   * Tests the entity.product_bundle.collection route as a user.
   *
   * @covers \Drupal\apigee_m10n\Entity\ListBuilder\ProductBundleListBuilder
   */
  public function testPage() {
    $this->assertAsAuthenticated();
    $this->assertAsAdmin();
  }

  /**
   * Checks access denied as a user without permissions.
   *
   * @throws \Exception
   */
  public function assertAsAuthenticated() {
    $this->setCurrentUser($this->authenticated);

    $request = Request::create(
      Url::fromRoute('entity.product_bundle.collection')->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);

    // Test the response.
    $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
  }

  /**
   * Checks access and response as a user with permissions.
   *
   * @throws \Exception
   */
  public function assertAsAdmin() {
    $this->setCurrentUser($this->admin);

    // Queue the packages response.
    // We need the "decorated" product bundles,
    // as we need the detailed apiProducts objects for the mock.
    $product_bundles = [];
    foreach ($this->product_bundles as $i => $product_bundle) {
      $product_bundles[$i] = $product_bundle->decorated();
    }
    $this->stack->queueMockResponse(['get_monetization_packages' => ['packages' => $product_bundles]]);
    $this->stack->queueMockResponse(['get_monetization_packages' => ['packages' => $product_bundles]]);

    $request = Request::create(Url::fromRoute('entity.product_bundle.collection')->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);
    $this->setRawContent($response->getContent());

    // Test the response.
    $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

    // Checking product bundle exists in table.
    foreach ($this->product_bundles as $product_bundle) {
      $this->assertText($product_bundle->id(), 'Product bundle ID exists.');

      // Checking individual product exists in table.
      foreach ($product_bundle->get('apiProducts') as $delta => $value) {
        $this->assertText($value->entity->label(), 'Product with this name exists.');
      }
    }
  }

}
