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

namespace Drupal\Tests\apigee_m10n\Kernel\Entity\Render;

use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
use Drupal\Tests\apigee_m10n\Traits\RatePlanDetailsKernelTestAssertionTrait;

/**
 * Performs functional tests on drupal_render().
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class RatePlanRenderTest extends MonetizationKernelTestBase {

  use RatePlanDetailsKernelTestAssertionTrait;

  /**
   * Test package rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $package_rate_plan;

  /**
   * The developer drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setUp();

    // Setup for creating a user.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);
    $this->installEntitySchema('user');
    $this->developer = $this->createAccount(['view rate_plan']);
    $this->setCurrentUser($this->developer);

    $this->package_rate_plan = $this->createPackageRatePlan($this->createPackage());

    // Enable the Classy theme.
    \Drupal::service('theme_handler')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();
  }

  /**
   * Tests theme preprocess functions being able to attach assets.
   */
  public function testRenderRatePlan() {
    $this->warmTnsCache();
    $this->warmDeveloperTnsCache($this->developer);

    $price_formatter = \Drupal::service('apigee_m10n.price_formatter');

    $rate_plan = $this->package_rate_plan;

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($rate_plan->getEntityTypeId());

    $this->warmPurchasedPlanCache($this->developer);

    $build = $view_builder->view($rate_plan, 'default');

    $this->setRawContent((string) \Drupal::service('renderer')->renderRoot($build));

    // Check the entity label.
    $this->assertCssElementText('.rate-plan > h2 > a', $rate_plan->label());
    $this->assertLink($rate_plan->label());
    $this->assertLinkByHref($rate_plan->toUrl()->toString(), 0, 'The display name links to the rate plan.');

    // Test product names.
    foreach ($rate_plan->get('packageProducts') as $index => $product_field) {
      $css_index = $index + 1;
      $this->assertCssElementText(".rate-plan .field--name-packageproducts .field__items .field__item:nth-child({$css_index})", $product_field->entity->label());
    }

    // Test fees.
    $this->assertCssElementText(".rate-plan .field--name-setupfee .field__label", 'Set Up Fee');
    $this->assertCssElementText(".rate-plan .field--name-setupfee .field__item", $price_formatter->format($rate_plan->get('setUpFee')->first()->get('amount')->getValue(), $rate_plan->get('setUpFee')->first()->get('currency_code')->getValue()));
    $this->assertCssElementText(".rate-plan .field--name-recurringfee .field__label", 'Recurring Fee');
    $this->assertCssElementText(".rate-plan .field--name-recurringfee .field__item", $price_formatter->format($rate_plan->get('recurringFee')->first()->get('amount')->getValue(), $rate_plan->get('recurringFee')->first()->get('currency_code')->getValue()));
    $this->assertCssElementText(".rate-plan .field--name-earlyterminationfee .field__label", 'Early Termination Fee');
    $this->assertCssElementText(".rate-plan .field--name-earlyterminationfee .field__item", $price_formatter->format($rate_plan->get('earlyTerminationFee')->first()->get('amount')->getValue(), $rate_plan->get('earlyTerminationFee')->first()->get('currency_code')->getValue()));

    // Plan details.
    $details = $rate_plan->getRatePlanDetails()[0];
    $this->assertRatePlanDetails($details);

    // TODO: test the purchase form.
  }

}
