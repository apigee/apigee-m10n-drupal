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
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for billing related routes.
 */
class BillingController extends ControllerBase implements ContainerInjectionInterface {

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
   * The Apigee SDK connector.
   *
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  protected $sdk_connector;

  /**
   * BillingController constructor.
   *
   * @param \Drupal\apigee_edge\SDKConnectorInterface $sdk_connector
   *   The `apigee_edge.sdk_connector` service.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The `apigee_m10n.monetization` service.
   */
  public function __construct(SDKConnectorInterface $sdk_connector, MonetizationInterface $monetization) {
    $this->sdk_connector = $sdk_connector;
    $this->monetization = $monetization;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('apigee_edge.sdk_connector'),
      $container->get('apigee_m10n.monetization')
    );
  }

  /**
   * Redirect to the user's prepaid balances page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Gets a redirect to the users's balance page.
   */
  public function myPrepaidBalance(): RedirectResponse {
    return $this->redirect(
      'apigee_monetization.billing',
      ['user' => \Drupal::currentUser()->id()],
      ['absolute' => TRUE]
    );
  }

  /**
   * View prepaid balance and account statements, add money to prepaid balance.
   *
   * @param string $user
   *   The user id.
   *
   * @todo: Use the user object.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array or a redirect response.
   *
   * @throws \Exception
   */
  public function prepaidBalancePage(string $user) {
    $user = User::load($user);

    if (!$user) {
      throw new NotFoundHttpException();
    }

    // Retrieve the prepaid balances for this user for the current month and
    // year.
    $balances = $this->monetization->getDeveloperPrepaidBalances($user, new \DateTimeImmutable('now'));

    return [
      'prepaid_balances' => [
        '#theme' => 'prepaid_balances',
        '#balances' => $balances,
        '#cache' => [
          // Add a custom cache tag so that this page can be invalidated by code
          // that updates prepaid balances.
          'tags' => [static::$cachePrefix . ':user:' . $user->id()],
          // Cache by path for up to 10 minutes.
          'contexts' => ['url.path'],
          'max-age' => 600,
        ],
      ],
    ];
  }

}
