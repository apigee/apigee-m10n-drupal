<?php

/*
 * Copyright 2021 Google Inc.
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

namespace Drupal\apigee_m10n\Entity\Access;

use Apigee\Edge\Api\Monetization\Entity\DeveloperCategoryRatePlanInterface;
use Apigee\Edge\Api\Monetization\Entity\DeveloperRatePlanInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\EntityAccessControlHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the xrate_plan entity.
 */
class XRatePlanAccessControlHandler extends EntityAccessControlHandlerBase implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an access control handler instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($entity_type);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\apigee_m10n\Entity\XRatePlanInterface $entity */
    $access = parent::checkAccess($entity, $operation, $account);

    // Allow access if user has permission `view/purchase xrate_plan as anyone`.
    if ($account->hasPermission("$operation rate_plan as anyone")) {
      return AccessResult::allowed();
    }

    /** @var \Apigee\Edge\Api\ApigeeX\Entity\RatePlanInterface $rate_plan */
    $rate_plan = $entity->decorated();

    // If rate plan is a developer category rate plan, deny access if developer
    // does not belong to rate_plan category.
    /** @var \Drupal\apigee_edge\Entity\DeveloperInterface $developer */
    if ($rate_plan instanceof DeveloperCategoryRatePlanInterface) {
      if (($category = $rate_plan->getDeveloperCategory()) && ($developer = $this->entityTypeManager->getStorage('developer')->load($account->getEmail()))) {
        return AccessResult::allowedIf(($developer_category = $developer->decorated()->getAttributeValue('MINT_DEVELOPER_CATEGORY')) && ($category->id() === $developer_category))
          ->andIf(AccessResult::allowedIfHasPermission($account, "$operation rate_plan"));
      }
      return AccessResult::forbidden("User {$developer->getEmail()} missing required developer category.");
    }

    // If rate plan is a developer xrate plan, and the assigned developer is
    // different from account, deny access.
    if ($rate_plan instanceof DeveloperRatePlanInterface) {
      if ($developer = $rate_plan->getDeveloper()) {
        return AccessResult::allowedIf($account->getEmail() === $developer->getEmail())
          ->andIf(AccessResult::allowedIfHasPermission($account, "$operation rate_plan"));
      }
      return AccessResult::forbidden("User {$developer->getEmail()} cannot view developer rate plan.");
    }

    return $access->andIf(AccessResult::allowedIfHasPermission($account, "$operation rate_plan"));
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::forbidden('Rate plans cannot be created via the developer portal.');
  }

}
