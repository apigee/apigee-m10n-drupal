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
use Apigee\Edge\Api\Monetization\Entity\PrepaidBalanceInterface;
use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\apigee_edge\Entity\Developer;
use Drupal\apigee_edge\Entity\DeveloperInterface;
use Drupal\apigee_edge\Job\EdgeJob;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;
use Drupal\apigee_m10n\Controller\PrepaidBalanceController;
use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

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
 *
 * @package Drupal\apigee_m10n_add_credit\Job
 */
class BalanceAdjustmentJob extends EdgeJob {

  use StringTranslationTrait;

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
   * The company or user the adjustment should be applied to.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $target;

  /**
   * Creates an Apigee balance adjustment (add credit) job.
   *
   * @param \Drupal\Core\Entity\EntityInterface $target
   *   The company or user the adjustment should be applied to.
   * @param \Drupal\commerce_order\Adjustment $adjustment
   *   The drupal commerce adjustment.
   */
  public function __construct(EntityInterface $target, Adjustment $adjustment) {
    parent::__construct();

    $this->target = $target;
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

        // Invalidate cache.
        $cache_entity = $this->target instanceof DeveloperInterface ? $this->target->getOwner() : $this->target;
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
        'id' => $this->target->id(),
        'existing' => $this->formatPrice($existing_top_ups),
        'adjustment' => $this->formatPrice($adjustment->getAmount()),
        'new_balance' => isset($new_balance) ? $this->formatPrice($new_balance) : 'Error retrieving the new balance.',
        'expected_balance' => $this->formatPrice($expected_balance),
        'month' => date('F'),
      ];

      // Report the transaction.
      $this->getLogger()->{$log_action}($report_text, $context);

      // Strip br html tags.
      $report_text = str_replace('<br />', '', $report_text);

      // The message parser strips out empty values so we may need to re-add
      // some empty values for formatting.
      $all_placeholders = [
        '@id' => '',
        '@existing' => '',
        '@adjustment' => '',
        '@new_balance' => '',
        '@expected_balance' => '',
        '@month' => '',
      ];

      // Format the message using the log message parser.
      $message_context = $this->getLogMessageParser()->parseMessagePlaceholders($report_text, $context);

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
    $abs_price = new Price(abs($this->adjustment->getAmount()
      ->getNumber()), $this->adjustment->getAmount()->getCurrencyCode());

    return t(":adj_verb :amount to :account", [
      ':adj_verb' => $adj_verb,
      ':amount' => $this->formatPrice($abs_price),
      ':account' => $this->target->id(),
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
      $balances = array_combine(array_map(function (PrepaidBalanceInterface $balance) {
        return $balance->getCurrency()->getName();
      }, $balances), $balances);
    }
    return !empty($balances[$currency_code]) ? $balances[$currency_code] : NULL;
  }

  /**
   * Gets the appropriate controller for the operational entity type.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\PrepaidBalanceControllerInterface|false
   *   The developer balance controller
   */
  protected function getBalanceController() {
    if ($this->target instanceof DeveloperInterface) {
      // For a developer, we need an instance of UserInterface.
      return $this->getSdkController()->developerBalanceController($this->target->getOwner());
    }

    if ($this->target instanceof CompanyInterface) {
      return $this->getSdkController()->companyBalanceController($this->target);
    }

    return FALSE;
  }

  /**
   * Returns the logger for this job.
   *
   * @return \Psr\Log\LoggerInterface
   *   The Psr7 logger.
   */
  protected function getLogger(): LoggerInterface {
    return \Drupal::service('logger.channel.apigee_m10n_add_credit');
  }

  /**
   * Returns the log message parser service.
   *
   * @return \Drupal\Core\Logger\LogMessageParserInterface
   *   The log message parser service.
   */
  protected function getLogMessageParser(): LogMessageParserInterface {
    return \Drupal::service('logger.log_message_parser');
  }

  /**
   * Returns the drupal commerce currency formatter.
   *
   * @return \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   *   The currency formatter.
   */
  protected function currencyFormatter(): CurrencyFormatterInterface {
    return \Drupal::service('commerce_price.currency_formatter');
  }

  /**
   * Returns the Apigee SDK controller factory service.
   *
   * @return \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface
   *   The Apigee SDK controller factory.
   */
  protected function getSdkController(): ApigeeSdkControllerFactoryInterface {
    return \Drupal::service('apigee_m10n.sdk_controller_factory');
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
        'currency_display' => 'symbol',
        'minimum_fraction_digits' => 2,
      ]
    );
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
    $type = 'entity';

    // Get the basename of the target entity.
    try {
      $type = (new \ReflectionClass($this->target))->getShortName();
    }
    catch (\ReflectionException $exception) {
      $this->getLogger()->warning($exception->getMessage());
    }

    $report_text = 'Existing credit added ({month}): {existing}.<br />';
    $report_text .= 'Amount Applied: {adjustment}.<br />';
    $report_text .= 'New Balance: {new_balance}.<br />';
    $report_text .= 'Expected New Balance: {expected_balance}.<br />';

    $messages = [
      'balance_error_message' => "The $type ({id}) has no balance for ({currency}).",
      'report_text_error_header' => "Calculation discrepancy applying adjustment to $type {id}. <br />",
      'report_text_info_header' => "Adjustment applied to $type {id}. <br />",
      'report_text' => $report_text,
    ];

    return $messages[$message_id];
  }

  /**
   * Send a notification using drupal mail API.
   *
   * @param string $notification_type
   *   The notification type.
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
