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

namespace Drupal\Tests\apigee_m10n\Traits;

/**
 * Helpers to enable the rate plan field for a view display.
 */
trait RatePlansPropertyEnablerTrait {

  /**
   * Helper to enable a the rate plan field for display.
   *
   * @param string $display_mode
   *   The display view mode.
   */
  protected function enableRatePlansForViewDisplay($display_mode = 'default') {
    $config = "core.entity_view_display.package.package.{$display_mode}";

    $package_view_mode = $this->config($config);
    $package_content = $package_view_mode->get('content');
    // Enable `ratePlans`.
    $package_view_mode->set('content', [
      'ratePlans' => [
        'type' => 'entity_reference_entity_view',
        'weight' => 5,
        'region' => 'content',
        'label' => 'above',
        'settings' => [
          'link' => TRUE,
          'view_mode' => 'default',
        ],
        'third_party_settings' => [],
      ],
    ] + $package_content);
    // Removes `ratePlans` from hidden.
    $package_view_mode->set('hidden', array_diff_key($package_view_mode->get('hidden'), ['ratePlans' => 1]));
    $package_view_mode->save();
  }

}
