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

namespace Drupal\Tests\apigee_m10n_teams\Kernel\Entity;

use Drupal\apigee_m10n\Entity\RatePlan;
use Drupal\apigee_m10n_teams\Entity\TeamsRatePlan;
use Drupal\Tests\apigee_m10n\Traits\RatePlanDetailsKernelTestAssertionTrait;
use Drupal\Tests\apigee_m10n_teams\Kernel\MonetizationTeamsKernelTestBase;
use Drupal\Tests\apigee_m10n_teams\Traits\TeamProphecyTrait;

/**
 * Tests the team rate plan entity.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class RatePlanEntityKernelTest extends MonetizationTeamsKernelTestBase {

  use TeamProphecyTrait;
  use RatePlanDetailsKernelTestAssertionTrait;

  /**
   * Drupal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * An apigee team.
   *
   * @var \Drupal\apigee_edge_teams\Entity\TeamInterface
   */
  protected $team;

  /**
   * A test product bundle.
   *
   * @var \Drupal\apigee_m10n\Entity\ProductBundleInterface
   */
  protected $product_bundle;

  /**
   * A test rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $rate_plan;

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
      'system',
    ]);

    // Enable the Classy theme.
    \Drupal::service('theme_handler')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();

    // Makes sure the new user isn't root.
    $this->createAccount();
    $this->developer = $this->createAccount();
    $this->team = $this->createTeam();
    $this->addUserToTeam($this->team, $this->developer);

    $this->createCurrentUserSession($this->developer);

    $this->product_bundle = $this->createProductBundle();
    $this->rate_plan = $this->createRatePlan($this->product_bundle);
  }

  /**
   * Test team rate plan entity overrides.
   *
   * @throws \Exception
   */
  public function testRatePlanEntity() {
    $this->setCurrentTeamRoute($this->team);
    $this->warmTnsCache();
    $this->warmTeamTnsCache($this->team);

    $price_formatter = \Drupal::service('apigee_m10n.price_formatter');

    // Check team access.
    static::assertTrue($this->rate_plan->access('view', $this->developer));

    // Make sure we get a team context when getting a package url.
    $url = $this->rate_plan->toUrl('team');
    static::assertSame("/teams/{$this->team->id()}/monetization/product-bundle/{$this->product_bundle->id()}/plan/{$this->rate_plan->id()}", $url->toString());
    static::assertSame('entity.rate_plan.team', $url->getRouteName());

    // Load the cached rate plan.
    $rate_plan = RatePlan::loadById($this->product_bundle->id(), $this->rate_plan->id());

    static::assertInstanceOf(TeamsRatePlan::class, $rate_plan);
    // Compare the loaded rate plan with the object comparator.
    static::assertEquals($rate_plan->decorated(), $this->rate_plan->decorated());

    // Render the package.
    $build = \Drupal::entityTypeManager()
      ->getViewBuilder('rate_plan')
      ->view($rate_plan, 'default');

    // Rate plans as rendered.
    $this->setRawContent((string) \Drupal::service('renderer')->renderRoot($build));

    // Check the entity label.
    $this->assertCssElementText('.rate-plan > h2 > a', $rate_plan->label());
    $this->assertLink($rate_plan->label());
    $this->assertLinkByHref($rate_plan->toUrl()->toString());

    // Test product names.
    foreach ($rate_plan->get('products') as $index => $product_field) {
      $css_index = $index + 1;
      $this->assertCssElementText(".rate-plan .field--name-products .field__items .field__item:nth-child({$css_index})", $product_field->entity->label());
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

    // Test rate plan rate card.
    // TODO: Test the rate plan render form.
  }

}
