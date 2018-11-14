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

namespace Drupal\Tests\apigee_m10n\Traits;

use Drupal\apigee_m10n\EnvironmentVariable;
use Drupal\Component\Serialization\Json;
use Drupal\key\Entity\Key;
use Drupal\Tests\apigee_edge\Functional\ApigeeEdgeTestTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Setup helpers for monetization tests.
 */
trait ApigeeMonetizationTestTrait {

  use ApigeeEdgeTestTrait {
    setUp as edgeSetup;
    createAccount as edgeCreateAccount;
  }

  /**
   * The mock handler stack is responsible for serving queued api responses.
   *
   * @var \Drupal\apigee_mock_client\MockHandlerStack
   */
  protected $stack;

  /**
   * The SDK Connector client.
   *
   * This will have it's http client stack replaced a mock stack.
   *
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  protected $sdk_connector;

  /**
   * {@inheritdoc}
   *
   * This prevents `edgeSetup` from being called unintentionally.
   */
  protected function setUp() {
    $this->init();
  }

  /**
   * Initialization.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function init() {
    $this->stack         = $this->container->get('apigee_mock_client.mock_http_handler_stack');
    $this->sdk_connector = $this->container->get('apigee_edge.sdk_connector');

    $this->initAuth();
  }

  /**
   * Initialize SDK connector.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function initAuth() {

    // Create new Apigee Edge basic auth key.
    $key = Key::create([
      'id'           => 'apigee_m10n_test_auth',
      'label'        => 'Apigee M10n Test Authorization',
      'key_type'     => 'apigee_edge_basic_auth',
      'key_provider' => 'config',
      'key_input'    => 'apigee_edge_basic_auth_input',
    ]);
    $key->setKeyValue(Json::encode([
      'endpoint'     => getenv(EnvironmentVariable::$APIGEE_EDGE_ENDPOINT),
      'organization' => getenv(EnvironmentVariable::$APIGEE_EDGE_ORGANIZATION),
      'username'     => getenv(EnvironmentVariable::$APIGEE_EDGE_USERNAME),
      'password'     => getenv(EnvironmentVariable::$APIGEE_EDGE_PASSWORD),
    ]));
    $key->save();

    $this->config('apigee_edge.auth')
      ->set('active_key', 'apigee_m10n_test_auth')
      ->set('active_key_oauth_token', '')
      ->save();
  }

  /**
   * Create an account.
   *
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

  /**
   * Queues up a mock developer response.
   *
   * @param \Drupal\user\UserInterface $developer
   *   The developer user to get properties from.
   * @param string|null $response_code
   *   Add a response code to override the default.
   *
   * @throws \Exception
   */
  protected function queueDeveloperResponse(UserInterface $developer, $response_code = NULL) {
    $context = empty($response_code) ? [] : ['status_code' => $response_code];

    $context['developer'] = $developer;
    $context['org_name'] = $this->sdk_connector->getOrganization();

    $this->stack->queueMockResponse(['get_developer' => $context]);
  }

  /**
   * Helper function to queue up an org response since every test will need it,.
   *
   * @param bool $monetized
   *   Whether or not the org is monetized.
   *
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  protected function queueOrg($monetized = TRUE) {
    $this->stack
      ->queueMockResponse(['get_organization' => ['monetization_enabled' => $monetized ? 'true' : 'false']]);
  }

  /**
   * Helper for testing element text by css selector.
   *
   * @param string $selector
   *   The css selector.
   * @param string $text
   *   The test to look for.
   *
   * @throws \Behat\Mink\Exception\ElementTextException
   */
  protected function assertCssElementContains($selector, $text) {
    $this->assertSession()->elementTextContains('css', $selector, $text);
  }

  /**
   * Helper for testing the lack of element text by css selector.
   *
   * @param string $selector
   *   The css selector.
   * @param string $text
   *   The test to look for.
   *
   * @throws \Behat\Mink\Exception\ElementTextException
   */
  protected function assertCssElementNotContains($selector, $text) {
    $this->assertSession()->elementTextNotContains('css', $selector, $text);
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

}
