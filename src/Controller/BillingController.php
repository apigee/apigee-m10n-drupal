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

use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *  Controller for billing related routes.
 */
class BillingController extends ControllerBase {

  /**
   * Apigee Monetization utility service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  private $monetization;

  /**
   * BillingController constructor.
   *
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   */
  public function __construct(MonetizationInterface $monetization) {
    $this->monetization = $monetization;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('apigee_m10n.monetization'));
  }

  /**
   *  View prepaid balance and account statements, add money to prepaid balance.
   *
   * @var $user_id string
   *
   * @return array|Response
   */
  public function prepaidBalanceAction(string $user_id) {

    // @todo permissions?

    $user = ($user_id === 'me') ? \Drupal::currentUser() : User::load($user_id);

    if (!$user) {
      throw new NotFoundHttpException();
    }

    $balances = $this->monetization->getDeveloperPrepaidBalances($user);

    return [
      'prepaid_balances' => [
        '#theme' => 'prepaid_balances',
        '#balances' => $balances,
      ]
    ];
  }
}
