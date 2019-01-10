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
 * Api Package storage controller proxy interface.
 */
interface ApiPackageEntityControllerProxyInterface {

  /**
   * Gets all available API packages for a developer.
   *
   * @param string $developerId
   *   Id or email address of the developer.
   * @param bool $active
   *   Whether to show only API packages with active rate plans or not.
   * @param bool $allAvailable
   *   Whether to show all available packages or only packages with developer
   *   specific rate plans.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\ApiPackageInterface[]
   *   An array of ApiPackage monetization SDK entities.
   *
   * @see https://apidocs.apigee.com/monetize/apis/get/organizations/%7Borg_name%7D/developers/%7Bdeveloper_id%7D/monetization-packages
   */
  public function getAvailableApiPackagesByDeveloper(string $developerId, bool $active = FALSE, bool $allAvailable = TRUE): array;

  /**
   * Gets all available API packages for a company.
   *
   * @param string $company
   *   Name of a company.
   * @param bool $active
   *   Whether to show only API packages with active rate plans or not.
   * @param bool $allAvailable
   *   Whether to show all available packages or only packages with company
   *   specific rate plans.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\ApiPackageInterface[]
   *   An array of ApiPackage monetization SDK entities.
   *
   * @see https://apidocs.apigee.com/monetize/apis/get/organizations/%7Borg_name%7D/companies/%7Bdeveloper_id%7D/monetization-packages
   */
  public function getAvailableApiPackagesByCompany(string $company, bool $active = FALSE, bool $allAvailable = TRUE): array;

}
