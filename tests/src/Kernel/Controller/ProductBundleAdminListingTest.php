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

use Drupal\apigee_edge\Entity\ApiProduct;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
      'apigee_m10n',
    ]);

    // Create user 1 account - will not be used for tests.
    $this->user_1 = $this->createAccount([]);

    $this->admin = $this->createAccount(['administer apigee monetization', 'view product_bundle']);

    // Enable the Classy theme.
    \Drupal::service('theme_handler')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();

    $this->warmOrganizationCache();
//    \Drupal::service('apigee_m10n.monetization')->isMonetizationEnabled();

    $this->product_bundles = [
      $this->createProductBundle(),
      $this->createProductBundle(),
    ];

    // Some of the entities referenced will be loaded from the API unless we
    // warm the static cache with them.
//    $entity_static_cache = \Drupal::service('entity.memory_cache');
    foreach ($this->product_bundles as $product_bundle) {
      // Warm the static cache for each product bundle.
//      $entity_static_cache->set(
//        "values:product_bundle:{$product_bundle->id()}", $product_bundle
//      );
      // Warm the static cache for each product bundle product.
      /*foreach ($product_bundle->decorated()->getApiProducts() as $product) {
        $api_product = ApiProduct::create(
          [
            'id'          => $product->id(),
            'name'        => $product->getName(),
            'displayName' => $product->getDisplayName(),
            'description' => $product->getDescription(),
          ]
        );
        $this->stack->queueMockResponse(['api_product' => ['product' => $api_product]]);
        // Load the product_bundle drupal entity and warm the entity cache.
        $api_product_loaded = ApiProduct::load($product->id());
      }*/
    }
  }

  /**
   * Tests the entity.product_bundle.collection route as a user with access.
   */
  public function testAsAdmin() {
    $this->setCurrentUser($this->user_1);

    // Queue the product bundle response.
//    var_dump($this->product_bundles);
    $this->stack->queueMockResponse(['get_monetization_packages' => ['packages' => $this->product_bundles]]);
//    $this->stack->queueMockResponse(['get_monetization_packages' => ['packages' => $this->product_bundles]]);

    /** @var \Drupal\apigee_m10n\Entity\ProductBundle $product_bundle */
    foreach ($this->product_bundles as $product_bundle) {
      foreach ($product_bundle->decorated()->getApiProducts() as $product) {
        $this->stack->queueMockResponse(['api_product' => ['product' => $product]]);
      }
    }

    $request = Request::create(Url::fromRoute('entity.product_bundle.collection')->toString(), 'GET');
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = $this->container->get('http_kernel');
    $response = $kernel->handle($request, HttpKernelInterface::MASTER_REQUEST, FALSE);

    $this->setRawContent($response->getContent());
var_dump($response->getContent());
    // Make sure user has access to the page.
    $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

    // Checking product bundle exists in table columns.
    foreach ($this->product_bundles as $product_bundle) {
      $this->assertText($product_bundle->id());
    }
  }

}
