<?php

/**
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

namespace Drupal\Tests\apigee_m10n\Kernel\ApigeeX\Controller;

use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Kernel\ApigeeX\MonetizationKernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ProductAdminListingTest.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class ProductAdminListingTest extends MonetizationKernelTestBase {

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
   * @var \Drupal\apigee_m10n\Entity\XProductInterface[]
   */
  protected $xproducts;

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

    // Create user 1 account - will not be used for tests.
    $this->createAccount([]);

    $this->authenticated = $this->createAccount();
    $this->admin = $this->createAccount([
      'administer apigee monetization',
    ]);

    // Get pre-configured token storage service for testing.
    $this->storeToken();

    $this->stack->reset();
    $this->xproducts[] = $this->createApigeexProduct();
    $this->stack->reset();
    $this->xproducts[] = $this->createApigeexProduct();

    $this->stack->reset();
    // Warm the ApigeeX organization.
    $this->warmApigeexOrganizationCache();
  }

  /**
   * Tests the entity.xproduct.collection route as a user.
   *
   * @covers \Drupal\apigee_m10n\Entity\ListBuilder\XProductListBuilder
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
      Url::fromRoute('entity.xproduct.collection')->toString(), 'GET');
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
    $this->stack->reset();

    // Queue the packages response.
    // We need the "decorated" apigee X product,
    // as we need the detailed apiProducts objects for the mock.
    $xproducts = [];
    foreach ($this->xproducts as $i => $xproduct) {
      $xproducts[$i] = $xproduct->decorated();
    }
    $this->stack->queueMockResponse(['get_apigeex_monetization_package' => ['xproducts' => $xproducts]]);
    $this->stack->queueMockResponse(['get_apigeex_monetization_package' => ['xproducts' => $xproducts]]);

    $request = Request::create(Url::fromRoute('entity.xproduct.collection')->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);
    $this->setRawContent($response->getContent());

    // Test the response.
    $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

    foreach ($this->xproducts as $i => $xproduct) {
      // Checking Apigee X product exists in table.
      $this->assertText($xproduct->id(), 'X Product ID exists.');
      $this->assertText($xproduct->getName(), 'Product with this name exists.');
    }
  }

}
