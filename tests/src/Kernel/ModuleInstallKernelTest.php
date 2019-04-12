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

use Drupal;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests module install.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class ModuleInstallKernelTest extends KernelTestBase {
  use UserCreationTrait;

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);

    // Install the user module.
    Drupal::service('module_installer')->install(['user', 'apigee_edge']);

    // Create an admin user.
    $this->admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($this->admin);

    $this->installEntitySchema('file');
  }

  /**
   * Test module installation.
   *
   * This makes sure the `apigee_edge` and `apigee_m10n` modules can be
   * installed at the same time.
   */
  public function testModuleInstall() {
    Drupal::service('module_installer')->install(['apigee_m10n']);
    // Installing modules updates the container and needs a router rebuild.
    $this->container = \Drupal::getContainer();
    $this->container->get('router.builder')->rebuildIfNeeded();

    // Gets the uninstall page.
    $request = Request::create(Url::fromRoute('system.modules_uninstall')->toString());
    $response = $this->container->get('http_kernel')->handle($request);
    $this->setRawContent($response->getContent());

    // Make sure both modules are on the uninstall page.
    $this->assertText('Uninstall Apigee Edge module');
    $this->assertText('Uninstall Apigee Monetization module');

    // Check the installed permissions.
    $authenticated_role_permissions = Role::load(AccountInterface::AUTHENTICATED_ROLE)->getPermissions();
    foreach (MonetizationInterface::DEFAULT_AUTHENTICATED_PERMISSIONS as $permission_name) {
      static::assertTrue(in_array($permission_name, $authenticated_role_permissions));
    }

    // Test uninstalling the monetization module.
    Drupal::service('module_installer')->uninstall(['apigee_m10n']);
  }

}
