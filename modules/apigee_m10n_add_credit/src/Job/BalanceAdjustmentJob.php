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

namespace Drupal\apigee_m10n_add_credit\Job;

use Apigee\Edge\Api\Management\Entity\CompanyInterface;
use Apigee\Edge\Api\Monetization\Controller\PrepaidBalanceControllerInterface;
use Drupal\apigee_edge\Entity\DeveloperInterface;
use Drupal\apigee_edge\Job\EdgeJob;
use Drupal\apigee_m10n\Controller\PrepaidBalanceController;
use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * An apigee job that will apply a balance adjustment.
 *
 * The job is responsible for updating the account balance for a developer or
 * company. It is usually initiated after an add credit product is purchased.
 *
 * Execute should not return anything if the job was successful. Throwing an
 * error will let the job runner know that the request was unsuccessful and will
 * trigger a retry.
 *
 * @todo: Handle refunds when the monetization API supports it.
 */
class BalanceAdjustmentJob extends EdgeJob {

  use StringTranslationTrait;

  /**
   * The developer account to whom a balance adjustment is to be made.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * The company to whom a balance adjustment is to be made.
   *
   * @var \Apigee\Edge\Api\Management\Entity\CompanyInterface
   */
  protected $company;

  /**
   * The drupal commerce adjustment.
   *
   * Co-opt the commerce adjustment since this module requires it anyway. For
   * the context of this job the adjustment is what is to be made to the account
   * balance. An increase to the account balance would be a positive adjustment
   * and a decrease would be a negative adjustment.
   *
   * @var \Drupal\commerce_order\Adjustment
   */
  protected $adjustment;

