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

namespace Drupal\Tests\apigee_m10n\Kernel;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Http\Message\Authentication\AutoBasicAuth;

/**
 * Tests the testing framework for testing offline.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class TestFrameworkKernelTest extends MonetizationKernelTestBase {

  /**
   * The SDK Connector.
   *
   * A client which should have it's http client stack replaced with our mock.
   *
   * @var \Apigee\Edge\ClientInterface
   */
  protected $sdk_client;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    if (!$this->integration_enabled) {
      $this->sdk_client = $this->sdk_connector->buildClient(new AutoBasicAuth());
    }
  }

  /**
   * Tests that the service override is working properly.
   */
  public function testServiceModification() {
    self::assertEquals(
      (string) $this->container->getDefinition('apigee_edge.sdk_connector')->getArgument(0),
      'apigee_mock_client.mock_http_client_factory'
    );
  }

  /**
   * Tests that api responses can be queued.
   */
  public function testInlineResponseQueue() {
    if ($this->integration_enabled) {
      static::markTestSkipped('Only test the response queue when running offline tests.');
      return;
    }

    // Queue a response from the mock server.
    $this->stack->append(new Response(200, [], "{\"status\": \"success\"}"));

    // Execute a client call.
    $response = $this->sdk_connector->buildClient(new AutoBasicAuth())->get('/');

    self::assertEquals("200", $response->getStatusCode());
    self::assertEquals('{"status": "success"}', (string) $response->getBody());
  }

  /**
   * Tests that api responses can be queued from the default response file.
   */
  public function testResponseFile() {
    if ($this->integration_enabled) {
      static::markTestSkipped('Only test the response queue when running offline tests.');
      return;
    }
    // Queue a response from the mock server.
    $this->stack->queueMockResponse(['catalog_test']);

    // Execute a client call.
    $response = $this->sdk_connector->buildClient(new AutoBasicAuth())->get('/');

    self::assertEquals("200", $response->getStatusCode());
    self::assertEquals('{"status": "success", "catalog_version": "1.0"}', (string) $response->getBody());
  }

  /**
   * Tests that the sdk client will be unaffected while integration is enabled.
   */
  public function testIntegrationToggle() {
    $handler = $this->container
      ->get('apigee_mock_client.mock_http_client_factory')
      ->fromOptions([])
      ->getConfig('handler');

    if ($this->integration_enabled) {
      self::assertTrue(($handler instanceof HandlerStack));
    }
    else {
      self::assertTrue(($handler instanceof MockHandler));
    }
  }

}
