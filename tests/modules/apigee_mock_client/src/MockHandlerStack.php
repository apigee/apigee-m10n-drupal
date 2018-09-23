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

use Apigee\Edge\Exception\ClientErrorException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
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

      // Add the default headers if headers aren't defined in the response catalog.
      $headers = isset($this->responses[$id]['headers']) ? $this->responses[$id]['headers'] : [
        'content-type' => 'application/json;charset=utf-8'
      ];
      // Set the default status code.
      $status_code = !empty($this->responses[$id]['status_code']) ? $this->responses[$id]['status_code'] : 200;
      $status_code = !empty($replacements['status_code']) ? $replacements['status_code'] : $status_code;
      // Make replacements inside the response body and append the response.
      $this->append(new Response(
        $status_code,
        $headers,
        strtr($this->responses[$id]['body'], $replacements)
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __invoke(RequestInterface $request, array $options) {
    try {
      /** @var \Psr\Http\Message\ResponseInterface $response */
      return parent::__invoke($request, $options);
    } catch (\Exception $ex) {
      // Fake a client error so the error gets reported during testing. The
      // `apigee_edge_user_presave` is catching errors in a way that this error
      // never gets reported to PHPUnit. This won't work for all use cases.
      //
      // @todo: Find another way to ensure an exception gets registered with PHPUnit.
      throw new ClientErrorException(new Response(500, [
        'Content-Type' => 'application/json',
      ], '{"code": "4242", "message": "'.$ex->getMessage().'"}'), $request, $ex->getMessage(), $ex->getCode(), $ex);
    }
  }
}
