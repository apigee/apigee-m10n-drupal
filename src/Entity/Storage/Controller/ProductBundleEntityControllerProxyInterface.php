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

/**
 * Api product bundle storage controller proxy interface.
 */
interface ProductBundleEntityControllerProxyInterface {

  /**
   * Gets all available product bundles for a developer.
   *
   * @param string $developerId
   *   Id or email address of the developer.
   * @param bool $active
   *   Whether to show only API bundles with active rate plans or not.
   * @param bool $allAvailable
   *   Whether to show all available bundles or only bundles with developer
   *   specific rate plans.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\ApiPackageInterface[]
   *   An array of ApiPackage monetization SDK entities.
   *
   * @see https://docs.apigee.com/api-platform/monetization/api-product-bundles
   */
  public function getAvailableProductBundlesByDeveloper(string $developerId, bool $active = FALSE, bool $allAvailable = TRUE): array;

  /**
   * Gets all available product bundles for a company.
   *
   * @param string $company
   *   Name of a company.
   * @param bool $active
   *   Whether to show only bundles with active rate plans or not.
   * @param bool $allAvailable
   *   Whether to show all available bundles or only bundles with company
   *   specific rate plans.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\ApiPackageInterface[]
   *   An array of ApiPackage monetization SDK entities.
   *
   * @see https://docs.apigee.com/api-platform/monetization/api-product-bundles
   */
  public function getAvailableProductBundlesByTeam(string $company, bool $active = FALSE, bool $allAvailable = TRUE): array;

  /**
   * Gets all product bundles.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\ApiPackageInterface[]
   *   An array of ApiPackage monetization SDK entities.
   *
   * @see https://docs.apigee.com/api-platform/monetization/api-product-bundles
   */
  public function getEntities();

}
