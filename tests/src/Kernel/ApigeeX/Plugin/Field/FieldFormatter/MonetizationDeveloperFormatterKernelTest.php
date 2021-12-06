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

use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\apigee_m10n\Plugin\Field\FieldFormatter\MonetizationDeveloperFormatter;
use Drupal\apigee_m10n\Plugin\Field\FieldType\MonetizationDeveloperFieldItem;
use Drupal\Core\Field\FieldItemList;
use Drupal\Tests\apigee_m10n\Kernel\ApigeeX\MonetizationKernelTestBase;

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
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
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
