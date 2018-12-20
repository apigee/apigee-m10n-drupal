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

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
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
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('user', ['users_data']);
    $this->installSchema('system', ['sequences']);

    // Create an admin user.
    $this->admin = $this->createUser([], NULL, TRUE);
    $this->setCurrentUser($this->admin);
  }

  /**
   * Test module installation.
   *
   * This makes sure the `apigee_edge` and `apigee_m10n` modules can be
   * installed at the same time.
   */
  public function testModuleInstall() {
    \Drupal::service('module_installer')->install(['apigee_edge', 'apigee_m10n']);
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
  }

}
