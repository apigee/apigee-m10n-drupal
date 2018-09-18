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

class MometizationKernelTestBase extends KernelTestBase {

  public static $APIGEE_EDGE_ENDPOINT       = 'APIGEE_EDGE_ENDPOINT';
  public static $APIGEE_EDGE_ORGANIZATION   = 'APIGEE_EDGE_ORGANIZATION';
  public static $APIGEE_EDGE_USERNAME       = 'APIGEE_EDGE_USERNAME';
  public static $APIGEE_EDGE_PASSWORD       = 'APIGEE_EDGE_PASSWORD';
  public static $APIGEE_INTEGRATION_ENABLE  = 'APIGEE_INTEGRATION_ENABLE';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'key',
    'file',
    'apigee_edge',
    'apigee_m10n',
    'apigee_mock_client',
    'system',
  ];

  /**
   * @var \Drupal\apigee_mock_client\MockHandlerStack
   *
   * The mock handler stack is responsible for serving queued api responses.
   */
  protected $stack;

  /**
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   *
   * The SDK Connector client which should have it's http client stack replaced
   * with our mock.
   */
  protected $sdk_connector;

  /**
   * Authentication key used by SDK Connector
   *
   * @var \Drupal\key\Entity\Key
   */
  private $auth_key;

  /**
   * Whether actual integration tests are enabled.
   * @var boolean
   */
  protected $integration_enabled;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->integration_enabled = !empty(getenv(static::$APIGEE_INTEGRATION_ENABLE));

    // Create new Apigee Edge basic auth key with private file provider.
    $key = Key::create([
      'id'           => 'apigee_m10n_test_auth',
      'label'        => 'Apigee M10n Test Authorization',
      'key_type'     => 'apigee_edge_basic_auth',
      'key_provider' => 'config',
      'key_input'    => 'apigee_edge_basic_auth_input',
    ]);
    $key->setKeyValue(Json::encode([
      'endpoint'     => getenv(static::$APIGEE_EDGE_ENDPOINT),
      'organization' => getenv(static::$APIGEE_EDGE_ORGANIZATION),
      'username'     => getenv(static::$APIGEE_EDGE_USERNAME),
      'password'     => getenv(static::$APIGEE_EDGE_PASSWORD),
    ]));
    $key->save();

    $this->auth_key = $key;

    $this->container->get('state')
                    ->set('apigee_edge.auth', ['active_key'             => 'apigee_m10n_test_auth',
                                               'active_key_oauth_token' => '',
                    ]);

    $this->stack         = $this->container->get('apigee_mock_client.mock_http_handler_stack');
    $this->sdk_connector = $this->container->get('apigee_edge.sdk_connector');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->auth_key->delete();

    parent::tearDown();
  }

}