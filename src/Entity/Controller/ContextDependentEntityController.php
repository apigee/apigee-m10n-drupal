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

namespace Drupal\apigee_m10n\Entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for subscribing to rate plans.
 */
class ContextDependentEntityController extends ControllerBase {

  /**
   * Redirects to the developer route for an entity.
   *
   * Some entities require developer context. A canonical url won't have
   * developer context so we redirect and add the context of the current user.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function developerRedirect(RouteMatchInterface $route_match): RedirectResponse {
    // Gets the entity type ID from the route options.
    $entity_type_id = $route_match->getRouteObject()->getOption('entity_type_id');
    // The developer routes should always follow this pattern.
    $developer_route_name = "entity.{$entity_type_id}.developer";
    // Add the current user as the developer (`user`).
    $parameters = ['user' => $this->currentUser()->id()];
    // Re-add the rest of the parameters for this route.
    foreach ($route_match->getParameters()->keys() as $key) {
      $parameters[$key] = $route_match->getRawParameter($key);
    }

    return $this->redirect($developer_route_name, $parameters);
  }

}
