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

namespace Drupal\Tests\apigee_m10n\Kernel\ApigeeX\Plugin\Field\FieldFormatter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldItemList;
use Drupal\Tests\apigee_m10n\Kernel\ApigeeX\MonetizationKernelTestBase;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\apigee_m10n\Plugin\Field\FieldFormatter\MonetizationDeveloperFormatter;
use Drupal\apigee_m10n\Plugin\Field\FieldType\MonetizationDeveloperFieldItem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;

/**
 * Test the `apigee_monetization_developer` field formatter.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class MonetizationDeveloperFormatterKernelTest extends MonetizationKernelTestBase {

  /**
   * The formatter manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Test X product.
   *
   * @var \Drupal\apigee_m10n\Entity\XProductInterface
   */
  protected $xproduct;

  /**
   * Test rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\XRatePlanInterface
   */
  protected $ratePlan;

  /**
   * Test purchased product.
   *
   * @var \Drupal\apigee_m10n\Entity\PurchasedProductInterface
   */
  protected $purchasedProduct;

  /**
   * Drupal developer user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $developer;

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Ensure the private stream wrapper service is registered in the container
    // for this test. This is sometimes necessary in kernel tests if the
    // full module set isn't loaded.
    if (!$container->hasDefinition('stream_wrapper.private')) {
      $container->register('stream_wrapper.private', 'Drupal\Core\StreamWrapper\PrivateStream')
        ->addTag('stream_wrapper', ['scheme' => 'private']);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  protected function setUp(): void {
    parent::setUp();

    // At this point, parent::setUp() should have run, and the register()
    // method above should have been called, ensuring the stream_wrapper.private
    // service definition exists.
    // The apigee_edge base classes likely set 'file_private_path'
    // to 'vfs://root/private'.
    if (!in_array('private', stream_get_wrappers())) {
      $settings = Settings::getInstance();
      $actual_private_path = $settings ? $settings->get('file_private_path') : 'Settings service not available';
      throw new \RuntimeException(
          "The 'private' stream wrapper is NOT registered after parent::setUp() and register(). " .
          "Actual 'file_private_path' setting: " . var_export($actual_private_path, TRUE)
      );
    }

    // Get the file system service.
    $file_system = $this->container->get('file_system');
    $options = FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS;

    // Prepare the directories within the file system (likely VFS).
    $oauth_uri = 'private://apigee/oauth';
    if (!$file_system->prepareDirectory($oauth_uri, $options)) {
      throw new \RuntimeException("Failed to create directory at " . $oauth_uri);
    }

    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
    ]);

    // Do not use user 1.
    $this->createAccount();

    $this->developer = $this->createAccount(MonetizationInterface::DEFAULT_AUTHENTICATED_PERMISSIONS);

    // Get pre-configured token storage service for testing.
    $this->storeToken();

    $this->stack->reset();
    $this->xproduct = $this->createApigeexProduct();
    $this->stack->reset();
    $this->ratePlan = $this->createRatePlan($this->xproduct);

    $this->setCurrentUser($this->developer);
    $this->stack->reset();
    $this->purchasedProduct = $this->createPurchasedProduct($this->developer, $this->ratePlan);
    $this->stack->reset();

    $this->formatterManager = $this->container->get('plugin.manager.field.formatter');
    $this->fieldManager = $this->container->get('entity_field.manager');
  }

  /**
   * Test viewing a purchased product.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function testView() {
    $item_list = $this->purchasedProduct->get('developer');

    static::assertInstanceOf(FieldItemList::class, $item_list);
    static::assertInstanceOf(MonetizationDeveloperFieldItem::class, $item_list->get(0));
    static::assertSame($this->purchasedProduct->getDeveloper()->id(), $item_list->get(0)->value->id());
    /** @var \Drupal\apigee_m10n\Plugin\Field\FieldFormatter\MonetizationDeveloperFormatter $instance */
    $instance = $this->formatterManager->createInstance('apigee_monetization_developer', [
      'field_definition' => $this->fieldManager->getBaseFieldDefinitions('purchased_product')['developer'],
      'settings' => [],
      'label' => TRUE,
      'view_mode' => 'default',
      'third_party_settings' => [],
    ]);
    static::assertInstanceOf(MonetizationDeveloperFormatter::class, $instance);

    // Render the field item.
    $build = $instance->view($item_list);

    static::assertSame('Developer', (string) $build['#title']);
    static::assertTrue($build['#label_display']);
    static::assertSame($this->purchasedProduct->getDeveloper()->getName(), (string) $build[0]['#markup']);

    $this->render($build);
    $this->assertText($this->purchasedProduct->getDeveloper()->getName());
  }

}
