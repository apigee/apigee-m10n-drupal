<?php
/**
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

namespace Drupal\apigee_m10n_test;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Yaml\Yaml;

class MockHandlerStack extends MockHandler {

  /**
   * @var array
   *
   * Responses that have been loaded from the response file.
   */
  protected $responses;

  public function queueFromResponseFile ($response_ids) {
    if (empty($this->responses)) {
      // Get the module path for this module.
      $module_path = \Drupal::moduleHandler()->getModule('apigee_m10n_test')->getPath();
      $this->responses = Yaml::parseFile($module_path . '/response_catalog.yml');
    }
    foreach ($response_ids as $id) {
      $this->append(new Response(
        $this->responses[$id]['status_code'],
        $this->responses[$id]['headers'],
        $this->responses[$id]['body'])
      );
    }
  }
}
