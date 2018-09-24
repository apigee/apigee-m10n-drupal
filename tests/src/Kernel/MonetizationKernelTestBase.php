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
use Drupal\Tests\apigee_edge\Functional\ApigeeEdgeTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use GuzzleHttp\Psr7\Response;

class MonetizationKernelTestBase extends KernelTestBase {

  public static $APIGEE_EDGE_ENDPOINT       = 'APIGEE_EDGE_ENDPOINT';
  public static $APIGEE_EDGE_ORGANIZATION   = 'APIGEE_EDGE_ORGANIZATION';
  public static $APIGEE_EDGE_USERNAME       = 'APIGEE_EDGE_USERNAME';
  public static $APIGEE_EDGE_PASSWORD       = 'APIGEE_EDGE_PASSWORD';
  public static $APIGEE_INTEGRATION_ENABLE  = 'APIGEE_INTEGRATION_ENABLE';

  use ApigeeEdgeTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'key',
    'file',
    'apigee_edge',
    'apigee_m10n',
    'apigee_mock_client',
    'user',
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
   * Whether actual integration tests are enabled.
   * @var boolean
   */
  protected $integration_enabled;

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();

    $this->integration_enabled = !empty(getenv(static::$APIGEE_INTEGRATION_ENABLE));

    // Create new Apigee Edge basic auth key.
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

    $this->container->get('state')->set('apigee_edge.auth', [
      'active_key' => 'apigee_m10n_test_auth',
      'active_key_oauth_token' => '',
    ]);

    $this->stack         = $this->container->get('apigee_mock_client.mock_http_handler_stack');
    $this->sdk_connector = $this->container->get('apigee_edge.sdk_connector');
  }

  /**
   * Makes sure no HTTP Client exceptions have been logged.
   */
  public function assertNoClientError() {
    $exceptions = $this->sdk_connector->getClient()->getJournal()->getLastException();
    static::assertEmpty(
      $exceptions,
      'A HTTP error has been logged in the Journal.'
    );
  }

  /**
   * Queues up a mock developer response.
   *
   * @param \Drupal\user\UserInterface $developer
   *   The developer user to get properties from.
   * @param null $response_code
   *   Add a response code to override the default.
   */
  protected function queueDeveloperResponse(UserInterface $developer, $response_code = NULL) {
    $replacements = empty($response_code) ? [] : ['status_code' => $response_code];

    $replacements[':email']       = $developer->getEmail();
    $replacements[':developerId'] = $developer->uuid();
    $replacements[':firstName']   = $developer->first_name->value;
    $replacements[':lastName']    = $developer->last_name->value;
    $replacements[':userName']    = $developer->getAccountName();

    $this->stack->queueFromResponseFile(['get_developer' => $replacements]);
  }

  /**
   * We override this function from `ApigeeEdgeTestTrait` so we can queue the
   * appropriate response upon account creation.
   *
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function createAccount(array $permissions = [], bool $status = TRUE, string $prefix = ''): ?UserInterface {
    $rid = NULL;
    if ($permissions) {
      $rid = $this->createRole($permissions);
      $this->assertTrue($rid, 'Role created');
    }

    $edit = [
      'first_name' => $this->randomMachineName(),
      'last_name' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
      'pass' => user_password(),
      'status' => $status,
    ];
    if ($rid) {
      $edit['roles'][] = $rid;
    }
    if ($prefix) {
      $edit['mail'] = "{$prefix}.{$edit['name']}@example.com";
    }
    else {
      $edit['mail'] = "{$edit['name']}@example.com";
    }

    $account = User::create($edit);

    // Queue up a created response.
    $this->queueDeveloperResponse($account, 201);
    $this->stack->append(new Response(204, [], ''));

    // Save the user.
    $account->save();

    $this->assertTrue($account->id(), 'User created.');
    if (!$account->id()) {
      return NULL;
    }

    // This is here to make drupalLogin() work.
    $account->passRaw = $edit['pass'];

    return $account;
  }
}
