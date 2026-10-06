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

namespace Drupal\Tests\apigee_m10n\FunctionalJavascript\ApigeeX;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\apigee_m10n\Traits\ApigeeX\ApigeeMonetizationTestTrait;

/**
 * Setup for functional javascript tests.
 */
class MonetizationFunctionalJavascriptTestBase extends WebDriverTestBase {

  use ApigeeMonetizationTestTrait {
    setUp as baseSetUp;
  }

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'apigee_m10n_test',
    'apigee_mock_api_client',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create new Apigee Edge basic auth key.
    $this->baseSetUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalLogout() {
    $assert_session = $this->assertSession();
    $destination = Url::fromRoute('user.page')->toString();
    $this->drupalGet(Url::fromRoute('user.logout.confirm', options: ['query' => ['destination' => $destination]]));
    $this->submitForm([], 'op', 'user-logout-confirm');
    $assert_session->waitForField('name');
    $assert_session->waitForField('pass');
    $assert_session->fieldExists('name');
    $assert_session->fieldExists('pass');

    $this->drupalResetSession();
  }

  /**
   * {@inheritdoc}
   */
  protected function assertCssElementContains($selector, $text) {
    $this->getSession()->getPage()->waitFor(10, function ($page) use ($selector, $text) {
      try {
        $element = $page->find('css', $selector);
        return $element !== NULL && str_contains($element->getText(), (string) $text);
      }
      catch (\Throwable $e) {
        return FALSE;
      }
    });
    $this->assertSession()->elementTextContains('css', $selector, $text);
  }

}
