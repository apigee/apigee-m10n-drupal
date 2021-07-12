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

namespace Drupal\apigee_m10n\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for team balances.
 */
class PrepaidBalanceController extends PrepaidBalanceControllerBase {

  /**
   * Redirect to the user's prepaid balances page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Gets a redirect to the users's balance page.
   */
  public function myRedirect(): RedirectResponse {
    if ($this->monetization->isOrganizationApigeeXorHybrid()) {
      return $this->redirect(
        'apigee_monetization.xbilling',
        ['user' => $this->currentUser->id()],
        ['absolute' => TRUE]
      );
    }
    else {
      return $this->redirect(
        'apigee_monetization.billing',
        ['user' => $this->currentUser->id()],
        ['absolute' => TRUE]
      );
    }
  }

  /**
   * Checks current users access and if developer is prepaid.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Grants access to the route if passed permissions are present and given
   *   developer is prepaid.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $user = $route_match->getParameter('user');
    if (!$this->monetization->isDeveloperPrepaid($user)) {
      return AccessResult::forbidden('Developer is not prepaid.');
    }

    if ($this->monetization->isOrganizationApigeeXorHybrid()) {
      return AccessResult::forbidden('Not accessible for ApigeeX organization');
    }
    return AccessResult::allowedIf(
      $account->hasPermission('view any prepaid balance') ||
      ($account->hasPermission('view own prepaid balance') && $account->id() === $user->id())
    );
  }

  /**
   * View prepaid balance and account statements, add money to prepaid balance.
   *
   * @param \Drupal\user\UserInterface $user
   *   The Drupal user.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array or a redirect response.
   *
   * @throws \Exception
   */
  public function prepaidBalancePage(UserInterface $user) {
    // Set the entity for this page call.
    $this->entity = $user;

    return $this->render();
  }

  /**
   * {@inheritdoc}
   */
  protected function canRefreshBalance() {
    $user = $this->entity;

    return $this->currentUser->hasPermission('refresh any prepaid balance') ||
      ($this->currentUser->hasPermission('refresh own prepaid balance') && $this->currentUser->id() === $user->id());
  }

  /**
   * {@inheritdoc}
   */
  protected function canAccessDownloadReport() {
    return $this->currentUser->hasPermission('download prepaid balance reports');
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    return ($list = $this->monetization->getDeveloperPrepaidBalances($this->entity, new \DateTimeImmutable('now'))) ? $list : [];
  }

}
