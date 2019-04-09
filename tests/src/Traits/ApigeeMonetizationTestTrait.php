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

namespace Drupal\Tests\apigee_m10n\Traits;

use Apigee\Edge\Api\Monetization\Controller\OrganizationProfileController;
use Apigee\Edge\Api\Monetization\Controller\SupportedCurrencyController;
use Apigee\Edge\Api\Monetization\Entity\ApiPackage;
use Apigee\Edge\Api\Monetization\Entity\ApiProduct as MonetizationApiProduct;
use Apigee\Edge\Api\Monetization\Entity\Developer;
use Apigee\Edge\Api\Monetization\Entity\Property\FreemiumPropertiesInterface;
use Apigee\Edge\Api\Monetization\Structure\RatePlanDetail;
use Apigee\Edge\Api\Monetization\Structure\RatePlanRateRateCard;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Drupal\apigee_edge\Entity\ApiProduct;
use Drupal\apigee_m10n\Entity\Package;
use Drupal\apigee_m10n\Entity\PackageInterface;
use Drupal\apigee_m10n\Entity\RatePlan;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\apigee_m10n\Entity\Subscription;
use Drupal\apigee_m10n\Entity\SubscriptionInterface;
use Drupal\apigee_m10n\EnvironmentVariable;
use Drupal\apigee_m10n_test\Plugin\KeyProvider\TestEnvironmentVariablesKeyProvider;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\key\Entity\Key;
use Drupal\Tests\apigee_edge\Traits\ApigeeEdgeTestTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Prophecy\Argument;

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
   * mock.
   *
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  protected $sdk_connector;

  /**
   * The SDK controller factory.
   *
   * @var \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface
   */
  protected $controller_factory;

  /**
   * The clean up queue.
   *
   * @var array
   *   An associative array with a `callback` and a `weight` key. Some items
   *   will need to be called before others which is the reason for the weight
   *   system.
   */
  protected $cleanup_queue;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    $this->integration_enabled = !empty(getenv(EnvironmentVariable::APIGEE_INTEGRATION_ENABLE));
    $this->stack               = $this->container->get('apigee_mock_client.mock_http_handler_stack');
    $this->sdk_connector       = $this->container->get('apigee_edge.sdk_connector');

    $this->initAuth();
    // `::initAuth` has to happen before getting the controller factory.
    $this->controller_factory = $this->container->get('apigee_m10n.sdk_controller_factory');
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
      'key_type'     => 'apigee_auth',
      'key_provider' => 'apigee_edge_environment_variables',
      'key_input'    => 'apigee_auth_input',
    ]);

    $key->save();
    // Make sure the credentials persists for functional tests.
    \Drupal::state()->set(TestEnvironmentVariablesKeyProvider::KEY_VALUE_STATE_ID, $key->getKeyValue());

    $this->config('apigee_edge.auth')
      ->set('active_key', 'apigee_m10n_test_auth')
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
      'pass' => \user_password(),
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

    // Assume the account has no subscriptions initially.
    $this->warmSubscriptionsCache($account);
    \Drupal::cache()->set("apigee_m10n:dev:subscriptions:{$account->getEmail()}", []);

    $this->cleanup_queue[] = [
      'weight' => 99,
      // Prepare for deleting the developer.
      'callback' => function () use ($account) {
        $this->queueDeveloperResponse($account);
        $this->queueDeveloperResponse($account);
        // Delete it.
        $account->delete();
      },
    ];

    return $account;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function createProduct(): MonetizationApiProduct {
    /** @var \Drupal\apigee_edge\Entity\ApiProduct $product */
    $product = ApiProduct::create([
      'id'            => strtolower($this->randomMachineName()),
      'name'          => $this->randomMachineName(),
      'description'   => $this->getRandomGenerator()->sentences(3),
      'displayName'   => $this->getRandomGenerator()->word(16),
      'approvalType'  => ApiProduct::APPROVAL_TYPE_AUTO,
    ]);
    // Need to queue the management spi product.
    $this->stack->queueMockResponse(['api_product' => ['product' => $product]]);
    $product->save();

    // Remove the product in the cleanup queue.
    $this->cleanup_queue[] = [
      'weight' => 20,
      'callback' => function () use ($product) {
        $this->stack->queueMockResponse(['api_product' => ['product' => $product]]);
        $product->delete();
      },
    ];

    // Queue another response for the entity load.
    $this->stack->queueMockResponse(['api_product_mint' => ['product' => $product]]);
    $controller = $this->controller_factory->apiProductController();

    return $controller->load($product->getName());
  }

  /**
   * Create an API package.
   *
   * @throws \Exception
   */
  protected function createPackage(): PackageInterface {
    $products = [];
    for ($i = rand(1, 4); $i > 0; $i--) {
      $products[] = $this->createProduct();
    }

    $package = new ApiPackage([
      'name'        => $this->randomMachineName(),
      'description' => $this->getRandomGenerator()->sentences(3),
      'displayName' => $this->getRandomGenerator()->word(16),
      'apiProducts' => $products,
      // CREATED, ACTIVE, INACTIVE.
      'status'      => 'CREATED',
    ]);
    // Get a package controller from the package controller factory.
    $package_controller = $this->controller_factory->apiPackageController();
    $this->stack
      ->queueMockResponse(['get_monetization_package' => ['package' => $package]]);
    $package_controller->create($package);

    // Remove the packages in the cleanup queue.
    $this->cleanup_queue[] = [
      'weight' => 10,
      'callback' => function () use ($package, $package_controller) {
        $this->stack
          ->queueMockResponse(['get_monetization_package' => ['package' => $package]]);
        $package_controller->delete($package->id());
      },
    ];

    $this->stack->queueMockResponse(['package' => ['package' => $package]]);
    // Load the package drupal entity and warm the cache.
    return Package::load($package->id());
  }

  /**
   * Create a package rate plan for a given package.
   *
   * @param \Drupal\apigee_m10n\Entity\PackageInterface $package
   *   The rate plan package.
   *
   * @return \Drupal\apigee_m10n\Entity\RatePlanInterface
   *   A rate plan entity.
   *
   * @throws \Exception
   */
  protected function createPackageRatePlan(PackageInterface $package): RatePlanInterface {
    $client = $this->sdk_connector->getClient();
    $org_name = $this->sdk_connector->getOrganization();

    // Load the org profile.
    $org_controller = new OrganizationProfileController($org_name, $client);
    $this->stack->queueMockResponse('get_organization_profile');
    $org = $org_controller->load();

    // The usd currency should be available by default.
    $currency_controller = new SupportedCurrencyController($org_name, $this->sdk_connector->getClient());
    $this->stack->queueMockResponse('get_supported_currency');
    $currency = $currency_controller->load('usd');

    $rate_plan_rate = new RatePlanRateRateCard([
      'id'        => strtolower($this->randomMachineName()),
      'rate'      => rand(5, 20),
    ]);
    $rate_plan_rate->setStartUnit(1);

    /** @var \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan */
    $rate_plan = RatePlan::create([
      'advance'               => TRUE,
      'customPaymentTerm'     => TRUE,
      'description'           => $this->getRandomGenerator()->sentences(3),
      'displayName'           => $this->getRandomGenerator()->word(16),
      'earlyTerminationFee'   => '2.0000',
      'endDate'               => new \DateTimeImmutable('now + 1 year'),
      'frequencyDuration'     => 1,
      'frequencyDurationType' => FreemiumPropertiesInterface::FREEMIUM_DURATION_MONTH,
      'freemiumUnit'          => 1,
      'id'                    => strtolower($this->randomMachineName()),
      'isPrivate'             => 'false',
      'name'                  => $this->randomMachineName(),
      'paymentDueDays'        => '30',
      'prorate'               => FALSE,
      'published'             => TRUE,
      'ratePlanDetails'       => [
        new RatePlanDetail([
          "aggregateFreemiumCounters" => TRUE,
          "aggregateStandardCounters" => TRUE,
          "aggregateTransactions"     => TRUE,
          'currency'                  => $currency,
          "customPaymentTerm"         => TRUE,
          "duration"                  => 1,
          "durationType"              => "MONTH",
          "freemiumDuration"          => 1,
          "freemiumDurationType"      => "MONTH",
          "freemiumUnit"              => 110,
          "id"                        => strtolower($this->randomMachineName(16)),
          "meteringType"              => "UNIT",
          'org'                       => $org,
          "paymentDueDays"            => "30",
          'ratePlanRates'             => [$rate_plan_rate],
          "ratingParameter"           => "VOLUME",
          "type"                      => "RATECARD",
        ]),
      ],
      'recurringFee'          => '3.0000',
      'recurringStartUnit'    => '1',
      'recurringType'         => 'CALENDAR',
      'setUpFee'              => '1.0000',
      'startDate'             => new \DateTimeImmutable('2018-07-26 00:00:00'),
      'type'                  => 'STANDARD',
      'organization'          => $org,
      'currency'              => $currency,
      'package'               => $package->decorated(),
      'subscribe'             => [],
    ]);

    $this->stack->queueMockResponse(['rate_plan' => ['plan' => $rate_plan]]);
    $rate_plan->save();

    // Warm the cache.
    $this->stack->queueMockResponse(['rate_plan' => ['plan' => $rate_plan]]);
    $rate_plan = RatePlan::loadById($package->id(), $rate_plan->id());

    // Remove the rate plan in the cleanup queue.
    $this->cleanup_queue[] = [
      'weight' => 9,
      'callback' => function () use ($rate_plan) {
        $this->stack->queueMockResponse('no_content');
        $rate_plan->delete();
      },
    ];

    return $rate_plan;
  }

  /**
   * Creates a subscription.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to subscribe to the rate plan.
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan
   *   The rate plan to subscribe to.
   *
   * @return \Drupal\apigee_m10n\Entity\SubscriptionInterface
   *   The subscription.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  protected function createSubscription(UserInterface $user, RatePlanInterface $rate_plan): SubscriptionInterface {
    $subscription = Subscription::create([
      'ratePlan' => $rate_plan,
      'developer' => new Developer([
        'email' => $user->getEmail(),
        'name' => $user->getDisplayName(),
      ]),
      'startDate' => new \DateTimeImmutable(),
    ]);

    $this->stack->queueMockResponse(['subscription' => ['subscription' => $subscription]]);
    $subscription->save();

    // Warm the cache for this subscription.
    $subscription->set('id', $this->getRandomUniqueId());
    $this->stack->queueMockResponse(['subscription' => ['subscription' => $subscription]]);
    $subscription = Subscription::load($subscription->id());

    // The subscription controller does not have a delete operation so there is
    // nothing to add to the cleanup queue.
    return $subscription;
  }

  /**
   * Populates the subscriptions cache for a user.
   *
   * Use this for tests that fetch subscriptions.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @see \Drupal\apigee_m10n\Monetization::isDeveloperAlreadySubscribed()
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  protected function warmSubscriptionsCache(UserInterface $user): void {
    \Drupal::cache()->set("apigee_m10n:dev:subscriptions:{$user->getEmail()}", []);
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
   * @throws \Exception
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

  /**
   * Performs cleanup tasks after each individual test method has been run.
   */
  protected function tearDown() {
    if (!empty($this->cleanup_queue)) {
      $errors = [];
      // Sort all callbacks by weight. Lower weights will be executed first.
      usort($this->cleanup_queue, function ($a, $b) {
        return ($a['weight'] === $b['weight']) ? 0 : (($a['weight'] < $b['weight']) ? -1 : 1);
      });
      // Loop through the queue and execute callbacks.
      foreach ($this->cleanup_queue as $claim) {
        try {
          $claim['callback']();
        }
        catch (\Exception $ex) {
          $errors[] = $ex;
        }
      }

      parent::tearDown();

      if (!empty($errors)) {
        throw new \Exception('Errors found while processing the cleanup queue', 0, reset($errors));
      }
    }
  }

  /**
   * Helper to current response code equals to provided one.
   *
   * @param int $code
   *   The expected status code.
   */
  protected function assertStatusCodeEquals($code) {
    $this->checkDriverHeaderSupport();

    $this->assertSession()->statusCodeEquals($code);
  }

  /**
   * Helper to check headers.
   *
   * @param mixed $expected
   *   The expected header.
   * @param mixed $actual
   *   The actual header.
   * @param string $message
   *   The message.
   */
  protected function assertHeaderEquals($expected, $actual, $message = '') {
    $this->checkDriverHeaderSupport();

    $this->assertEquals($expected, $actual, $message);
  }

  /**
   * Checks if the driver supports headers.
   */
  protected function checkDriverHeaderSupport() {
    try {
      $this->getSession()->getResponseHeaders();
    }
    catch (UnsupportedDriverActionException $exception) {
      $this->markTestSkipped($exception->getMessage());
    }
  }

  /**
   * Set the current user to a mock.
   *
   * @param array $permissions
   *   An array of permissions the current user should have.
   *
   *   If this permissions array is empty we assume the current user should be
   *   a root user.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   The current user.
   */
  protected function mockCurrentUser($permissions = []) {
    // Set the current user to a mock.
    $account = $this->prophesize(AccountProxyInterface::class);
    $account->id()->willReturn(empty($permissions) ? 1 : 2);
    $account->getEmail()->willReturn("{$this->randomMachineName()}@example.com");
    $account->isAnonymous()->willReturn(FALSE);
    $account->isAuthenticated()->willReturn(TRUE);
    $account->getUsername()->willReturn($this->getRandomGenerator()->word(8));
    $account->getAccountName()->willReturn($this->getRandomGenerator()->word(8));
    $account->getDisplayName()->willReturn($this->getRandomGenerator()->word(8) . ' ' . $this->getRandomGenerator()->word(12));
    // Handle permissions array.
    $account->hasPermission(Argument::any())->will(function ($args) use ($permissions) {
      // Assume an empty permissions array means the root user.
      return empty($permissions) ? TRUE : in_array($args[0], $permissions);
    });
    $this->container->set('current_user', $account->reveal());

    return \Drupal::currentUser();
  }

}
