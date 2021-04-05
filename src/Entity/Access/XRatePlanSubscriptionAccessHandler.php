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

use Apigee\Edge\Api\Monetization\Entity\CompanyRatePlanInterface;
use Apigee\Edge\Api\Monetization\Entity\DeveloperCategoryRatePlanInterface;
use Apigee\Edge\Api\Monetization\Entity\DeveloperRatePlanInterface;
use Drupal\apigee_m10n\Entity\XRatePlanInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access check for subscribing an account to a xrate plan.
 */
class XRatePlanSubscriptionAccessHandler implements AccessInterface, EntityHandlerInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a XRatePlanSubscriptionAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Checks access to see if an account can subscribe to a xrate plan.
   *
   * This is different than access control, as an admin might have access to
   * view and purchase a rate plan as any developer, but they might not be able
   * to subscribe to the plan themselves.
   *
   * @param \Drupal\apigee_m10n\Entity\XRatePlanInterface $rate_plan_entity
   *   The rate plan drupal entity.
   * @param \Drupal\user\UserInterface $account
   *   The account for which we try to determine subscription access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result specifying if subscription to the rate plan is or not allowed.
   */
  public function access(XRatePlanInterface $rate_plan_entity, UserInterface $account): AccessResultInterface {
    /** @var \Apigee\Edge\Api\ApigeeX\Entity\RatePlanInterface $rate_plan */
    $rate_plan = $rate_plan_entity->decorated();

    // If rate plan is a developer category rate plan, deny access if developer
    // does not belong to rate_plan category.
    /** @var \Drupal\apigee_edge\Entity\DeveloperInterface $developer */
    if ($rate_plan instanceof DeveloperCategoryRatePlanInterface) {
      $developer_storage = $this->entityTypeManager->getStorage('developer');
      if (($category = $rate_plan->getDeveloperCategory()) && ($developer = $developer_storage->load($account->getEmail()))) {
        return AccessResult::allowedIf(($developer_category = $developer->decorated()->getAttributeValue('MINT_DEVELOPER_CATEGORY')) && ($category->id() === $developer_category));
      }
      return AccessResult::forbidden("User {$developer->getEmail()} missing required developer category.");
    }

    // If rate plan is a developer rate plan, and the assigned developer is
    // different from account, deny access.
    if ($rate_plan instanceof DeveloperRatePlanInterface) {
      if ($developer = $rate_plan->getDeveloper()) {
        return AccessResult::allowedIf($account->getEmail() === $developer->getEmail());
      }
      return AccessResult::forbidden("User {$developer->getEmail()} cannot subscribe to developer rate plan.");
    }

    if ($rate_plan instanceof CompanyRatePlanInterface) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

}
