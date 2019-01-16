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

use Drupal\Core\Url;
use Drupal\user\Entity\Role;

/**
 * Class AutoAssignLegalNameTest.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class AutoAssignLegalNameTest extends MonetizationFunctionalTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function setUp() {
    parent::setUp();
    $this->developer = $this->createAccount([]);
    $this->drupalLogin($this->developer);
  }

  /**
   * Tests for auto assign legal name functionality.
   *
   * @throws \Exception
   */
  public function testLegalNameAutoassignment() {
    $user_roles = $this->developer->getRoles();
    $this->grantPermissions(Role::load(reset($user_roles)), ['subscribe rate_plan']);

    $this->queueOrg();
    $this->stack->queueMockResponse([
      'developer' => [
        'mail'  => ['value' => $this->developer->getEmail()],
        'uuid' => ['value' => rand(0, 1000)],
        'first_name' => ['value' => 'First Name'],
        'last_name' => ['value' => 'Last Name'],
        'name' => ['value' => $this->developer->getEmail()],
        'org_name' => 'Test Org',
      ]
    ]);

    $this->drupalGet(Url::fromRoute('apigee_monetization.profile', [
      'user' => $this->developer->id(),
    ]));

    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');
    $this->assertSame($this->developer->getEmail(), $this->getSession()->getPage()->findById('edit-legal-company-name')->getValue());

  }

}
