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

use Apigee\Edge\Structure\AttributesProperty;
use Drupal\apigee_edge\Entity\Developer;
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
   * Tests the Apigee Edge developer response.
   *
   * @throws \Http\Client\Exception
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function testDeveloperResponse() {
    $email = "{$this->randomMachineName()}@example.com";
    $developer = Developer::create([
      'developerId' => $email,
      'email' => $email,
      'firstName' => $this->randomMachineName(),
      'lastName' => $this->randomMachineName(),
      'userName' => $this->randomMachineName(),
      'status' => 'ACTIVE',
    ]);
    $developer->setAttributes(new AttributesProperty([
      'MINT_BILLING_TYPE' => 'foo',
      'MINT_DEVELOPER_TYPE' => 'bar',
      'MINT_DEVELOPER_LEGAL_NAME' => 'baz',
    ]));

    $this->stack->queueMockResponse(['developer' => ['developer' => $developer]]);

    // Execute a client call.
    $response = $this->sdk_connector->buildClient(new AutoBasicAuth())->get('/');

    self::assertEquals("200", $response->getStatusCode());

    $developer_array = json_decode($response->getBody(), TRUE);
    static::assertSame($developer->id(), $developer_array['developerId']);
    static::assertSame($developer->getEmail(), $developer_array['email']);
    static::assertSame($developer->getFirstName(), $developer_array['firstName']);
    static::assertSame($developer->getLastName(), $developer_array['lastName']);
    static::assertSame($developer->getUserName(), $developer_array['userName']);
    static::assertSame($developer->getStatus(), strtoupper($developer_array['status']));
    static::assertSame($developer->getAttributeValue('MINT_BILLING_TYPE'), $developer_array["attributes"][0]["value"]);
    static::assertSame($developer->getAttributeValue('MINT_DEVELOPER_TYPE'), $developer_array["attributes"][1]["value"]);
    static::assertSame($developer->getAttributeValue('MINT_DEVELOPER_LEGAL_NAME'), $developer_array["attributes"][2]["value"]);
  }

  /**
   * Tests that the sdk client will be unaffected while integration is enabled.
   */
  public function testIntegrationToggle() {
    $this->container->set('apigee_mock_client.mock_http_client_factory', NULL);
    putenv('APIGEE_INTEGRATION_ENABLE=0');
    $handler = $this->container
      ->get('apigee_mock_client.mock_http_client_factory')
      ->fromOptions([])
      ->getConfig('handler');

    self::assertInstanceOf(MockHandler::class, $handler);

    $this->container->set('apigee_mock_client.mock_http_client_factory', NULL);
    putenv('APIGEE_INTEGRATION_ENABLE=1');

    $handler = $this->container
      ->get('apigee_mock_client.mock_http_client_factory')
      ->fromOptions([])
      ->getConfig('handler');

    self::assertInstanceOf(HandlerStack::class, $handler);
    putenv('APIGEE_INTEGRATION_ENABLE=' . $this->integration_enabled ? 1 : 0);
  }

}
