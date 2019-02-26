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

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a base class for add credit entity type plugins.
 */
abstract class AddCreditEntityTypeBase extends PluginBase implements AddCreditEntityTypeInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPath(): string {
    return $this->pluginDefinition['path'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteEntityTypeId(): string {
    // This defaults to the plugin id in most cases.
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions(): array {
    $plugin_id = $this->getPluginId();

    return [
      "add credit to own $plugin_id prepaid balance" => [
        'title' => $this->t("Add credit to own $plugin_id prepaid balance"),
      ],
      "add credit to any $plugin_id prepaid balance" => [
        'title' => $this->t("Add credit to any $plugin_id prepaid balance"),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityId(EntityInterface $entity): string {
    return $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, AccountInterface $account): AccessResultInterface {
    $plugin_id = $this->getPluginId();

    return AccessResult::allowedIf(
      $account->hasPermission("add credit to any $plugin_id prepaid balance")
      || ($account->hasPermission("add credit to own $plugin_id prepaid balance") && $account->id() === $entity->id())
    );
  }

}
