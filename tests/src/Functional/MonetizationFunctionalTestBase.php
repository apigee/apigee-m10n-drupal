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

use Drupal\Component\Serialization\Json;
use Drupal\key\Entity\Key;
use Drupal\Tests\apigee_edge\Functional\ApigeeEdgeTestTrait;
use Drupal\Tests\apigee_m10n\MonetizationTestEnvironmentVariables;
use Drupal\Tests\BrowserTestBase;

class MonetizationFunctionalTestBase extends BrowserTestBase {

  use ApigeeEdgeTestTrait;

  protected static $modules = [
    'apigee_edge',
    'apigee_m10n',
    'apigee_mock_client',
    'system'
  ];

  /**
   * @var \Drupal\apigee_mock_client\MockHandlerStack
   *
   * The mock handler stack is responsible for serving queued api responses.
   */
  protected $stack;

  /**
   * Whether actual integration tests are enabled.
   * @var boolean
   */
  protected $integration_enabled;

  protected function setUp() {
    parent::setUp();

    $this->integration_enabled = !empty(getenv('APIGEE_INTEGRATION_ENABLE'));

    // Create new Apigee Edge basic auth key.
    $key = Key::create([
      'id'           => 'test',
      'label'        => 'Apigee M10n Test Authorization',
      'key_type'     => 'apigee_edge_basic_auth',
      'key_provider' => 'config',
      'key_input'    => 'apigee_edge_basic_auth_input',
    ]);
    $key->setKeyValue(Json::encode([
      'endpoint'     => getenv('APIGEE_EDGE_ENDPOINT'),
      'organization' => getenv('APIGEE_EDGE_ORGANIZATION'),
      'username'     => getenv('APIGEE_EDGE_USERNAME'),
      'password'     => getenv('APIGEE_EDGE_PASSWORD'),
    ]));
    $key->save();

    $this->container->get('state')->set('apigee_edge.auth', [
      'active_key' => 'test',
      'active_key_oauth_token' => '',
    ]);
    $this->container->get('state')->resetCache();

    $this->stack = $this->container->get('apigee_mock_client.mock_http_handler_stack');
  }

}

