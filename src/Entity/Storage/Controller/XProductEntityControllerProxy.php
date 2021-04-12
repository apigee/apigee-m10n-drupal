<?php

/*
 * Copyright 2021 Google Inc.
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
 * XProduct storage controller proxy.
 */
class XProductEntityControllerProxy extends MonetizationEntityControllerProxy implements XProductEntityControllerProxyInterface {

  /**
   * {@inheritdoc}
   */
  protected function controller(): EntityControllerInterface {
    return $this->controllerFactory()->apixProductController();
  }

  /**
   * {@inheritdoc}
   */
  public function update(EntityInterface $entity): void {
    throw new InvalidOperationException('XProduct can\'t be updated directly. See: <https://docs.apigee.com/api-platform/monetization/create-api-packages#createpackapi>.');
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailablexProductByDeveloper(string $developerId, bool $active = FALSE, bool $allAvailable = TRUE): array {
    // XProduct are called xpackages in the PHP API client.
    return $this->controller()->getAvailableApixProductsByDeveloper($developerId, $active, $allAvailable);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableProductBundlesByTeam(string $company, bool $active = FALSE, bool $allAvailable = TRUE): array {
    // Product are called packages in the PHP API client.
    return $this->controller()->getAvailableApixProductsByCompany($company, $active, $allAvailable);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntities() {
    return $this->controller()->getEntities();
  }

}