  /**
   * The `apigee_m10n_add_credit` module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $module_config;

  /**
   * Creates an Apigee balance adjustment (add credit) job.
   *
   * @param \Drupal\Core\Entity\EntityInterface $company_or_user
   *   The company  or user the adjustment should  be applied to.
   * @param \Drupal\commerce_order\Adjustment $adjustment
   *   The drupal commerce adjustment.
   */
  public function __construct(EntityInterface $company_or_user, Adjustment $adjustment) {
    parent::__construct();

    // Either a developer or a company can be passed.
    if ($company_or_user instanceof UserInterface) {
      // A user was passed.
      $this->developer = $company_or_user;
    }
    elseif ($company_or_user instanceof DeveloperInterface) {
      // A developer was passed. Get the owner.
      $this->developer = $company_or_user->getOwner();
    }
    elseif ($company_or_user instanceof CompanyInterface) {
      // A company was passed.
      $this->company = $company_or_user;
    }

    $this->adjustment = $adjustment;

    $this->module_config = \Drupal::config(AddCreditConfig::CONFIG_NAME);

    $this->setTag('prepaid_balance_update_wait');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Throwable
   */
  protected function executeRequest() {
    $adjustment = $this->adjustment;
    $currency_code = $adjustment->getAmount()->getCurrencyCode();
    // Grab the current balances.
    if ($controller = $this->getBalanceController()) {
      // Get existing balance with the same currency code.
      $balance = $this->getPrepaidBalance($controller, $currency_code);

      $existing_top_ups = new Price(!empty($balance) ? (string) $balance->getTopUps() : '0', $currency_code);

      // Calculate the expected new balance.
      $expected_balance = $existing_top_ups->add($adjustment->getAmount());

      try {
        // Top up by the adjustment amount.
        $controller->topUpBalance((float) $adjustment->getAmount()->getNumber(), $currency_code);
        // The data returned from `topUpBalance` doesn't get us the new top up
        // total so we have to grab that from the balance controller again.
        $balance_after = $this->getPrepaidBalance($controller, $currency_code);
        $new_balance = new Price((string) ($balance_after->getTopUps()), $currency_code);
        $cache_entity = $this->isDeveloperAdjustment() ? $this->developer : $this->company;
        Cache::invalidateTags([PrepaidBalanceController::getCacheId($cache_entity)]);
      }
      catch (\Throwable $t) {
        // Nothing gets logged/reported if we let errors end the job here.
        $this->getLogger()->error((string) $t);
        $thrown = $t;
      }

      // Check the balance again to make sure the amount is correct.
      if (!empty($new_balance)
        && !empty($new_balance->getNumber())
        && ($expected_balance->getNumber() === $new_balance->getNumber())
      ) {
        // Set the log action.
        $log_action = 'info';
      }
      else {
        // Something is fishy here, we should log as an error.
        $log_action = 'error';
      }

      // Get the appropriate report text from the lookup table.
      $report_text = $this->getMessage("report_text_{$log_action}_header") . $this->getMessage('report_text');

      // Compile message context.
      $context = [
        'email'             => !empty($this->developer) ? $this->developer->getEmail() : '',
        'team_name'         => !empty($this->company) ? $this->company->label() : '',
        'existing'          => $this->formatPrice($existing_top_ups),
        'adjustment'        => $this->formatPrice($adjustment->getAmount()),
        'new_balance'       => isset($new_balance) ? $this->formatPrice($new_balance) : 'Error retrieving the new balance.',
        'expected_balance'  => $this->formatPrice($expected_balance),
        'month'             => date('F'),
      ];

      // Report the transaction.
      $this->getLogger()->{$log_action}($report_text, $context);

      /** @var \Drupal\Core\Logger\LogMessageParser $message_parser */
      $message_parser = \Drupal::service('logger.log_message_parser');
      // Strip br html tags.
      $report_text = str_replace('<br />', '', $report_text);
      // The message parser strips out empty values so we may need to re-add
      // some empty values for formatting.
      $all_placeholders = [
        '@email' => '',
        '@team_name' => '',
        '@existing' => '',
        '@adjustment' => '',
        '@new_balance' => '',
        '@expected_balance' => '',
        '@month' => '',
      ];
      // Format the message using the log message parser.
      $message_context = $message_parser->parseMessagePlaceholders($report_text, $context);
      // Re-add empty values to message context.
      $message_context = $message_context + $all_placeholders;
      // Add the report text to the message context.
      $message_context['report_text'] = $report_text;

      // If there were any errors or exceptions, they still need to be thrown.
      if (isset($thrown)) {
        $message_context['@error'] = (string) $thrown;
        // Sent the notification.
        $this->sendNotification('balance_adjustment_error_report', $message_context);

        throw $thrown;
      }
      elseif ($this->module_config->get('notify_on') == AddCreditConfig::NOTIFY_ALWAYS) {
        $this->sendNotification('balance_adjustment_report', $message_context);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRetry(\Exception $exception): bool {
    // We aren't retrying requests ATM. If we can confirm that the payment
    // wasn't applied, we could return true here and the top-up would be
    // retried.
    // @todo Return true once we can determine the payment wasn't applied.

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    // Use "Add" for an increase adjustment or "Subtract" for a decrease.
    $adj_verb = $this->adjustment->isPositive() ? 'Add' : 'Subtract';
    $abs_price = new Price(abs($this->adjustment->getAmount()->getNumber()), $this->adjustment->getAmount()->getCurrencyCode());

    return t(":adj_verb :amount to :account", [
      ':adj_verb' => $adj_verb,
      ':amount' => $this->formatPrice($abs_price),
      ':account' => $this->developer->getEmail(),
    ]);
  }

  /**
   * Get's the prepaid balance information from the given controller.
   *
   * @param \Apigee\Edge\Api\Monetization\Controller\PrepaidBalanceControllerInterface $controller
   *   The team or developer controller.
   * @param string $currency_code
   *   The currency code to retrieve the balance for.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\PrepaidBalanceInterface|null
   *   The balance for this adjustment currency.
   *
   * @throws \Exception
   */
  protected function getPrepaidBalance(PrepaidBalanceControllerInterface $controller, $currency_code) {
    /** @var \Apigee\Edge\Api\Monetization\Entity\PrepaidBalanceInterface[] $balances */
    $balances = $controller->getPrepaidBalance(new \DateTimeImmutable());

    if (!empty($balances)) {
      $balances = array_combine(array_map(function ($balance) {
        return $balance->getCurrency()->getName();
      }, $balances), $balances);
    }
    return !empty($balances[$currency_code]) ? $balances[$currency_code] : NULL;
  }

  /**
   * Get's the logger for this job.
   *
   * @return \Psr\Log\LoggerInterface
   *   The Psr7 logger.
   */
  protected function getLogger() {
    return \Drupal::service('logger.channel.apigee_m10n_add_credit');
  }

  /**
   * Gets the developer balance controller for the developer user.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\PrepaidBalanceControllerInterface|false
   *   The developer balance controller
   */
  protected function getBalanceController() {
    // Return the appropriate controller for the operational entity type.
    if (!empty($this->developer)) {
      return \Drupal::service('apigee_m10n.sdk_controller_factory')
        ->developerBalanceController($this->developer);
    }
    elseif (!empty($this->company)) {
      return \Drupal::service('apigee_m10n.sdk_controller_factory')
        ->companyBalanceController($this->company);
    }
    return FALSE;
  }

  /**
   * Get's the drupal commerce currency formatter.
   *
   * @return \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   *   The currency formatter.
   */
  protected function currencyFormatter() {
    return \Drupal::service('commerce_price.currency_formatter');
  }

  /**
   * Formats a commerce price using the currency formatter service.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The commerce price to be formatted.
   *
   * @return string
   *   The formatted price, i.e. $100 USD.
   */
  protected function formatPrice(Price $price) {
    return $this->currencyFormatter()->format(
      $price->getNumber(),
      strtoupper($price->getCurrencyCode()),
      [
        'currency_display'        => 'symbol',
        'minimum_fraction_digits' => 2,
      ]
    );
  }

  /**
   * Helper to determine if this is a developer adjustment.
   *
   * Otherwise, this is a company adjustment.
   *
   * @return bool
   *   True if this is a developer adjustment.
   */
  protected function isDeveloperAdjustment(): bool {
    return !empty($this->developer);
  }

  /**
   * Get a message.
   *
   * A lookup for messages that depends on the type of adjustment we are dealing
   * with here.
   *
   * @param string $message_id
   *   An identifier for the message.
   *
   * @return string
   *   The message.
   */
  protected function getMessage($message_id) {
    $type = $this->isDeveloperAdjustment() ? 'developer' : 'company';

    $report_text = 'Existing credit added ({month}):  `{existing}`.<br />' . PHP_EOL;
    $report_text .= 'Amount Applied:                   `{adjustment}`.<br />' . PHP_EOL;
    $report_text .= 'New Balance:                      `{new_balance}`.<br />' . PHP_EOL;
    $report_text .= 'Expected New Balance:             `{expected_balance}`.<br />' . PHP_EOL;

    $messages = [
      'developer' => [
        'balance_error_message' => 'Apigee User ({email}) has no balance for ({currency}).',
        'report_text_error_header' => 'Calculation discrepancy applying adjustment to developer `{email}`. <br />' . PHP_EOL . PHP_EOL,
        'report_text_info_header'  => 'Adjustment applied to developer:  `{email}`. <br />' . PHP_EOL . PHP_EOL,
        'report_text'              => $report_text,
      ],
      'company' => [
        'balance_error_message' => 'Apigee team ({team_name}) has no balance for ({currency}).',
        'report_text_error_header'  => 'Calculation discrepancy applying adjustment to team `{team_name}`. <br />' . PHP_EOL . PHP_EOL,
        'report_text_info_header'   => 'Adjustment applied to team:       `{team_name}`. <br />' . PHP_EOL . PHP_EOL,
        'report_text'               => $report_text,
      ],
    ];

    return $messages[$type][$message_id];
  }

  /**
   * Send a notification using drupal mail API.
   *
   * @param string $notification_type
   *   The notificaiton type.
   * @param array|null $message_context
   *   The message context.
   */
  protected function sendNotification($notification_type, $message_context) {
    // Email the error to an administrator.
    $recipient = !empty($this->module_config->get('notification_recipient'))
      ? $this->module_config->get('notification_recipient')
      : \Drupal::config('system.site')->get('mail');
    $recipient = !empty($recipient) ? $recipient : ini_get('sendmail_from');
    \Drupal::service('plugin.manager.mail')->mail(
      'apigee_m10n_add_credit',
      $notification_type,
      $recipient,
      Language::LANGCODE_DEFAULT,
      $message_context
    );
  }

}
