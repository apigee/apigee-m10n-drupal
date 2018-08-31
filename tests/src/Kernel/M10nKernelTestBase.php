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

use Drupal\Component\Serialization\Json;
use Drupal\KernelTests\KernelTestBase;
use Drupal\key\Entity\Key;

class M10nKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'key',
    'file',
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
   * @var Drupal\apigee_edge\SDKConnectorInterface
   *
   * The SDK Connector client which should have it's http client stack replaced
   * with our mock.
   */
  protected $sdk_connector;

  /**
   * Authentication key used by SDK Connector
   *
   * @var Drupal\key\Entity\Key
   */
  private $auth_key;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create new Apigee Edge basic auth key with private file provider.
    $key = Key::create([
      'id'           => 'apigee_m10n_test_auth',
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

    $this->auth_key = $key;

    $this->container->get('state')
                    ->set('apigee_edge.auth', ['active_key'             => 'apigee_m10n_test_auth',
                                               'active_key_oauth_token' => '',
                    ]);

    $this->stack         = $this->container->get('apigee_m10n_test.mock_http_handler_stack');
    $this->sdk_connector = $this->container->get('apigee_edge.sdk_connector');

    $this->sdk_connector->getClient();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->auth_key->delete();

    parent::tearDown();
  }

}