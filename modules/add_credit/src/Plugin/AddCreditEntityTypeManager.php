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

use Drupal\apigee_m10n_add_credit\Annotation\AddCreditEntityType;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a manager for add credit entity type plugins.
 */
class AddCreditEntityTypeManager extends DefaultPluginManager implements AddCreditEntityTypeManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/AddCreditEntityType',
      $namespaces,
      $module_handler,
      AddCreditEntityTypeInterface::class,
      AddCreditEntityType::class
    );
    $this->alterInfo('add_credit_entity_type_info');
    $this->setCacheBackend($cache_backend, 'add_credit_entity_type_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins(): array {
    $instances = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      $instances[$plugin_id] = $this->createInstance($plugin_id);
    }
    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginById(string $plugin_id): ?AddCreditEntityTypeInterface {
    return $this->getPlugins()[$plugin_id] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions(): array {
    $permissions = [];

    /** @var \Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeInterface $entity_type */
    foreach ($this->getPlugins() as $entity_type) {
      $permissions = array_merge($permissions, $entity_type->getPermissions());
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntities(AccountInterface $account): array {
    $entities = [];
    /** @var \Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeInterface $plugin */
    foreach ($this->getPlugins() as $plugin) {
      if ($plugin_entities = $plugin->getEntities($account)) {
        $entities[$plugin->getPluginId()] = $plugin_entities;
      }
    }
    return $entities;
  }

}
