<?php

/**
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

namespace Drupal\Tests\apigee_m10n\Functional;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\key\Entity\Key;
use Drupal\Tests\apigee_edge\Functional\ApigeeEdgeTestTrait;
use Drupal\Tests\BrowserTestBase;

class MonetizationFunctionalTestBase extends BrowserTestBase {

  use ApigeeEdgeTestTrait;

  protected static $modules = [
    'apigee_edge_test',
//    'apigee_mock_client',
    'system'
  ];

  protected function setUp() {
    parent::setUp();
    $key = Key::create([
      'id' => 'test',
      'label' => 'test',
      'key_type' => 'apigee_edge_basic_auth',
      'key_provider' => 'apigee_edge_environment_variables',
      'key_input' => 'apigee_edge_basic_auth_input',
    ]);
    try {
      $key->save();
    }
    catch (EntityStorageException $exception) {
      self::fail('Could not create key for testing.');
    }

    $this->container->get('state')->set('apigee_edge.auth', [
      'active_key' => 'test',
      'active_key_oauth_token' => '',
    ]);
    $this->container->get('state')->resetCache();
  }

}

