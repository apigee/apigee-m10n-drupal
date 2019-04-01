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
   * Helper to retrieve an array of permissions defined by all plugins.
   *
   * @return array
   *   An array of permissions.
   */
  public function getPermissions(): array;

  /**
   * Returns an array of entities the given account has access to.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return array
   *   An array of entities.
   */
  public function getEntities(AccountInterface $account): array;

}
