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

namespace Drupal\apigee_m10n_add_credit\Routing;

use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\PreloadableRouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

/**
 * Route builder for add credit.
 */
class AddCreditRoutes implements ContainerInjectionInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\PreloadableRouteProviderInterface
   */
  protected $routeProvider;

  /**
   * AddCreditRoutes constructor.
   *
   * @param \Drupal\Core\Routing\PreloadableRouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(PreloadableRouteProviderInterface $route_provider) {
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.route_provider')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = [];

    foreach (AddCreditConfig::getEntityTypes() as $entity_type_id => $config) {
      // Build the path using the base path and suffix with 'add-credit/{currency_id}'.
      try {
        if (($route = $this->routeProvider->getRouteByName($config['base_route_name'])) && ($path = $route->getPath())) {
          $edge_entity_type = $config['edge_entity_type'];
          $routes["apigee_m10n_add_credit.add_credit.$entity_type_id"] = new Route($path . '/add-credit/{currency_id}',
            [
              '_controller' => '\Drupal\apigee_m10n_add_credit\Controller\AddCreditController::view',
              '_title' => 'Add credit',
            ],
            [
              '_permission' => "add credit to any $edge_entity_type prepaid balance+add credit to own $edge_entity_type prepaid balance",
            ],
            [
              '_apigee_monetization_route' => TRUE,
              'parameters' => [
                $entity_type_id => [
                  'type' => "entity:{$entity_type_id}",
                ],
              ],
            ]
          );
        }
      }
      catch (RouteNotFoundException $exception) {
        // TODO: Log this.
      }
    }

    return $routes;
  }

}
