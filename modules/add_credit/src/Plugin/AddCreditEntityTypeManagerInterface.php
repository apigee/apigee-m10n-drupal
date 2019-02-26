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

namespace Drupal\apigee_m10n_add_credit\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for the add credit entity type plugin manager.
 */
interface AddCreditEntityTypeManagerInterface extends PluginManagerInterface {

  /**
   * Returns an array of add credit plugin entity type instances.
   *
   * @return \Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeInterface[]
   *   An array of add credit plugin instances.
   */
  public function getPlugins(): array;

  /**
   * Returns an instance of a plugin based on the give plugin id.
   *
   * @param string $plugin_id
   *   The plugin id.
   *
   * @return \Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeInterface|null
   *   An instance of add credit entity type plugin.
   */
  public function getPluginById(string $plugin_id): ?AddCreditEntityTypeInterface;

  /**
   * Finds an instance of a plugin from the route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeInterface|null
   *   An instance of add credit entity type plugin.
   */
  public function getPluginFromRouteMatch(RouteMatchInterface $route_match): ?AddCreditEntityTypeInterface;

  /**
   * Finds the entity from the route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity from the route.
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match): ?EntityInterface;

  /**
   * Determines access to the current route based on plugin access callbacks.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface|null
   *   Whether or not the user has access to the entity.
   */
  public function checkAccessFromRouteMatch(RouteMatchInterface $route_match, AccountInterface $account): ?AccessResultInterface;

}
