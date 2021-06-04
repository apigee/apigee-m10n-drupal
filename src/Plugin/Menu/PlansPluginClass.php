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

namespace Drupal\apigee_m10n\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Url;

/**
 * Represents a menu link for a listing and buying apis for 4G and 5G.
 */
class PlansPluginClass extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $monetization = \Drupal::service('apigee_m10n.monetization');
    if ($monetization->isOrganizationApigeeXorHybrid()) {
      return (string) 'Buy API';
    }
    else {
      return (string) 'Pricing & plans';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $monetization = \Drupal::service('apigee_m10n.monetization');
    if ($monetization->isOrganizationApigeeXorHybrid()) {
      return (string) 'Buy API';
    }
    else {
      return (string) 'Pricing & plans';
    }
  }

}
