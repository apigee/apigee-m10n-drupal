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

use Drupal\apigee_m10n\Form\RatePlanConfigForm;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Form\FormState;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Tests for `apigee_m10n.rate_plan.config` settings.
 */
class RatePlanConfigFormTest extends MonetizationKernelTestBase {

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
    $form = $builder->getForm(RatePlanConfigForm::class);
    static::assertSame('rate_plan_settings', $form['#form_id']);

    // Make sure the defualt view mode is available.
    $display_options = $form["catalog_view_mode"]["#options"];
    static::assertCount(2, $display_options);
    static::assertSame('Default', (string) $display_options['default']);
    static::assertSame('Teaser', (string) $display_options['teaser']);

    // Create a new entity view mode.
    EntityViewMode::create([
      'id' => 'rate_plan.catalog',
      'targetEntityType' => 'rate_plan',
      'label' => 'Catalog',
    ])->save();

    // Makes sure the view mode is not yet available.
    $form = $builder->getForm(RatePlanConfigForm::class);
    $display_options = $form["catalog_view_mode"]["#options"];
    static::assertCount(2, $display_options);

    $view_mode = EntityViewDisplay::create([
      'targetEntityType' => 'rate_plan',
      'bundle' => 'rate_plan',
      'mode' => 'catalog',
      'status' => FALSE,
    ]);
    $view_mode->save();

    // Makes sure the view mode is not yet available.
    $form = $builder->getForm(RatePlanConfigForm::class);
    $display_options = $form["catalog_view_mode"]["#options"];
    static::assertCount(2, $display_options);

    // Enable the view mode.
    $view_mode->setStatus(TRUE)->save();

    // Now the "catalog" view mode should be available in the config form.
    $form = $builder->getForm(RatePlanConfigForm::class);
    $display_options = $form["catalog_view_mode"]["#options"];
    static::assertCount(3, $display_options);
    static::assertSame('Default', (string) $display_options['default']);
    static::assertSame('Teaser', (string) $display_options['teaser']);
    static::assertSame('Catalog', (string) $display_options['catalog']);

    // Prepare to submit the form.
    $form_state = new FormState();
    // Change the view mode.
    $form_state->setValue('catalog_view_mode', 'catalog');
    // Submit the form.
    $builder->submitForm(RatePlanConfigForm::class, $form_state);

    // Grab the config to make sure the `catalog_view_mode` has changed.
    $config = $this->config(RatePlanConfigForm::CONFIG_NAME);
    static::assertSame('catalog', $config->get('catalog_view_mode'));
  }

}
