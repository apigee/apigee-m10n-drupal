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

use GuzzleHttp\Psr7\Response;

/**
 * Tests the testing framework for testing offline.
 *
 * @group apigee_m10n
 */
class MonetizationServiceKernelTest extends M10nKernelTestBase {

  /**
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->monetization = $this->container->get('apigee_m10n.monetization');
  }

  /**
   * Tests that monetization service correctly identifies a monetized
   * organization.
   */
  public function testMonetizationEnabled() {

    // Response body representing a monetized organization.
    $response_body = <<< EOF
      {
        "properties" : {
          "property" : [ {
              "name" : "features.isMonetizationEnabled",
              "value" : "true"
            }
          ]
        }
      }
EOF;

    // Queue a response from the mock server.
    $this->stack->append(new Response(200, [], $response_body));

    // Execute a client call.
    $is_monetization_enabled = $this->monetization->isMonetizationEnabled();


    self::assertEquals($is_monetization_enabled, TRUE);
  }

  /**
   * Tests that the monetization service correctly identifies an organization without monetization enabled.
   */
  public function testMonetizationDisabled() {

    if ($this->integration_enabled) {
      $this->markTestSkipped('This test suite is expecting a monetization enabled org. Disable for integration testing.');
    }
    // Response body representing a monetized organization.
    $response_body = <<< EOF
      {
        "properties" : {
          "property" : [ {
              "name" : "features.isMonetizationEnabled",
              "value" : "false"
            }
          ]
        }
      }
EOF;

    // Queue a response from the mock server.
    $this->stack->append(new Response(200, [], $response_body));

    // Execute a client call.
    $is_monetization_enabled = $this->monetization->isMonetizationEnabled();


    self::assertEquals($is_monetization_enabled, FALSE);
  }
}
