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

namespace Drupal\Tests\apigee_m10n\Kernel\ApigeeX\Entity;

use Drupal\apigee_edge\Entity\EdgeEntityType;
use Drupal\apigee_m10n\Entity\XProduct;
use Drupal\apigee_m10n\Entity\XProductInterface;
use Drupal\apigee_m10n\Entity\Routing\MonetizationEntityRouteProvider;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Kernel\ApigeeX\MonetizationKernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test the `xproduct` entity.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class ProductEntityKernelTest extends MonetizationKernelTestBase {

  /**
   * A test X product.
   *
   * @var \Drupal\apigee_m10n\Entity\XProductInterface
   */
  protected $xproduct;

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Get pre-configured token storage service for testing.
    $this->storeToken();

    $this->stack->reset();
    $this->xproduct = $this->createApigeexProduct();
    $this->stack->reset();
    static::assertInstanceOf(XProduct::class, $this->xproduct);

    // Prepare to create a user.
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);

    $this->user = $this->createAccount(array_keys($this->container->get('user.permissions')->getPermissions()));
    $this->setCurrentUser($this->user);
  }

  /**
   * Test loading a rate plan from drupal entity storage.
   *
   * @throws \Exception
   */
  public function testProductEntity() {
    $this->setCurrentRouteMatch();

    // Get the `xproduct` entity definition.
    $entity_type = $this->container->get('entity_type.manager')->getDefinition('xproduct');
    static::assertInstanceOf(EdgeEntityType::class, $entity_type);

    // Check that the entity class remains unchanged.
    static::assertSame(XProduct::class, $entity_type->getClass());

    // Make sure we are using our custom route provider.
    static::assertSame(MonetizationEntityRouteProvider::class, $entity_type->getRouteProviderClasses()['html']);

    // Test that xproduct canonical urls are redirecting to the developer
    // specific X product url.
    $request = Request::create(Url::fromRoute('entity.xproduct.canonical', ['xproduct' => $this->xproduct->id()])->toString(), 'GET');
    $response = $this->container->get('http_kernel')->handle($request);
    static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    static::assertSame("http://localhost/user/{$this->user->id()}/monetization/xproduct/{$this->xproduct->id()}", $response->headers->get('location'));

    // Make sure we get a team context when getting a product url.
    $url = $this->xproduct->toUrl('canonical');
    static::assertSame("/user/1/monetization/xproduct/{$this->xproduct->id()}", $url->toString());
    static::assertSame('entity.xproduct.developer', $url->getRouteName());

    // Load the cached product.
    $xproduct = XProduct::load($this->xproduct->id());

    static::assertInstanceOf(XProductInterface::class, $xproduct);
    static::assertSame(gettype($xproduct), gettype($this->xproduct));

    // Check properties.
    static::assertSame($this->xproduct->id(), $xproduct->id());
    static::assertSame($this->xproduct->getDisplayName(), $xproduct->getDisplayName());
    static::assertSame($this->xproduct->getName(), $xproduct->getName());
    static::assertSame($this->xproduct->getDescription(), $xproduct->getDescription());
  }

  /**
   * Helper to set up a mock current route match.
   */
  private function setCurrentRouteMatch() {
    // Create a request stack for the user page.
    $request = Request::create(Url::fromRoute('entity.user.canonical', ['user' => $this->user->id()])->toString(), 'GET');
    $route = $this->container->get('router.no_access_checks')->matchRequest($request);
    $request->attributes->replace($route);
    $request_stack = new RequestStack();
    $request_stack->push($request);
    $current_route_match = new CurrentRouteMatch($request_stack);
    // Set the current route match to the current user route.
    $this->container->set('current_route_match', $current_route_match);
  }

}
