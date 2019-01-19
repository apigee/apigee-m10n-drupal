<?php

/*
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Drupal\Tests\apigee_m10n\Kernel\Form;

use Drupal\apigee_m10n\Form\PackageConfigForm;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Form\FormState;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Tests for `apigee_m10n.package.config` settings.
 */
class PackageConfigFormTest extends MonetizationKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installConfig('apigee_m10n');
  }

  /**
   * Test the form.
   */
  public function testForm() {
    /** @var \Drupal\Core\Form\FormBuilderInterface $builder */$builder = \Drupal::service('form_builder');
    $form = $builder->getForm(PackageConfigForm::class);
    static::assertSame('package_settings', $form['#form_id']);

    // Make sure the defualt view mode is available.
    $display_options = $form["catalog_view_mode"]["#options"];
    static::assertCount(1, $display_options);
    static::assertSame('Default', (string) $display_options['default']);

    // Create a new entity view mode.
    EntityViewMode::create([
      'id' => 'package.catalog',
      'targetEntityType' => 'package',
      'label' => 'Catalog',
    ])->save();

    // Makes sure the view mode is not yet available.
    $form = $builder->getForm(PackageConfigForm::class);
    $display_options = $form["catalog_view_mode"]["#options"];
    static::assertCount(1, $display_options);

    $view_mode = EntityViewDisplay::create([
      'targetEntityType' => 'package',
      'bundle' => 'package',
      'mode' => 'catalog',
      'status' => FALSE,
    ]);
    $view_mode->save();

    // Makes sure the view mode is not yet available.
    $form = $builder->getForm(PackageConfigForm::class);
    $display_options = $form["catalog_view_mode"]["#options"];
    static::assertCount(1, $display_options);

    // Enable the view mode.
    $view_mode->setStatus(TRUE)->save();

    // Now the "catalog" view mode should be available in the config form.
    $form = $builder->getForm(PackageConfigForm::class);
    $display_options = $form["catalog_view_mode"]["#options"];
    static::assertCount(2, $display_options);
    static::assertSame('Default', (string) $display_options['default']);
    static::assertSame('Catalog', (string) $display_options['catalog']);

    // Prepare to submit the form.
    $form_state = new FormState();
    // Change the view mode.
    $form_state->setValue('catalog_view_mode', 'catalog');
    // Submit the form.
    $builder->submitForm(PackageConfigForm::class, $form_state);

    // Grab the config to make sure the `catalog_view_mode` has changed.
    $config = $this->config(PackageConfigForm::CONFIG_NAME);
    static::assertSame('catalog', $config->get('catalog_view_mode'));
  }

}
