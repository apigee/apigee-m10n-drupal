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

namespace Drupal\apigee_m10n\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Monetization routes.
 */
class MonetizationRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $routes_to_block = [
      // Rate Plans.
      "entity.field_config.rate_plan_field_edit_form",
      "entity.field_config.rate_plan_storage_edit_form",
      "entity.field_config.rate_plan_field_delete_form",
      "entity.rate_plan.field_ui_fields",
      "field_ui.field_storage_config_add_rate_plan",
      "entity.entity_form_display.rate_plan.default",
      "entity.entity_form_display.rate_plan.form_mode",
      // Subscriptions.
      "entity.field_config.subscription_field_edit_form",
      "entity.field_config.subscription_storage_edit_form",
      "entity.field_config.subscription_field_delete_form",
      "entity.subscription.field_ui_fields",
      "field_ui.field_storage_config_add_subscription",
      // Packages.
      "entity.field_config.package_field_edit_form",
      "entity.field_config.package_storage_edit_form",
      "entity.field_config.package_field_delete_form",
      "entity.package.field_ui_fields",
      "field_ui.field_storage_config_add_package",
      "entity.entity_form_display.package.default",
      "entity.entity_form_display.package.form_mode",
    ];

    foreach ($routes_to_block as $route_id) {
      if ($route = $collection->get($route_id)) {
        $route->setRequirements(['_access' => 'FALSE']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -101];
    return $events;
  }

}
