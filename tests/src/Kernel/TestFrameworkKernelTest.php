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

namespace Drupal\Tests\apigee_m10n\Kernel;

use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Psr7\Response;
use Http\Message\Authentication\AutoBasicAuth;

/**
 * Tests the testing framework for testing offline.
 *
 * @group apigee_m10n
 */
class TestFrameworkKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'key',
    'apigee_edge',
    'apigee_m10n',
    'apigee_m10n_test',
    'system',
  ];

  /**
   * @var \Drupal\apigee_m10n_test\MockHandlerStack
   *
   * The mock handler stack is responsible for serving queued api responses.
   */
  protected $stack;

  /**
   * @var \Apigee\Edge\ClientInterface
   *
   * The SDK Connector client which should have it's http client stack replaced
   * with our mock.
   */
  protected $sdk_client;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->stack = $this->container->get('apigee_m10n_test.mock_http_handler_stack');
    // @todo: Fix `$sdk_connector->getClient` failure b/c key state is uninitialized.
    $this->sdk_client = $this->container->get('apigee_edge.sdk_connector')
      ->buildClient(new AutoBasicAuth());
  }

  /**
   * Tests that api responses can be queued.
   */
  public function testInlineResponseQueue() {
    // Queue a response from the mock server.
    $this->stack->append(new Response(200, [], "{\"status\": \"success\"}"));

    // Execute a client call.
    $response = $this->sdk_client->get('/');

    self::assertEquals("200", $response->getStatusCode());
    self::assertEquals('{"status": "success"}', (string) $response->getBody());
  }

  /**
   * Tests that api responses can be queued from the default response file.
   */
  public function testResponseFile() {
    // Queue a response from the mock server.
    $this->stack->queueFromResponseFile(['catalog_test']);

    // Execute a client call.
    $response = $this->sdk_client->get('/');

    self::assertEquals("200", $response->getStatusCode());
    self::assertEquals('{"status": "success", "catalog_version": "1.0"}', (string) $response->getBody());
  }

}
