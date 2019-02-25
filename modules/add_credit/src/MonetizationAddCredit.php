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

namespace Drupal\apigee_m10n_add_credit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Apigee Monetization Add Credit base class.
 *
 * @package Drupal\apigee_m10n_add_credit
 */
class MonetizationAddCredit implements MonetizationAddCreditInterface {

  /**
   * The Add Credit SDK controller.
   *
   * @var \Drupal\apigee_m10n_add_credit\AddCreditSdkControllerFactoryInterface
   */
  protected $sdkControllerFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MonetizationAddCredit constructor.
   *
   * @param \Drupal\apigee_m10n_add_credit\AddCreditSdkControllerFactoryInterface $sdk_controller_factory
   *   The Add Credit SDK controller.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AddCreditSdkControllerFactoryInterface $sdk_controller_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->sdkControllerFactory = $sdk_controller_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getLegalEntities(AccountInterface $account): array {
    // This is using the entity storage to get developer entities.
    // In case this can be pulled from the monetization API, switch to
    // return $this->sdkControllerFactory->legalEntityController()->getEntities().
    $entities = $developers = [];

    if ($account->hasPermission('add credit to own developer prepaid balance')) {
      $developers = $this->entityTypeManager->getStorage('developer')->loadMultiple([$account->getEmail()]);
    }

    if ($account->hasPermission('add credit to any developer prepaid balance')) {
      $developers = \Drupal::entityTypeManager()->getStorage('developer')->loadMultiple();
    }

    /** @var \Drupal\apigee_edge\Entity\DeveloperInterface $developer */
    foreach ($developers as $developer) {
      if ($developer->getOwner()) {
        $entities['developer'][$developer->id()] = $developer;
      }
    }

    return $entities;
  }

}
