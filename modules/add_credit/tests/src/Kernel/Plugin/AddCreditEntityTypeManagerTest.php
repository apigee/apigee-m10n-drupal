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

namespace Drupal\Tests\apigee_m10n_add_credit\Kernel\Plugin;

use Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeInterface;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Tests the AddCreditEntityTypeManager plugin.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_kernel
 *
 * @coversDefaultClass \Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeManager
 */
class AddCreditEntityTypeManagerTest extends MonetizationKernelTestBase {

  /**
   * The add credit entity type plugin manager.
   *
   * @var \Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeManagerInterface
   */
  protected $manager;

  /**
   * The current user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Base modules.
    'key',
    'file',
    'apigee_edge',
    'apigee_m10n',
    'apigee_mock_client',
    'system',

    // Modules for this test.
    'apigee_m10n_add_credit',
    'commerce_order',
    'commerce_price',
    'commerce_product',
    'commerce',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequence']);
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'apigee_m10n_add_credit',
      'user',
      'system',
    ]);
    $this->installEntitySchema('user');

    $this->manager = $this->container->get('plugin.manager.apigee_add_credit_entity_type');
  }

  /**
   * Tests retrieving plugins for the manager.
   *
   * @covers ::getPlugins
   * @covers ::getPluginById
   * @covers ::getPermissions
   * @covers ::getEntities
   */
  public function testAddCreditDeveloperPlugin() {
    $plugins = $this->manager->getPlugins();
    $this->assertCount(1, $plugins);

    $plugin = $plugins['developer'];
    $this->assertInstanceOf(AddCreditEntityTypeInterface::class, $plugin);
    $this->assertCount(1, $this->manager->getPlugins($plugin->getPluginId()));

    // Test permissions.
    $permissions = $this->manager->getPermissions();
    $this->assertCount(2, $permissions);
    $this->assertArrayHasKey('add credit to own developer prepaid balance', $permissions);
    $this->assertArrayHasKey('add credit to any developer prepaid balance', $permissions);

    // Create a user with proper permission.
    $this->currentUser = $this->createAccount(['add credit to own developer prepaid balance']);
    $this->setCurrentUser($this->currentUser);
    $this->queueDeveloperResponse($this->currentUser);
    $this->stack->queueMockResponse([
      'get-developers' => [
        'developers' => [$this->currentUser],
        'org_name' => $this->sdk_connector->getOrganization(),
      ]
    ]);
    $this->assertCount(1, $this->manager->getEntities($this->currentUser)['developer']);
  }

}
