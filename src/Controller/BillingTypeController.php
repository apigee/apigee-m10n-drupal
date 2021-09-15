<?php

/**
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

namespace Drupal\apigee_m10n\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\apigee_m10n\MonetizationInterface;

/**
 * An example controller.
 */
class BillingTypeController extends ControllerBase {

  /**
   * Apigee Monetization base service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * BillingTypeController constructor.
   *
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   Monetization service.
   */
  public function __construct(MonetizationInterface $monetization) {
    $this->monetization = $monetization;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('apigee_m10n.monetization')
    );
  }

  /**
   * Checks current users access to Billing type page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Grants access to the route if passed permissions are present.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $user = $route_match->getParameter('user');
    try {
      $developer_billingtype = $this->monetization->getBillingtype($user);
    }
    catch (\Exception $e) {
      return AccessResult::forbidden('Developer does not exist.');
    }

    if (!$this->monetization->isOrganizationApigeeXorHybrid()) {
      return AccessResult::forbidden('Only accessible for ApigeeX organization.');
    }

    return AccessResult::allowedIf(
      $account->hasPermission('view any billing details') ||
      ($account->hasPermission('view own billing details') && $account->id() === $user->id())
    );
  }

  /**
   * Displays the user/developers billing type.
   *
   * @param \Drupal\user\UserInterface $user
   *   The drupal user/developer.
   *
   * @return array
   *   The billing type array.
   */
  public function myBillingType(UserInterface $user) {
    $developer_billing_type = $this->monetization->getBillingtype($user);
    $build = [
      '#type' => 'Page',
      '#prefix' => 'Billing Type : ',
      '#markup' => $developer_billing_type ? $developer_billing_type : 'Not Specified (Defaults to Postpaid)',
    ];
    return $build;
  }

}
