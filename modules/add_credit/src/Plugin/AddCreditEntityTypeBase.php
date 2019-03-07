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
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base class for add credit entity type plugins.
 */
abstract class AddCreditEntityTypeBase extends PluginBase implements AddCreditEntityTypeInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AddCreditEntityTypeBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->pluginDefinition['label'];
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

    /** @var \Drupal\Core\Access\AccessResultReasonInterface $result */
    $result = AccessResult::allowedIf(
      $account->hasPermission("add credit to any $plugin_id prepaid balance")
      || ($account->hasPermission("add credit to own $plugin_id prepaid balance") && $account->id() === $entity->id())
    );

    return $result->isAllowed() ? $result : AccessResult::forbidden($result->getReason());
  }

}
