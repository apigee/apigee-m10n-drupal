<?php

/*
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

namespace Drupal\apigee_m10n\Entity\ListBuilder;

use Drupal\apigee_m10n\Entity\PurchasedProduct;
use Drupal\apigee_m10n\Entity\PurchasedProductInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\apigee_m10n\Monetization;
use Drupal\apigee_m10n\MonetizationInterface;

/**
 * Defines implementation of a purchased product listing page.
 *
 * @ingroup entity_api
 */
class PurchasedProductListBuilderForDeveloper extends PurchasedProductListBuilder {

  /**
   * The developer's user that is used to load purchased products.
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
    $monetization = \Drupal::service('apigee_m10n.monetization');

    if (!$monetization->isOrganizationApigeeXorHybrid()) {
      return AccessResult::forbidden('ApigeeX is not enabled.');
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
    return PurchasedProduct::loadByDeveloperId($this->user->getEmail());
  }

  /**
   * {@inheritdoc}
   */
  protected function cancelUrl(PurchasedProductInterface $purchased_product) {
    return $this->ensureDestination(Url::fromRoute('entity.purchased_product.developer_cancel_form', [
      'user' => $this->user->id(),
      'purchased_product' => $purchased_product->id(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  protected function ratePlanUrl(PurchasedProductInterface $purchased_product, $rateplanId) {
    return $this->ensureDestination(Url::fromRoute('entity.xrate_plan.canonical', [
      'user' => $this->user->id(),
      'xproduct' => $purchased_product->getApiproduct(),
      'xrate_plan' => $rateplanId,
    ]));
  }

}
