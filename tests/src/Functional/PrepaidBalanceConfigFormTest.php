<?php

/**
 * Copyright 2019 Google Inc.
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

namespace Drupal\Tests\apigee_m10n\FunctionalJavascript;

use Drupal\apigee_m10n\Form\PrepaidBalanceConfigForm;
use Drupal\Core\Url;
use Drupal\Tests\apigee_m10n\Functional\MonetizationFunctionalTestBase;

/**
 * UI tests for the prepaid balance config form.
 *
 * @group apigee_m10n
 * @group apigee_m10n_functional
 *
 * @coversDefaultClass \Drupal\apigee_m10n\Form\PrepaidBalanceConfigForm
 */
class PrepaidBalanceConfigFormTest extends MonetizationFunctionalTestBase {

  /**
   * The developer.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->developer = $this->createAccount(['administer apigee monetization']);
    $this->drupalLogin($this->developer);
  }

  /**
   * Tests the config form.
   *
   * @covers ::buildForm
   * @covers ::submitForm
   */
  public function testPrepaidBalanceConfigForm() {
    $this->warmOrganizationCache();
    $this->drupalGet(Url::fromRoute('apigee_m10n.settings.prepaid_balance')->toString());
    $this->assertSession()->pageTextContains('Prepaid balance');

    $this->submitForm([
      'max_age' => 1800,
      'enable_insufficient_funds_workflow' => FALSE,
      'max_statement_history_months' => 24,
    ], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Check if config were saved.
    $config = $this->config(PrepaidBalanceConfigForm::CONFIG_NAME);
    $this->assertEquals(1800, $config->get('cache.max_age'));
    $this->assertFalse($config->get('enable_insufficient_funds_workflow'));
    $this->assertEquals(24, $config->get('max_statement_history_months'));
  }

}
