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

namespace Drupal\apigee_m10n_teams;

/**
 * Trait for getting the team SDK controller factory.
 */
trait TeamSdkControllerFactoryAwareTrait {

  /**
   * Get the controller factory.
   *
   * Since some classes are used during install, this is used instead of
   * dependency injection to avoid an issue with the sdk controller credentials
   * not yet being available when the service is instantiated.
   *
   * @return \Drupal\apigee_m10n_teams\TeamSdkControllerFactoryInterface
   *   The controller factory.
   */
  public function teamControllerFactory(): TeamSdkControllerFactoryInterface {
    return \Drupal::service('apigee_m10n_teams.sdk_controller_factory');
  }

}
