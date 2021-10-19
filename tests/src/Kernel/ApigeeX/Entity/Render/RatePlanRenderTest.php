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

namespace Drupal\Tests\apigee_m10n\Kernel\ApigeeX\Entity\Render;

use Drupal\Tests\apigee_m10n\Kernel\ApigeeX\MonetizationKernelTestBase;
use Drupal\Tests\apigee_m10n\Traits\ApigeeX\RatePlanDetailsKernelTestAssertionTrait;

/**
 * Performs functional tests on drupal_render().
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class RatePlanRenderTest extends MonetizationKernelTestBase {

  use RatePlanDetailsKernelTestAssertionTrait;

  /**
   * Test rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\XRatePlanInterface
   */
  protected $rate_plan;

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

    // Get pre-configured token storage service for testing.
    $this->storeToken();

    $this->developer = $this->createAccount(
      [
        'view rate_plan',
        'view xrate_plan'
      ]);
    $this->setCurrentUser($this->developer);

    $this->stack->reset();
    $xproduct = $this->createApigeexProduct();
    $this->stack->reset();
    $this->rate_plan = $this->createRatePlan($xproduct);

    // Enable the Classy theme.
    \Drupal::service('theme_installer')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();
  }

  /**
   * Tests theme preprocess functions being able to attach assets.
   */
  public function testRenderRatePlan() {
    $price_formatter = \Drupal::service('apigee_m10n.price_formatter');

    $rate_plan = $this->rate_plan;

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($rate_plan->getEntityTypeId());
    $build = $view_builder->view($rate_plan, 'default');
    $this->setRawContent((string) \Drupal::service('renderer')->renderRoot($build));

    $this->assertLinkByHref($rate_plan->toUrl()->toString(), 0, 'The display name links to the rate plan.');

    $setUpFee = $rate_plan->getSetupFeesPriceValue();
    $recurringFee = $rate_plan->getRecurringFeesPriceValue();
    $consumptionFee = $rate_plan->getConsumptionFeePriceValue();
    $feeFrequency = $rate_plan->getfeeFrequencyFormat();

    // Rate Plan.
    $this->assertCssElementText(".xrate-plan .field--name-displayname .field__label", 'RatePlan');
    $this->assertCssElementText(".xrate-plan .field--name-displayname .field__item", $rate_plan->getDisplayName());

    // Test fees.
    $this->assertCssElementText(".xrate-plan .field--name-setupfees .field__label", 'Setup Fees');
    $this->assertCssElementText(".xrate-plan .field--name-setupfees .field__item", $price_formatter->format($setUpFee[0]['amount'], $setUpFee[0]['currency_code']));
    $this->assertCssElementText(".xrate-plan .field--name-feefrequency .field__label", 'Recurring Fee Frequency');
    $this->assertCssElementText(".xrate-plan .field--name-feefrequency .field__item", $feeFrequency);
    $this->assertCssElementText(".xrate-plan .field--name-consumptionfee .field__label", 'Consumption Fee');
    $this->assertCssElementText(".xrate-plan .field--name-consumptionfee .field__item", $price_formatter->format($consumptionFee[0]['amount'], $consumptionFee[0]['currency_code']));

    // Pricing Type.
    $this->assertCssElementText(".xrate-plan .field--name-consumptionpricingtype .field__label", 'Consumption Pricing Type');
    $this->assertCssElementText(".xrate-plan .field--name-consumptionpricingtype .field__item", $rate_plan->getConsumptionPricingType());

    // Payment model.
    $this->assertCssElementText(".xrate-plan .field--name-paymentfundingmodel .field__label", 'Payment Funding Model');
    $this->assertCssElementText(".xrate-plan .field--name-paymentfundingmodel .field__item", $rate_plan->getPaymentFundingModel());
  }

}
