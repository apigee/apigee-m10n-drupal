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

use Apigee\Edge\Api\Monetization\Controller\DeveloperControllerInterface;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;

/**
 * Interface AddCreditSdkControllerFactoryInterface.
 *
 * @package Drupal\apigee_m10n_add_credit
 */
interface AddCreditSdkControllerFactoryInterface extends ApigeeSdkControllerFactoryInterface {

  /**
   * Creates a monetization legal entity controller.
   *
   * This currently returns a controller which returns both returns both developers and companies.
   *
   * @see \Apigee\Edge\Api\Monetization\Controller\DeveloperController
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\DeveloperControllerInterface
   *   The legal entity controller.
   */
  public function legalEntityController(): DeveloperControllerInterface;

}
