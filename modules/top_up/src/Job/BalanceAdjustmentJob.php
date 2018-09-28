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

namespace Drupal\apigee_m10n_top_up\Job;

use Apigee\Edge\Api\Management\Entity\CompanyInterface;
use Drupal\apigee_edge\Job\EdgeJob;
use Drupal\apigee_m10n\Controller\BillingController;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;

/**
 * The job is responsible for updating the account balance for a developer or
 * company. It is usually initiated after a top up product is purchased.
 *
 * Execute should not return anything if the job was successful. Throwing an
 * error will let the job runner know that the request was unsuccessful and will
 * trigger a retry.
 *
 * TODO: Handle refunds when the monetization API supports it.
 *
 * @package Drupal\apigee_m10n_top_up\Job
 */
class BalanceAdjustmentJob extends EdgeJob {

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
   * Co-opt the commerce adjustment since this module requires it anyway. For the
   * context of this job the adjustment is what is to be made to the account
   * balance. An increase to the account balance would be a positive adjustment
   * and a decrease would be a negative adjustment.
   *
   * @var \Drupal\commerce_order\Adjustment
   */
  protected $adjustment;

  /**
   * Creates a top up balance job.
   *
   * @param \Drupal\Core\Entity\EntityInterface $company_or_user
   * @param \Drupal\commerce_order\Adjustment $adjustment
   */
  public function __construct(EntityInterface $company_or_user, Adjustment $adjustment) {
    parent::__construct();

    // Either a developer or a company can be passed.
    if ($company_or_user instanceof UserInterface) {
      // A developer was passed.
      $this->developer = $company_or_user;
    } elseif ($company_or_user instanceof CompanyInterface) {
      // A company was passed.
      $this->company = $company_or_user;
    }

    $this->adjustment = $adjustment;

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
      // The controller doesn't deal well if there is no balance for a given
      // currency so we have to catch the error.
      try {
        // Get existing balance with the same currency code.
        $balance = $controller->getByCurrency($currency_code);
      } catch (\TypeError $err) {
        $this->getLogger()->info('Apigee User ({email}) has no balance for ({currency}).', [
          'email' => $this->developer->getEmail(),
          'currency' => $currency_code,
        ]);
      }

      $existing_balance = new Price(!empty($balance) ? (string) $balance->getAmount() : '0', $currency_code);

      // Calculate the new balance.
      $new_balance = $existing_balance->add($adjustment->getAmount());

      try {
        // Top up by the adjustment amount.
        $updated_balance = $controller->topUpBalance((float) $adjustment->getAmount()->getNumber(), $currency_code);
        Cache::invalidateTags([BillingController::$cachePrefix  . ':user:' . $this->developer->id()]);
      } catch (\Throwable $t) {
        // Nothing gets logged/reported if we let errors end the job here.
        $this->getLogger()->error((string) $t);
        $thrown = $t;
      }

      // Check the balance again to make sure the amount is correct.
      if (!empty($updated_balance)
        && !empty($updated_balance->getAmount())
        && ($new_balance->getNumber() === (string) $updated_balance->getAmount())
      ) {
        // Set the log message and action.
        $report  = 'Adjustment applied to `{email}`. <br />' . PHP_EOL;
        $log_action = 'info';
      } else {
        // TODO: Send an email to an administrator if the calculations don't work out.
        // Something is fishy here, we should log as an error.
        $report  = 'Calculation discrepancy applying adjustment to `{email}`.<br />' . PHP_EOL;
        $log_action = 'error';
      }

      $report .= '  Previous Balance: `{previous}`.<br />' . PHP_EOL;
      $report .= '  Amount Applied:   `{adjustment}`.<br />' . PHP_EOL;
      $report .= '  New Balance:      `{new_balance}`.<br />' . PHP_EOL;

      // Report the transaction.
      $this->getLogger()->{$log_action}($report, [
        'email'       => $this->developer->getEmail(),
        'previous'    => $this->formatPrice($existing_balance),
        'adjustment'  => $this->formatPrice($adjustment->getAmount()),
        'new_balance' => $this->formatPrice($new_balance),
      ]);

      // If there were any errors or exceptions, they still need to be thrown.
      if (isset($thrown)) {
        throw $thrown;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRetry(\Exception $exception): bool {
    // We aren't retrying requests ATM. If we can confirm that the payment
    // wasn't applied, we could return true here and the top-up would be retried.
    // TODO: Return true once we can determine the payment wasn't applied (fosho).

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
   * Get's the logger for this job.
   *
   * @return \Psr\Log\LoggerInterface
   *   The Psr7 logger.
   */
  protected function getLogger() {
    return \Drupal::logger('apigee_monetization_top_up');
  }

  /**
   * Gets the developer balance controller for the developer user.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\PrepaidBalanceControllerInterface|FALSE
   *   The developer balance controller
   */
  protected function getBalanceController() {
    // Return the appropriate controller for the operational entity type.
    if (!empty($this->developer)) {
      return \Drupal::service('apigee_m10n.sdk_controller_factory')
        ->developerBalanceController($this->developer);
    } elseif (!empty($this->company)) {
      return \Drupal::service('apigee_m10n.sdk_controller_factory')
        ->companyBalanceController($this->company);
    }
    return FALSE;
  }

  /**
   * Get's the drupal commerce currency formatter.
   *
   * @return \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
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
      $price->getCurrencyCode(),
      [
        'currency_display'        => 'symbol',
        'minimum_fraction_digits' => 0,
      ]
    );
  }
}
