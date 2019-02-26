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

use Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\PreloadableRouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

/**
 * Route builder for add credit.
 */
class AddCreditRoutes implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\PreloadableRouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The add credit plugin manager.
   *
   * @var \Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeManagerInterface
   */
  protected $addCreditPluginManager;

  /**
   * AddCreditRoutes constructor.
   *
   * @param \Drupal\Core\Routing\PreloadableRouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeManagerInterface $add_credit_plugin_manager
   *   The add credit plugin manager.
   */
  public function __construct(PreloadableRouteProviderInterface $route_provider, AddCreditEntityTypeManagerInterface $add_credit_plugin_manager) {
    $this->routeProvider = $route_provider;
    $this->addCreditPluginManager = $add_credit_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('plugin.manager.apigee_add_credit_entity_type')
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

    foreach ($this->addCreditPluginManager->getPlugins() as $plugin) {
      $entity_type_id = $plugin->getPluginId();
      $path_entity_type_id = $plugin->getRouteEntityTypeId();
      $routes["apigee_m10n_add_credit.add_credit.{$entity_type_id}"] = new Route(
        $plugin->getPath(),
        [
          '_controller' => '\Drupal\apigee_m10n_add_credit\Controller\AddCreditController::view',
          '_title' => (string) $this->t('Add credit to @label', [
            '@label' => strtolower($plugin->getLabel()),
          ]),
        ],
        [
          '_custom_access' => '\Drupal\apigee_m10n_add_credit\Controller\AddCreditController::access',
        ],
        [
          '_apigee_monetization_route' => TRUE,
          '_add_credit_entity_type' => $entity_type_id,
          'parameters' => [
            $path_entity_type_id => [
              'type' => "entity:{$path_entity_type_id}",
            ],
          ],
        ]
      );
    }

    return $routes;
  }

}
