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

use Apigee\Edge\Api\Monetization\Entity\CompanyInterface;
use Apigee\Edge\Api\Monetization\Entity\DeveloperInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Apigee Monetization Add Credit base class.
 *
 * @package Drupal\apigee_m10n_add_credit
 */
class AddCreditMonetization implements AddCreditMonetizationInterface {

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
    $entities = $this->sdkControllerFactory->legalEntityController()->getEntities();

    $return = [];
    /** @var \Apigee\Edge\Entity\EntityInterface $entity */
    foreach ($entities as $entity) {
      $return[$this->getEntityTypeId($entity)][] = $entity;
    }

    return $return;
  }

  /**
   * Helper to get an entity type from entity.
   *
   * @param \Apigee\Edge\Api\Monetization\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return string
   *   The entity type id.
   *
   * @throws \ReflectionException
   */
  protected function getEntityTypeId($entity) {
    if ($entity instanceof DeveloperInterface) {
      return 'developer';
    }

    if ($entity instanceof CompanyInterface) {
      return 'team';
    }

    return (new \ReflectionClass($entity))->getShortName();
  }

}
