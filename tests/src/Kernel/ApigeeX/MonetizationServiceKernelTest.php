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

namespace Drupal\Tests\apigee_m10n\Kernel\ApigeeX;

use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Form\UserPermissionsForm;
use Psr\Log\LoggerInterface;

/**
 * Tests the testing framework for testing offline.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class MonetizationServiceKernelTest extends MonetizationKernelTestBase {

  /**
   * The monetization service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Install the anonymous user role.
    $this->installConfig('user');

    // Get pre-configured token storage service for testing.
    $this->storeToken();

    $this->monetization = $this->container->get('apigee_m10n.monetization');
    $this->stack->reset();
  }

  /**
   * Tests that monetization service can identifies a monetized organization.
   *
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function testMonetizationEnabled() {
    // Queue a response from the mock server.
    $this->stack->queueMockResponse(['get_apigeex_organization' => ['monetization_enabled' => 'true']]);
    // Execute a client call.
    $is_monetization_enabled = $this->monetization->isMonetizationEnabled();
    self::assertEquals($is_monetization_enabled, TRUE);
  }

  /**
   * Tests a org without monetization enabled.
   *
   * Tests that the monetization service correctly identifies an organization
   * without monetization enabled.
   */
  public function testMonetizationDisabled() {
    if ($this->integration_enabled) {
      $this->markTestSkipped('This test suite is expecting a monetization enabled org. Disable for integration testing.');
    }

    // Queue a response from the mock server.
    $this->stack->queueMockResponse('get_apigeex_organization');

    // Execute a client call.
    $is_monetization_enabled = $this->monetization->isMonetizationEnabled();

    self::assertEquals($is_monetization_enabled, FALSE);
  }

  /**
   * Test the module logger.
   *
   * @throws \Exception
   */
  public function testLogger() {
    $logger = $this->container->get('logger.channel.apigee_m10n');
    static::assertInstanceOf(LoggerInterface::class, $logger);
  }

  /**
   * Tests the user permissions form alter.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function testUserPermissionAlterations() {
    $form_state = new FormState();
    $form = \Drupal::formBuilder()->buildForm(UserPermissionsForm::class, $form_state);
    static::assertInstanceOf(UserPermissionsForm::class, $form_state->getFormObject());

    $protected_permisisons = array_filter(\Drupal::service('user.permissions')->getPermissions(), function ($permission) {
      return ($permission['provider'] === 'apigee_m10n');
    });
    // Make sure all permissions are disabled.
    foreach (array_keys($protected_permisisons) as $permission_name) {
      static::assertTrue($form['permissions'][$permission_name][AccountInterface::ANONYMOUS_ROLE]['#disabled']);
    }

    $anonymous_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $anonymous_role->grantPermission('administer apigee monetization');
    $anonymous_role->save();

    static::assertFalse($anonymous_role->hasPermission('administer apigee monetization'));
  }

}
