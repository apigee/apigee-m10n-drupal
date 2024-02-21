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

namespace Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityType;

use Drupal\apigee_edge\Entity\DeveloperInterface;
use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\apigee_m10n_add_credit\Annotation\AddCreditEntityType;
use Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a developer add credit entity type plugin.
 *
 * @AddCreditEntityType(
 *   id = "developer",
 *   label = "Developer",
 * )
 */
class Developer extends AddCreditEntityTypeBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

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
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteEntityTypeId(): string {
    // Developer entity type uses /user/{user} in the route.
    // We override it here so that the user param is properly converted.
    return 'user';
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityId(EntityInterface $entity): string {
    /** @var \Drupal\user\UserInterface $entity */
    return $entity->getEmail();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntities(AccountInterface $account): array {
    $ids = [];
    if ($account->hasPermission('add credit to any developer prepaid balance')) {
      $target_account = $this->request->getCurrentRequest()->get(AddCreditConfig::TARGET_FIELD_NAME) ?? [
        'target_type' => 'developer',
        'target_id' => $account->getEmail(),
      ];

      // Instead of loading all users, load target user and current as fallback.
      $ids = [$target_account['target_id'], $account->getEmail()];
    }
    elseif ($account->hasPermission('add credit to own developer prepaid balance')) {
      $ids = [$account->getEmail()];
    }

    // Filter out developers with no user accounts.
    return array_filter($this->entityTypeManager->getStorage('developer')->loadMultiple($ids), function (DeveloperInterface $developer) {
      return $developer->getOwnerId();
    });
  }

}
