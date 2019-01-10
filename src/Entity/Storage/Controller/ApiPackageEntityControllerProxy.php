<?php

/*
 * Copyright 2018 Google Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

namespace Drupal\apigee_m10n\Entity\Storage\Controller;

use Apigee\Edge\Entity\EntityInterface;
use Drupal\apigee_m10n\Exception\InvalidOperationException;

/**
 * Api Package storage controller proxy.
 */
class ApiPackageEntityControllerProxy extends MonetizationEntityControllerProxy implements ApiPackageEntityControllerProxyInterface {

  /**
   * The decorated controller from the SDK.
   *
   * @var \Apigee\Edge\Api\Monetization\Controller\ApiPackageControllerInterface
   */
  private $controller;

  /**
   * ApiPackageEntityControllerProxy constructor.
   *
   * @param \Apigee\Edge\Api\Monetization\Controller\ApiPackageControllerInterface $controller
   *   A monetization package controller from the SDK.
   */
  public function __construct($controller) {
    parent::__construct($controller);

    $this->controller = $controller;
  }

  /**
   * {@inheritdoc}
   */
  public function update(EntityInterface $entity): void {
    throw new InvalidOperationException('Api packages can\'t be updated directly. See: <https://docs.apigee.com/api-platform/monetization/create-api-packages#createpackapi>.');
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableApiPackagesByDeveloper(string $developerId, bool $active = FALSE, bool $allAvailable = TRUE): array {
    return $this->controller->getAvailableApiPackagesByDeveloper($developerId, $active, $allAvailable);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableApiPackagesByCompany(string $company, bool $active = FALSE, bool $allAvailable = TRUE): array {
    return $this->controller->getAvailableApiPackagesByDeveloper($company, $active, $allAvailable);
  }

}
