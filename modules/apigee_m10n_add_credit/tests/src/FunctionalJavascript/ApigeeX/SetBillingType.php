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
 * Tests the default billing type on registration.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_functional
 */
class SetBillingType extends AddCreditFunctionalJavascriptTestBase {

  /**
   * A developer user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

  }

  /**
   * Tests the default billing type on registration.
   */
  public function testUpdateBillingType() {
    $this->warmApigeexOrganizationCache();

    // Create and sign in a user with view own billing details permissions
    // and billing type as Prepaid.
    $this->stack->queueMockResponse([
      'post-apigeex-billing-type'
    ]);
    $this->developer1 = $this->signIn(['view own billing details'], 'Prepaid');

    $this->queueApigeexDeveloperResponse($this->developer1);
    $this->stack->queueMockResponse([
      'post-apigeex-billing-type'
    ]);

    $this->queueApigeexDeveloperResponse($this->developer1);
    $this->stack->queueMockResponse([
      'get-apigeex-billing-type'
    ]);

    $this->drupalGet(Url::fromRoute('apigee_monetization.xprofile', [
      'user' => $this->developer1->id(),
    ]));

    // Users billing type should be the one that is set in general setting form.
    $this->assertSession()->responseContains('Billing Details');
    $this->assertSession()->responseContains('Prepaid');

    $this->stack->queueMockResponse([
      'post-apigeex-billing-type' => ['billingType' => 'Postpaid']
    ]);

    // Create and sign in a user with view own billing details permissions
    // and billing type as Postpaid.
    $this->developer = $this->signIn(['view own billing details'], 'Postpaid');

    $this->queueApigeexDeveloperResponse($this->developer);
    $this->stack->queueMockResponse([
      'post-apigeex-billing-type' => ['billingType' => 'Postpaid']
    ]);

    $this->queueApigeexDeveloperResponse($this->developer);
    $this->stack->queueMockResponse([
      'get-apigeex-billing-type'  => ['billingType' => 'Postpaid']
    ]);

    $this->drupalGet(Url::fromRoute('apigee_monetization.xprofile', [
      'user' => $this->developer->id(),
    ]));

    // Users billing type should be the one that is set in general setting form.
    $this->assertSession()->responseContains('Billing Details');
    $this->assertSession()->responseContains('Postpaid');

  }

}
