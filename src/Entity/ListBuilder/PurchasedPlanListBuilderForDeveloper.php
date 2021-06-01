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

namespace Drupal\apigee_m10n\Entity\ListBuilder;

use Drupal\apigee_m10n\Entity\PurchasedPlan;
use Drupal\apigee_m10n\Entity\PurchasedPlanInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines implementation of a purchased plan listing page.
 *
 * @ingroup entity_api
 */
class PurchasedPlanListBuilderForDeveloper extends PurchasedPlanListBuilder {

  /**
   * The developer's user that is used to load purchased plans.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function render(UserInterface $user = NULL) {
    // Return 404 if the user is not set and keep a compatible method signature.
    if (!($user instanceof UserInterface)) {
      // There is a `user: '^[1-9]+[0-9]*$'` requirement on the route that uses
      // this controller so we should never end up here unless a custom module
      // misuses this controller.
      throw new NotFoundHttpException('The user with the given ID was not found.');
    }
    // From this point forward `$this->user` is a safe assumption.
    $this->user = $user;

    return parent::render();
  }

  /**
   * Checks current users access.
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
    if ($this->monetization->isOrganizationApigeeXorHybrid()) {
      return AccessResult::forbidden('ApigeeX does not support purchased plan.');
    }
    $user = $route_match->getParameter('user');
    return AccessResult::allowedIf(
      $account->hasPermission('view any purchased_plan') ||
      ($account->hasPermission('view own purchased_plan') && $account->id() === $user->id())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    return PurchasedPlan::loadByDeveloperId($this->user->getEmail());
  }

  /**
   * {@inheritdoc}
   */
  protected function cancelUrl(PurchasedPlanInterface $purchased_plan) {
    return $this->ensureDestination(Url::fromRoute('entity.purchased_plan.developer_cancel_form', [
      'user' => $this->user->id(),
      'purchased_plan' => $purchased_plan->id(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  protected function ratePlanUrl(PurchasedPlanInterface $purchased_plan) {
    return $this->ensureDestination(Url::fromRoute('entity.rate_plan.canonical', [
      'user' => $this->user->id(),
      'product_bundle' => $purchased_plan->getRatePlan()->getProductBundleId(),
      'rate_plan' => $purchased_plan->getRatePlan()->id(),
    ]));
  }

}
