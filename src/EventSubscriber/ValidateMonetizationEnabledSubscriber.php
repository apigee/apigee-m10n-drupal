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

namespace Drupal\apigee_m10n\EventSubscriber;

use Drupal\apigee_m10n\Monetization;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Validates that monetization is enabled on every request.
 */
class ValidateMonetizationEnabledSubscriber implements EventSubscriberInterface {

  /**
   * The monetization service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The current route match.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * ValidateMonetizationEnabledSubscriber constructor.
   *
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The monetization service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match.
   */
  public function __construct(MonetizationInterface $monetization, MessengerInterface $messenger, RouteMatchInterface $current_route_match) {
    $this->messenger = $messenger;
    $this->monetization = $monetization;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * If monetization isn't enabled alert the user.
   */
  public function validateMonetizationEnabled() {
    /** @var \Symfony\Component\Routing\Route $current_route */
    if (($current_route = $this->currentRouteMatch->getRouteObject()) && ($current_route->hasOption('_apigee_monetization_route'))) {
      if (!$this->monetization->isMonetizationEnabled()) {
        $this->messenger->addError(Monetization::MONETIZATION_DISABLED_ERROR_MESSAGE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['validateMonetizationEnabled'];
    return $events;
  }

}
