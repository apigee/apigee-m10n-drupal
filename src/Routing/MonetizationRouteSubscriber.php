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
      // XRate Plans.
      "entity.field_config.xrate_plan_field_edit_form",
      "entity.field_config.xrate_plan_storage_edit_form",
      "entity.field_config.xrate_plan_field_delete_form",
      "entity.xrate_plan.field_ui_fields",
      "field_ui.field_storage_config_add_xrate_plan",
      "entity.entity_form_display.xrate_plan.default",
      "entity.entity_form_display.xrate_plan.form_mode",
      // Purchased plans.
      "entity.field_config.purchased_plan_field_edit_form",
      "entity.field_config.purchased_plan_storage_edit_form",
      "entity.field_config.purchased_plan_field_delete_form",
      "entity.purchased_plan.field_ui_fields",
      "field_ui.field_storage_config_add_purchased_plan",
      // Purchased plans.
      "entity.field_config.purchased_product_field_edit_form",
      "entity.field_config.purchased_product_storage_edit_form",
      "entity.field_config.purchased_product_field_delete_form",
      "entity.purchased_product.field_ui_fields",
      "field_ui.field_storage_config_add_purchased_product",
      // Product bundles.
      "entity.field_config.product_bundle_field_edit_form",
      "entity.field_config.product_bundle_storage_edit_form",
      "entity.field_config.product_bundle_field_delete_form",
      "entity.product_bundle.field_ui_fields",
      "field_ui.field_storage_config_add_product_bundle",
      "entity.entity_form_display.product_bundle.default",
      "entity.entity_form_display.product_bundle.form_mode",
      // Product X.
      "entity.field_config.xproduct_field_edit_form",
      "entity.field_config.xproduct_storage_edit_form",
      "entity.field_config.xproduct_field_delete_form",
      "entity.xproduct.field_ui_fields",
      "field_ui.field_storage_config_add_xproduct",
      "entity.entity_form_display.xproduct.default",
      "entity.entity_form_display.xproduct.form_mode",
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
