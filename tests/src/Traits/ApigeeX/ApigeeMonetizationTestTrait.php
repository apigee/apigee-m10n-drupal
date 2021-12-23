<?php

/*
 * Copyright 2021 Google Inc.
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

namespace Drupal\Tests\apigee_m10n\Traits\ApigeeX;

use Apigee\Edge\Api\Management\Entity\DeveloperInterface;
use Apigee\Edge\Api\ApigeeX\Entity\ApiProduct as ApigeexApiProduct;
use Apigee\Edge\Api\ApigeeX\Structure\ConsumptionPricingRate;
use Apigee\Edge\Api\ApigeeX\Structure\Fee;
use Apigee\Edge\Api\ApigeeX\Structure\RatePlanXFee;
use Apigee\Edge\Api\ApigeeX\Structure\RevenueShareRates;
use Apigee\Edge\Api\Monetization\Entity\Developer;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Drupal\apigee_edge\Entity\ApiProduct;
use Drupal\apigee_m10n\Entity\XProduct;
use Drupal\apigee_m10n\Entity\XProductInterface;
use Drupal\apigee_edge\Entity\Developer as EdgeDeveloper;
use Drupal\apigee_edge\UserDeveloperConverterInterface;
use Drupal\apigee_edge\Plugin\EdgeKeyTypeInterface;
use Drupal\apigee_m10n\Entity\XRatePlan;
use Drupal\apigee_m10n\Entity\XRatePlanInterface;
use Drupal\apigee_m10n\Entity\PurchasedProduct;
use Drupal\apigee_m10n\Entity\PurchasedProductInterface;
use Drupal\apigee_m10n\EnvironmentVariable;
use Drupal\apigee_m10n_test\Plugin\KeyProvider\TestEnvironmentVariablesKeyProvider;
use Drupal\key\Entity\Key;
use Drupal\Tests\apigee_edge\Traits\ApigeeEdgeFunctionalTestTrait;
use Drupal\Tests\apigee_m10n\Traits\AccountProphecyTrait;
use Drupal\Tests\apigee_mock_api_client\Traits\ApigeeMockApiClientHelperTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Setup helpers for hybrid tests.
 */
trait ApigeeMonetizationTestTrait {

  use AccountProphecyTrait;
  use ApigeeEdgeFunctionalTestTrait {
    createAccount as edgeCreateAccount;
  }
  use ApigeeMockApiClientHelperTrait {
    ApigeeEdgeFunctionalTestTrait::createDeveloperApp insteadof ApigeeMockApiClientHelperTrait;
  }

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
   * The default org timezone.
   *
   * @var string
   */
  protected $org_default_timezone = 'America/Los_Angeles';

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp(): void {
    // Skipping the test if instance type is Public.
    $instance_type = getenv('APIGEE_EDGE_INSTANCE_TYPE');
    if (!empty($instance_type) && $instance_type === EdgeKeyTypeInterface::INSTANCE_TYPE_PUBLIC) {
      $this->markTestSkipped('This test suite is expecting a HYBRID instance type.');
    }

    $this->apigeeTestHelperSetup();
    $this->sdk_connector = $this->sdkConnector;

    // `::initAuth` in above has to happen before getting the controller factory.
    $this->controller_factory = $this->container->get('apigee_m10n.sdk_controller_factory');
  }

