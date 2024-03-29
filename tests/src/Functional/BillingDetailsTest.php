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

/**
 * Class BillingDetailsTest.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 */
class BillingDetailsTest extends MonetizationFunctionalTestBase {

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
  protected function setUp(): void {
    parent::setUp();

    $this->warmOrganizationCache();

    // If the user doesn't have the "view any monetization billing details"
    // permission, they should get access denied.
    $this->developer = $this->createAccount([]);

    $this->drupalLogin($this->developer);
  }

  /**
   * Tests for `Billing Details` page.
   *
   * @throws \Exception
   */
  public function testBillingDetailsPageView() {
    $this->stack->queueMockResponse([
      'developer_mint' => [
        'mail'  => ['value' => $this->developer->getEmail()],
        'uuid' => ['value' => '123123123'],
        'first_name' => ['value' => 'First Name'],
        'last_name' => ['value' => 'Last Name'],
        'name' => ['value' => $this->developer->getEmail()],
        'org_name' => 'Test Org',
      ],
    ]);

    $this->drupalGet(Url::fromRoute('apigee_monetization.profile', [
      'user' => $this->developer->id(),
    ]));

    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');
    $this->assertSame($this->developer->getEmail(), $this->getSession()->getPage()->findById('edit-legal-company-name')->getValue());
    $this->assertCssElementContains('#edit-billing div', 'PREPAID');
  }

}
