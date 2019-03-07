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

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for add credit entity type plugins.
 */
interface AddCreditEntityTypeInterface extends PluginInspectionInterface {

  /**
   * Returns the label for the plugin.
   *
   * @return string
   *   The label of the plugin.
   */
  public function getLabel(): string;

  /**
   * Returns an array of permissions for this plugin.
   *
   * @return array
   *   An array of permissions.
   */
  public function getPermissions(): array;

  /**
   * Returns an array of entities available to the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entities.
   */
  public function getEntities(AccountInterface $account): array;

  /**
   * Returns the entity id for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The entity id.
   */
  public function getEntityId(EntityInterface $entity): string;

  /**
   * Determines if the current user has access based on the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check for access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether or not the user has access to the entity.
   */
  public function access(EntityInterface $entity, AccountInterface $account): AccessResultInterface;

}
