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

namespace Drupal\apigee_mock_client;

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

  /**
   * Queue a response that is in the catalog. Dynamic values can be passed and
   * will be replaced in the response. For example `['foo' => [':bar' => 'baz']]`
   * will load the catalog entry named `foo `and replace `:bar` with "baz" in the
   * body text.
   *
   * @param string|array $response_ids
   *   The response id to queue in one of the following formats:
   *     - `'foo'`
   *     - `['foo', 'bar']` // Queue multiple responses.
   *     - `['foo' => [':bar' => 'baz']]` // w/ replacements for response body.
   */
  public function queueFromResponseFile($response_ids) {
    if (empty($this->responses)) {
      // Get the module path for this module.
      $module_path = \Drupal::moduleHandler()->getModule('apigee_mock_client')->getPath();
      $this->responses = Yaml::parseFile($module_path . '/response_catalog.yml');
    }
    foreach ((array) $response_ids as $index => $item) {
      // The catalog id should either be the item itself or the keys if an associative array has been passed.
      $id = !is_array($item) ? $item : $index;
      // Body text can have elements replaced in it for certain values. See: http://php.net/strtr
      $replacements = is_array($item) ? $item : [];

      $this->append(new Response(
        $this->responses[$id]['status_code'],
        $this->responses[$id]['headers'],
        strtr($this->responses[$id]['body'], $replacements))
      );
    }
  }
}
