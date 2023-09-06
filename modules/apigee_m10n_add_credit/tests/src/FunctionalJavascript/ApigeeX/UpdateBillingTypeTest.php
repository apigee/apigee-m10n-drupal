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

namespace Drupal\Tests\apigee_m10n_add_credit\FunctionalJavascript\ApigeeX;

use Drupal\Core\Url;

/**
 * Tests the switching of billing type of a developer.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_functional
 */
class UpdateBillingTypeTest extends AddCreditFunctionalJavascriptTestBase {

  /**
   * A developer user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * A developer user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developeruser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

  }

  /**
   * Tests the switching of billing type.
   *
   * @covers \Drupal\apigee_m10n_add_credit\Form\BillingTypeForm
   * @covers \Drupal\apigee_m10n_add_credit\Form\ConfirmUpdateForm
   */
  public function testUpdateBillingType() {
    $this->warmApigeexOrganizationCache();

    // Create and sign in a user with no update any billing type permissions.
    $this->stack->queueMockResponse([
      'post-apigeex-billing-type'
    ]);
    $this->developeruser = $this->signIn([], 'Prepaid');

    $this->drupalGet(Url::fromRoute('apigee_m10n_add_credit.userbillingtype', [
      'user' => $this->developeruser->id(),
    ]));

    // User should see access denied on billing type switching form.
    $this->assertSession()->responseContains('Access denied');

    $this->stack->queueMockResponse([
      'get-apigeex-billing-type'
    ]);

    // Create and sign in a user with update any billing type permissions.
    $this->developer = $this->signIn(['update any billing type'], 'Prepaid');

    $this->queueApigeexDeveloperResponse($this->developeruser);
    $this->stack->queueMockResponse([
      'post-apigeex-billing-type'
    ]);

    $this->queueApigeexDeveloperResponse($this->developeruser);
    $this->stack->queueMockResponse([
      'get-apigeex-billing-type'
    ]);

    $this->drupalGet(Url::fromRoute('apigee_m10n_add_credit.userbillingtype', [
      'user' => $this->developeruser->id(),
    ]));

    $this->assertCssElementContains('h1.page-title', 'Billing Type');
    $this->assertSession()->fieldValueEquals('billingtype', 'prepaid');

    $this->queueApigeexDeveloperResponse($this->developeruser);
    $this->stack->queueMockResponse([
      'post-apigeex-billing-type' => [
        "billingType" => "PREPAID",
      ],
    ]);

    $this->queueApigeexDeveloperResponse($this->developeruser);
    $this->stack->queueMockResponse([
      'get-apigeex-billing-type' => [
        "billingType" => "PREPAID",
      ],
    ]);

    $this->queueApigeexDeveloperResponse($this->developeruser);
    $this->stack->queueMockResponse([
      'post-apigeex-billing-type' => [
        "billingType" => "PREPAID",
      ],
    ]);

    $this->queueApigeexDeveloperResponse($this->developeruser);
    $this->stack->queueMockResponse([
      'get-apigeex-billing-type' => [
        "billingType" => "PREPAID",
      ],
    ]);

    // Test form config.
    $this->submitForm([
      'billingtype' => 'postpaid'
    ], 'Save changes');

    $this->queueApigeexDeveloperResponse($this->developeruser);
    $this->stack->queueMockResponse([
      'post-apigeex-billing-type' => [
        "billingType" => "PREPAID",
      ],
    ]);

    $this->queueApigeexDeveloperResponse($this->developeruser);
    $this->stack->queueMockResponse([
      'get-apigeex-billing-type' => [
        "billingType" => "PREPAID",
      ],
    ]);

    $this->getSession()->getPage()->findButton('Confirm')->click();

    $this->queueApigeexDeveloperResponse($this->developeruser);
    $this->stack->queueMockResponse([
      'get-apigeex-billing-type' => [
        "billingType" => "POSTPAID",
      ],
    ]);

    $this->assertSession()->responseContains('Billing type of the user is saved.');

  }

}
