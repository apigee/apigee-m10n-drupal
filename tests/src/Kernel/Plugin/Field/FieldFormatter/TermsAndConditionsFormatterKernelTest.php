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

namespace Drupal\Tests\apigee_m10n\Kernel\Plugin\Field\FieldFormatter;

use Drupal\apigee_m10n\Plugin\Field\FieldFormatter\TermsAndConditionsFormatter;
use Drupal\apigee_m10n\Plugin\Field\FieldType\TermsAndConditionsFieldItem;
use Drupal\Core\Field\FieldItemList;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Test the `apigee_tnc_default` field formatter.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class TermsAndConditionsFormatterKernelTest extends MonetizationKernelTestBase {

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The formatter manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatter_manager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $field_manager;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setUp();

    // If the user doesn't have the "view subscription" permission, they should
    // get access denied.
    $this->account = $this->createAccount(['view subscription']);

    $this->queueOrg();

    $this->drupalLogin($this->account);

    $this->formatter_manager = $this->container->get('plugin.manager.field.formatter');
    $this->field_manager = $this->container->get('entity_field.manager');
  }

  /**
   * Test viewing a terms and condition field.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Twig_Error_Loader
   * @throws \Twig_Error_Runtime
   * @throws \Twig_Error_Syntax
   */
  public function testView() {
    $package = $this->createPackage();
    $rate_plan = $this->createPackageRatePlan($package);
    $subscription = $this->createsubscription($this->account, $rate_plan);

    $this->stack
      ->queueMockResponse(['get_developer_subscriptions' => ['subscriptions' => [$subscription]]])
      ->queueMockResponse(['get_package_rate_plan' => ['plan' => $rate_plan]]);

    // Make sure user has access to the page.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseNotContains('Connection error');

    $item_list = $subscription->get('termsAndConditions');
    static::assertInstanceOf(FieldItemList::class, $item_list);
    static::assertInstanceOf(TermsAndConditionsFieldItem::class, $item_list->get(0));
    static::assertSame('Accepted', $item_list->get(0)->value);
    /** @var \Drupal\apigee_m10n\Plugin\Field\FieldFormatter\TermsAndConditionsFormatter $instance */
    $instance = $this->formatter_manager->createInstance('termsAndConditions', [
      'field_definition' => $this->field_manager->getBaseFieldDefinitions('subscription')['termsAndConditions'],
      'settings' => [],
      'label' => TRUE,
      'view_mode' => 'default',
      'third_party_settings' => [],
    ]);
    static::assertInstanceOf(TermsAndConditionsFormatter::class, $instance);

    // Render the field item.
    $build = $instance->view($item_list);

    static::assertSame('Terms and Conditions', (string) $build['#title']);
    static::assertTrue($build['#label_display']);

    $this->render($build);

  }

}
