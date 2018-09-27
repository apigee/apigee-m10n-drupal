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

namespace Drupal\apigee_m10n\Controller;

use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\apigee_m10n\FormattedBalance;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\commerce_price\CurrencyFormatter;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *  Controller for billing related routes.
 */
class BillingController extends ControllerBase {

  /**
   * Cache prefix that is used for cache tags for this controller.
   *
   * @var string
   */
  public static $cachePrefix = 'apigee.monetization.billing';

  /**
   * Apigee Monetization utility service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  private $monetization;

  /**
   * @var \Drupal\commerce_price\CurrencyFormatter
   */
  private $currency_formatter;

  /**
   * BillingController constructor.
   *
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   */
  public function __construct(SDKConnectorInterface $sdk_connector, MonetizationInterface $monetization, CurrencyFormatter $currency_formatter) {
    $this->sdk_connector = $sdk_connector;
    $this->monetization = $monetization;
    $this->currency_formatter = $currency_formatter;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('apigee_edge.sdk_connector'),
      $container->get('apigee_m10n.monetization'),
      $container->get('commerce_price.currency_formatter')
    );
  }

  /**
   * Redirect to the user's prepaid balances page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function myPrepaidBalance(): RedirectResponse {
    return $this->redirect(
      'apigee_monetization.billing',
      ['user' => \Drupal::currentUser()->id()],
      ['absolute' => TRUE]
    );
  }


  /**
   *  View prepaid balance and account statements, add money to prepaid balance.
   *
   * @var $user string
   *
   * @return array|Response
   * @throws \Exception
   */
  public function prepaidBalancePage(string $user) {

    /** @todo Drupal 7 version uses company id if it's available. Implement when company context is ironed out. */
    $user = User::load($user);

    if (!$user) {
      throw new NotFoundHttpException();
    }

    // Retrieve the prepaid balances for this user for the current month and year.
    $balances = $this->monetization->getDeveloperPrepaidBalances($user, new \DateTimeImmutable('now'));

    foreach ($balances as $index => $balance) {
      $balances[$index] = new FormattedBalance($balance, $this->currency_formatter);
    }

    return [
      'prepaid_balances' => [
        '#theme' => 'prepaid_balances',
        '#balances' => $balances,
        '#cache' => [
          // Add a custom cache tag so that this page can be invalidated by code that updates prepaid balances.
          'tags' => [static::$cachePrefix . ':user:' . $user->id()],
          // Cache by path for up to 10 minutes
          'contexts' => ['url.path'],
          'max-age' => 600
        ],
      ]
    ];
  }
}