  /**
   * Create an account.
   *
   * We override this function from `ApigeeEdgeFunctionalTestTrait` so we can queue the
   * appropriate response upon account creation.
   *
   * {@inheritdoc}
   */
  protected function createAccount(array $permissions = [], bool $status = TRUE, string $prefix = '', $attributes = []): ?UserInterface {
    $rid = NULL;
    if ($permissions) {
      $rid = $this->createRole($permissions);
      $this->assertNotEmpty($rid, 'Role created');
    }

    // Apigee X developer email address should not be in upper-case.
    $edit = [
      'first_name' => $this->randomMachineName(),
      'last_name' => $this->randomMachineName(),
      'name' => strtolower($this->randomMachineName()),
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

    $billing_type = empty($attributes['billing_type']) ? NULL : $attributes['billing_type'];

    // Queue up a created response.
    $this->queueApigeexDeveloperResponse($account, 201, $billing_type);

    // Save the user.
    $account->save();

    $this->assertNotEmpty($account->id());
    if (!$account->id()) {
      return NULL;
    }

    // This is here to make drupalLogin() work.
    $account->passRaw = $edit['pass'];

    // Assume the account has no purchased plans initially.
    $this->warmPurchasedProductCache($account);

    $this->cleanup_queue[] = [
      'weight' => 99,
      // Prepare for deleting the developer.
      'callback' => function () use ($account, $billing_type) {
        try {
          // Delete it.
          $account->delete();
        }
        catch (\Exception $e) {
          // We only care about deleting from Edge, do nothing if exception
          // gets thrown if it couldn't delete remotely.
        }
        catch (\Error $e) {
          // We only care about deleting from Edge, do nothing if exception
          // gets thrown if it couldn't delete remotely.
        }
      },
    ];

    return $account;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function createProduct(): ApigeexApiProduct {
    /** @var \Drupal\apigee_edge\Entity\ApiProduct $product */
    $product = new ApigeexApiProduct([
      'name'          => $this->randomMachineName(),
      'description'   => $this->getRandomGenerator()->sentences(3),
      'displayName'   => $this->getRandomGenerator()->word(16),
      'approvalType'  => ApiProduct::APPROVAL_TYPE_AUTO
    ]);
    // Need to queue the management api product.
    $this->stack->queueMockResponse(['api_apigeex_product' => ['product' => $product]]);

    // Warm the entity static cache.
    \Drupal::service('entity.memory_cache')->set("values:api_apigeex_product:{$product->getName()}", $product);

    // Remove the product in the cleanup queue.
    $this->cleanup_queue[] = [
      'weight' => 20,
      'callback' => function () use ($product) {
        // $this->stack->queueMockResponse(['xpackage' => ['product' => $product]]);
        // $product->delete();
      },
    ];

    // Queue another response for the entity load.
    $this->stack->queueMockResponse(['api_apigeex_product' => ['product' => $product]]);
    $controller = $this->controller_factory->apixProductController();

    return $controller->load($product->getName());
  }

  /**
   * Create a Apigee X product.
   *
   * @throws \Exception
   */
  protected function createApigeexProduct(): XProductInterface {
    $product = NULL;
    $product = $this->createProduct();
    $this->stack->queueMockResponse(['api_apigeex_product' => ['product' => $product]]);
    $xpackage = new XProduct([
      'id'            => $product->id(),
      'name'          => $product->getName(),
      'description'   => $product->getDescription(),
      'displayName'   => $product->getDisplayName()
    ]);

    $this->stack->queueMockResponse(['xpackage' => ['product' => $xpackage]]);
    $xproduct = XProduct::load($xpackage->id());

    return $xproduct;
  }

  /**
   * Create a rate plan for a given product for ApigeeX.
   *
   * @param \Drupal\apigee_m10n\Entity\XProductInterface $xproduct
   *   The rate plan xproduct.
   * @param string $type
   *   The type of plan.
   * @param string $id
   *   The rate plan Id. It not set it will randomly generated.
   * @param array $properties
   *   Optional properties to set on the decorated object.
   *
   * @return \Drupal\apigee_m10n\Entity\XRatePlanInterface
   *   A rate plan entity.
   *
   * @throws \Exception
   */
  protected function createRatePlan(XProductInterface $xproduct, $type = XRatePlanInterface::TYPE_STANDARD, string $id = NULL, array $properties = []): XRatePlanInterface {
    $start_date = new \DateTimeImmutable('2018-07-26 00:00:00', new \DateTimeZone($this->org_default_timezone));
    $start_time = (int) ($start_date->getTimestamp() . $start_date->format('v'));
    $end_date = new \DateTimeImmutable('today +1 year', new \DateTimeZone($this->org_default_timezone));
    $end_time = (int) ($end_date->getTimestamp() . $end_date->format('v'));
    $properties += [
      'name'                 => $id ?: strtolower($this->randomMachineName()),
      'displayName'          => $this->getRandomGenerator()->word(16),
      'apiproduct'           => $xproduct->getName(),
      'billingPeriod'        => 'MONTHLY',
      'paymentFundingModel'  => 'POSTPAID',
      'currencyCode'         => 'USD',
      'ratePlanXFee'         => new RatePlanXFee([
        'currencyCode'  => 'USD',
        'units'         => '1',
        'nanos'         => 300000000
      ]),
      'consumptionPricingType'  => 'FIXED_PER_UNIT',
      'consumptionPricingRates' => new ConsumptionPricingRate([
        'fee' => new Fee(
            [
                'currencyCode'    => 'USD',
                'units'           => '1',
                'nanos'           => 100000000
            ]),
      ]),
      'revenueShareType'      => 'FIXED',
      'revenueShareRates'     => new RevenueShareRates([
          'sharePercentage' => 17.56
      ]),
      'state'                 => 'PUBLISHED',
      'startTime'             => $start_time,
      'endTime'               => $end_time,
      'package'               => $xproduct->decorated(),
    ];

    switch ($type) {
      case XRatePlanInterface::TYPE_DEVELOPER:
        $this->stack->queueMockResponse('access_token');
        $this->stack->queueMockResponse(['rate_plan_apigeex' => ['plan' => $properties]]);
        $xrate_plan = XRatePlan::loadById($xproduct->id(), $properties['name']);
        break;

      default:

        /** @var \Drupal\apigee_m10n\Entity\XRatePlanInterface $xrate_plan */
        $xrate_plan = XRatePlan::create($properties);
        $this->stack->queueMockResponse('access_token');
        $this->stack->queueMockResponse(['rate_plan_apigeex' => ['plan' => $xrate_plan]]);
        $xrate_plan->save();

        // Warm the cache.
        $this->stack->queueMockResponse(['rate_plan_apigeex' => ['plan' => $xrate_plan]]);
        $xrate_plan = XRatePlan::loadById($xproduct->id(), $xrate_plan->id());

        // Warm the plan cache.
        $this->stack->queueMockResponse(['get_monetization_apigeex_plans' => ['plans' => [$xrate_plan]]]);

        // Make sure the dates loaded the same as they were originally set.
        static::assertEquals($start_time, $xrate_plan->getStartTime());
        static::assertEquals($end_time, $xrate_plan->getEndTime());
    }

    // Remove the rate plan in the cleanup queue.
    $this->cleanup_queue[] = [
      'weight' => 9,
      'callback' => function () use ($xrate_plan) {
        $this->stack->queueMockResponse('access_token');
        $this->stack->queueMockResponse('no_content');
        $xrate_plan->delete();
      },
    ];

    return $xrate_plan;
  }

  /**
   * Creates a purchased product for Apigee X.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to purchase the x rate plan.
   * @param \Drupal\apigee_m10n\Entity\XRatePlanInterface $xrate_plan
   *   The rate plan to purchase.
   *
   * @return \Drupal\apigee_m10n\Entity\PurchasedProductInterface
   *   The purchased product.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  protected function createPurchasedProduct(UserInterface $user, XRatePlanInterface $xrate_plan): PurchasedProductInterface {
    // Get Current timestamp & Transform timeRange to the required format and time zone.
    $start_date = new \DateTimeImmutable('now', new \DateTimeZone($this->org_default_timezone));
    $start_time = (int) ($start_date->getTimestamp() . $start_date->format('v'));

    $purchased_product = PurchasedProduct::create([
      'name'            => strtolower($this->randomMachineName()),
      'apiproduct'      => $xrate_plan->getApiProduct(),
      'developer' => new Developer([
        'email' => $user->getEmail(),
        'name' => $user->getDisplayName(),
      ]),
      'startTime'       => $start_time
    ]);

    // Warm the purchased_plan.
    $this->stack->queueMockResponse('access_token');
    $this->stack->queueMockResponse(['purchased_product' => ['purchased_product' => $purchased_product]]);
    $purchased_product->save();

    // Warm the cache for this purchased_plan.
    $this->stack->queueMockResponse(['purchased_product' => ['purchased_product' => $purchased_product]]);
    $purchased_product = PurchasedProduct::load($purchased_product->getName());

    // Make sure the start date is unchanged while loading.
    static::assertEquals($start_time, $purchased_product->decorated()->getStartTime());

    $this->stack->queueMockResponse('access_token');
    // The purchased_product controller does not have a delete operation so there is
    // nothing to add to the cleanup queue.
    return $purchased_product;
  }

  /**
   * Populates the purchased product cache for a user.
   *
   * Use this for tests that fetch purchased products.
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
  protected function warmPurchasedProductCache(UserInterface $user): void {
    \Drupal::cache()->set("apigee_m10n:dev:purchased_products:{$user->getEmail()}", []);
  }

  /**
   * Queues up a mock developer response.
   *
   * @param \Drupal\user\UserInterface $developer
   *   The developer user to get properties from.
   * @param string|null $response_code
   *   Add a response code to override the default.
   * @param string|null $billing_type
   *   The developer billing type.
   */
  protected function queueApigeexDeveloperResponse(UserInterface $developer, $response_code = NULL, $billing_type = NULL) {
    $context = empty($response_code) ? [] : ['status_code' => $response_code];

    $context['developer'] = $developer;
    $context['org_name'] = $this->sdk_connector->getOrganization();

    if ($billing_type) {
      $context['billing_type'] = $billing_type;
    }

    $this->stack->queueMockResponse(['get_developer_apigeex' => $context]);
  }

  /**
   * Helper function to queue up an ApigeeX org response since every test will need it,.
   *
   * @param string $runtimetype
   *   Whether or not the org is hybrid or non-hybrid.
   * @param bool $monetized
   *   Whether or not the org is monetized.
   *
   * @throws \Exception
   */
  protected function warmApigeexOrganizationCache($runtimetype = 'CLOUD', $monetized = TRUE) {
    if (!(\Drupal::service('apigee_m10n.monetization')->getOrganization())) {
      $this->addApigeexOrganizationMatchedResponse();
    }
    $this->stack
      ->queueMockResponse([
        'get_apigeex_organization' => [
          'runtimetype' => $runtimetype,
          'monetization_enabled' => $monetized ? 'true' : 'false',
          'timezone' => $this->org_default_timezone,
        ],
      ]);
    \Drupal::service('apigee_m10n.monetization')->getOrganization();
  }

  /**
   * Helper for testing element text matches by css selector.
   *
   * @param string $selector
   *   The css selector.
   * @param string $text
   *   The test to look for.
   *
   * @throws \Behat\Mink\Exception\ElementTextException
   */
  protected function assertCssElementText($selector, $text) {
    static::assertSame(
      $this->getSession()->getPage()->find('css', $selector)->getText(),
      $text
    );
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
   * Set the billing type of the user.
   *
   * @throws \Exception
   */
  protected function setBillingType(UserInterface $user, $billingType = 'PREPAID') {
    $this->queueApigeexDeveloperResponse($user);

    \Drupal::service('apigee_m10n.monetization')->updateBillingtype($user->getEmail(), $billingType);

    $this->queueApigeexDeveloperResponse($user);
    $this->stack->queueMockResponse([
      'get-apigeex-billing-type' => [
        "billingType" => $billingType,
      ],
    ]);
  }

}
