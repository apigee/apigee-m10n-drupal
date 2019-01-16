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

namespace Drupal\Tests\apigee_m10n\Kernel;

use Drupal\apigee_edge\Entity\Developer;

/**
 * AutoAssignLegalNameKernelTest tests.
 */
class AutoAssignLegalNameKernelTest extends MonetizationKernelTestBase {

  /**
   * The developer drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setUp();

    // Setup for creating a user.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);
    $this->installEntitySchema('user');
    $this->developer = $this->createAccount(['view rate_plan']);
    $this->setCurrentUser($this->developer);

  }

  /**
   * Tests legal name auto assignment routine.
   */
  public function testAutoAssignLegalName() {
    $this->stack
      ->queueMockResponse([
        'developer' => [
          'mail'  => ['value' => $this->developer->getEmail()],
          'uuid' => ['value' => rand(0, 1000)],
          'first_name' => ['value' => 'First Name'],
          'last_name' => ['value' => 'Last Name'],
          'name' => ['value' => $this->developer->getEmail()],
          'org_name' => 'Test Org',
        ]
      ]);
    $current_developer = Developer::load($this->developer->getEmail());
    static::assertSame($this->developer->getEmail(), $current_developer->getAttributeValue('MINT_DEVELOPER_LEGAL_NAME'));
  }

}
