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

namespace Drupal\Tests\apigee_m10n_add_credit\Kernel;

use Apigee\Edge\Api\Monetization\Entity\Developer;
use Drupal\apigee_edge\Job\Job;
use Drupal\apigee_edge\Job\JobCreatorTrait;
use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\apigee_m10n_add_credit\Form\AddCreditConfigForm;
use Drupal\apigee_m10n_add_credit\Job\BalanceAdjustmentJob;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the testing framework for testing offline.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_kernel
 */
class BalanceAdjustmentJobKernelTest extends MonetizationKernelTestBase {

  use JobCreatorTrait;

  /**
   * The developer.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * A test site email address.
   *
   * @var string
   */
  protected $site_mail;

  /**
   * The prepaid balance controller.
   *
   * @var \Apigee\Edge\Api\Monetization\Controller\DeveloperPrepaidBalanceController
   */
  protected $balance_controller;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Base modules.
    'key',
    'file',
    'apigee_edge',
    'apigee_m10n',
    'apigee_mock_client',
    'system',
    // Modules for this test.
    'apigee_m10n_add_credit',
    'commerce_order',
    'commerce_price',
    'commerce',
    'user',
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('apigee_edge', ['apigee_edge_job']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'apigee_m10n_add_credit',
      'user',
      'system',
    ]);
    $this->installEntitySchema('user');
    \Drupal::service('commerce_price.currency_importer')->importByCountry('US');

    $this->developer = $this->createAccount();

    $this->assertNoClientError();

    $this->balance_controller = \Drupal::service('apigee_m10n.sdk_controller_factory')
      ->developerBalanceController($this->developer);
    $this->site_mail = $this->randomMachineName() . '@exmaple.com';
    $this->config('system.site')
      ->set('mail', $this->site_mail)
      ->set('name', 'example site')
      ->save();
  }

  /**
   * Tests the job will update the developer balance.
   *
   * @throws \Exception
   */
  public function testExecuteRequest() {
    // Create a new job update the account balance. Use a custom adjustment
    // type because it can support a credit or a debit.
    $job = new BalanceAdjustmentJob($this->developer, new Adjustment([
      'type' => 'apigee_balance',
      'label' => 'Apigee credit adjustment',
      'amount' => new Price('19.99', 'USD'),
    ]));

    // Save the job.
    $this->scheduleJob($job);
    static::assertSame(Job::IDLE, $job->getStatus());

    $this->stack
      // Queue an empty balance response because this is what you get with a new
      // user.
      ->queueMockResponse('get_prepaid_balances_empty')
      // Queue a developer balance response for the top up (POST).
      ->queueMockResponse(['post_developer_balances' => ['amount' => '19.99']])
      // Queue an updated balance response.
      ->queueMockResponse([
        'get_prepaid_balances' => [
          'amount_usd' => '19.99',
          'topups_usd' => '19.99',
          'current_usage_usd' => '0',
        ],
      ]);

    // Execute the job which will update the developer balance.
    $this->getExecutor()->call($job);
    static::assertSame(Job::FINISHED, $job->getStatus());

    // The new balance will be re-read so queue the response.
    $this->stack->queueMockResponse([
      'get_developer_balances' => [
        'amount_usd' => '19.99',
        'developer' => new Developer([
          'email' => $this->developer->getEmail(),
          'uuid' => \Drupal::service('uuid')->generate(),
        ]),
      ],
    ]);
    $new_balance = $this->balance_controller->getByCurrency('USD');
    // The new balance should be 19.99.
    static::assertSame(19.99, $new_balance->getAmount());

    $this->assertNoClientError();
    static::assertEmpty($this->stack->count());

    // Make sure no emails were sent.
    $emails = \Drupal::state()->get('system.test_mail_collector');
    static::assertEmpty($emails, 'No emails were sent.');
  }

  /**
   * Test the request when there is an existing balance.
   *
   * Run the test again with the same users to test adding to an existing
   * balance. This would normally run as a separate test but that would create
   * another user that cannot be removed because of holding a developer
   * balance. Err: `Cannot delete Developer [xxxxx] used in Developer_Balance`.
   *
   * @throws \Exception
   */
  public function testExecuteRequestWithExistingBalance() {
    $job = new BalanceAdjustmentJob($this->developer, new Adjustment([
      'type' => 'apigee_balance',
      'label' => 'Apigee credit adjustment',
      'amount' => new Price('19.99', 'USD'),
    ]));

    // Save the job.
    $this->scheduleJob($job);
    static::assertSame(Job::IDLE, $job->getStatus());

    $this->stack
      // We should now have an existing balance of 19.99.
      ->queueMockResponse([
        'get_prepaid_balances' => [
          'amount_usd' => '19.99',
          'topups_usd' => '19.99',
          'current_usage_usd' => '0',
        ],
      ])
      // Queue a developer balance response for the top up (POST).
      ->queueMockResponse(['post_developer_balances' => ['amount' => '39.98']])
      // Queue an updated balance response.
      ->queueMockResponse([
        'get_prepaid_balances' => [
          'amount_usd' => '39.98',
          'topups_usd' => '39.98',
          'current_usage_usd' => '0',
        ],
      ]);

    // Execute the job which will update the developer balance.
    $this->getExecutor()->call($job);
    static::assertSame(Job::FINISHED, $job->getStatus());

    // The new balance will be re-read so queue the response.
    $this->stack->queueMockResponse([
      'get_developer_balances' => [
        'amount_usd' => '39.98',
        'developer' => new Developer([
          'email' => $this->developer->getEmail(),
          'uuid' => \Drupal::service('uuid')->generate(),
        ]),
      ],
    ]);
    $new_balance = $this->balance_controller->getByCurrency('USD');
    // The new balance should be 19.99.
    static::assertSame(39.98, $new_balance->getAmount());

    $this->assertNoClientError();
  }

  /**
   * Run another test to check for success notifications.
   *
   * @throws \Exception
   */
  public function testSuccessfulNotification() {
    $this->config(AddCreditConfig::CONFIG_NAME)
      ->set('notify_on', AddCreditConfig::NOTIFY_ALWAYS)
      ->save();

    $this->testExecuteRequestWithExistingBalance();

    $emails = \Drupal::state()->get('system.test_mail_collector');
    static::assertCount(1, $emails, 'An email has been captured.');

    $developer_mail = $this->developer->getEmail();
    static::assertSame($this->site_mail, $emails[0]['to']);
    static::assertSame($this->site_mail, $emails[0]['from']);
    static::assertSame('balance_adjustment_report', $emails[0]['key']);
    static::assertSame("Add Credit successfully applied to account ({$developer_mail}) from example site", $emails[0]['subject']);
    static::assertContains("Adjustment applied to developer:  `{$developer_mail}`.", $emails[0]['body']);
    static::assertContains('Existing credit added (' . date('F') . '):  `$19.99`', $emails[0]['body']);
    static::assertContains('Amount Applied:                   `$19.99`.', $emails[0]['body']);
    static::assertContains('New Balance:                      `$39.98`.', $emails[0]['body']);
    static::assertContains('Expected New Balance:             `$39.98`.', $emails[0]['body']);
  }

  /**
   * Tests the job will update the developer balance.
   *
   * Run the test again with the same users to test adding to an existing
   * balance. This would normally run as a seperate test but that would create
   * another user that cannot be removed because of holding a developer
   * balance. Err: `Cannot delete Developer [xxxxx] used in Developer_Balance`
   *
   * @throws \Exception
   */
  public function testExecuteRequestWithError() {
    $job = new BalanceAdjustmentJob($this->developer, new Adjustment([
      'type' => 'apigee_balance',
      'label' => 'Apigee credit adjustment',
      'amount' => new Price('19.99', 'USD'),
    ]));

    // Save the job.
    $this->scheduleJob($job);
    static::assertSame(Job::IDLE, $job->getStatus());

    $this->stack
      // We should now have an existing balance of 19.99.
      ->queueMockResponse([
        'get_prepaid_balances' => [
          'amount_usd' => '19.99',
          'topups_usd' => '19.99',
          'current_usage_usd' => '0',
        ],
      ])
      // Queue a developer balance response for the top up (POST).
      ->append(new Response(415, [], ''));

    // Execute the job which will update the developer balance.
    $this->getExecutor()->call($job);
    static::assertSame(Job::FAILED, $job->getStatus());

    // Get the last exception.
    $exceptions = $this->sdk_connector->getClient()->getJournal()->getLastException();
    static::assertNotEmpty(
      $exceptions,
      'A HTTP error has been logged in the Journal.'
    );

    $emails = \Drupal::state()->get('system.test_mail_collector');
    static::assertCount(1, $emails, 'An email has been captured.');

    static::assertSame($this->site_mail, $emails[0]['to']);
    static::assertSame($this->site_mail, $emails[0]['from']);
    static::assertSame('balance_adjustment_error_report', $emails[0]['key']);
    static::assertSame('Developer account add credit error from example site', $emails[0]['subject']);
    static::assertContains('There was an error applying a credit to an account.', $emails[0]['body']);
    $nl = PHP_EOL;
    static::assertContains("Calculation discrepancy applying adjustment to developer{$nl}`{$this->developer->getEmail()}`.", $emails[0]['body']);
    static::assertContains('Existing credit added (' . date('F') . '):  `$19.99`', $emails[0]['body']);
    static::assertContains('Amount Applied:                   `$19.99`.', $emails[0]['body']);
    static::assertContains('New Balance:                      `Error retrieving the new balance.`.', $emails[0]['body']);
    static::assertContains('Expected New Balance:             `$39.98`.', $emails[0]['body']);
  }

}
