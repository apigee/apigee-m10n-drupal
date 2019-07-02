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

use Apigee\Edge\Controller\EntityControllerInterface;
use Apigee\Edge\Entity\EntityInterface;
use Drupal\apigee_m10n\Exception\InvalidOperationException;

/**
 * Product bundle storage controller proxy.
 */
class ProductBundleEntityControllerProxy extends MonetizationEntityControllerProxy implements ProductBundleEntityControllerProxyInterface {

  /**
   * {@inheritdoc}
   */
  protected function controller(): EntityControllerInterface {
    return $this->controllerFactory()->apiPackageController();
  }

  /**
   * {@inheritdoc}
   */
  public function update(EntityInterface $entity): void {
    throw new InvalidOperationException('Product bundles can\'t be updated directly. See: <https://docs.apigee.com/api-platform/monetization/create-api-packages#createpackapi>.');
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableProductBundlesByDeveloper(string $developerId, bool $active = FALSE, bool $allAvailable = TRUE): array {
    // Product bundles are called packages in the PHP API client.
    return $this->controller()->getAvailableApiPackagesByDeveloper($developerId, $active, $allAvailable);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableProductBundlesByTeam(string $company, bool $active = FALSE, bool $allAvailable = TRUE): array {
    // Product bundles are called packages in the PHP API client.
    return $this->controller()->getAvailableApiPackagesByCompany($company, $active, $allAvailable);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntities() {
    return $this->controller()->getEntities();
  }

}
