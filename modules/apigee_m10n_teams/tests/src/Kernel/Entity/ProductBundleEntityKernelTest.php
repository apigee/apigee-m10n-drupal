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

use Drupal\apigee_m10n\Entity\ProductBundle;
use Drupal\apigee_m10n_teams\Entity\TeamProductBundleInterface;
use Drupal\Tests\apigee_m10n\Traits\RatePlansPropertyEnablerTrait;
use Drupal\Tests\apigee_m10n_teams\Kernel\MonetizationTeamsKernelTestBase;
use Drupal\Tests\apigee_m10n_teams\Traits\TeamProphecyTrait;

/**
 * Tests the team product bundle entity.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 * @group apigee_m10n_teams
 * @group apigee_m10n_teams_kernel
 */
class ProductBundleEntityKernelTest extends MonetizationTeamsKernelTestBase {

  use TeamProphecyTrait;
  use RatePlansPropertyEnablerTrait;

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
      'apigee_m10n',
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
  }

  /**
   * Test team product bundle entity rendering.
   *
   * @throws \Exception
   */
  public function testProductBundleEntity() {
    $this->setCurrentTeamRoute($this->team);
    $this->warmTnsCache();
    $this->warmTeamTnsCache($this->team);

    // Check team access.
    static::assertTrue($this->product_bundle->access('view', $this->developer));

    // Make sure we get a team context when getting a product bundle url.
    $url = $this->product_bundle->toUrl('team');
    static::assertSame("/teams/{$this->team->id()}/monetization/product-bundle/{$this->product_bundle->id()}", $url->toString());
    static::assertSame('entity.product_bundle.team', $url->getRouteName());

    // Load the cached product bundle.
    $product_bundle = ProductBundle::load($this->product_bundle->id());

    static::assertInstanceOf(TeamProductBundleInterface::class, $product_bundle);
    // Use the object comparator to compare the loaded product bundle.
    static::assertEquals($this->product_bundle, $product_bundle);

    // Get the product bundle products.
    static::assertGreaterThan(0, $this->count($product_bundle->getApiProducts()));

    // Render the product bundle.
    $build = \Drupal::entityTypeManager()
      ->getViewBuilder('product_bundle')
      ->view($this->product_bundle, 'default');

    $rate_plan_1 = $this->createRatePlan($this->product_bundle);
    $rate_plan_2 = $this->createRatePlan($this->product_bundle);

    $this->stack->queueMockResponse(['get_monetization_package_plans' => ['plans' => [$rate_plan_1, $rate_plan_2]]]);
    $content = \Drupal::service('renderer')->renderRoot($build);

    $this->setRawContent((string) $content);

    $css_prefix = '.apigee-entity.product-bundle';
    // Product bundle detail as rendered.
    $this->assertCssElementText("{$css_prefix} > h2", $product_bundle->label());

    // API Products.
    $this->assertCssElementText("{$css_prefix} .field--name-apiproducts .field__label", 'Included products');
    foreach ($this->product_bundle->get('apiProducts') as $index => $apiProduct) {
      // CSS indexes start at 1.
      $css_index = $index + 1;
      $this->assertCssElementText("{$css_prefix} .field--name-apiproducts .field__item:nth-child({$css_index})", $apiProduct->entity->label());
    }

    // Rate plans as rendered.
    foreach ([$rate_plan_1, $rate_plan_2] as $index => $rate_plan) {
      /** @var \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan */
      $this->assertLink($rate_plan->label());
      $this->assertLinkByHref($rate_plan->toUrl()->toString());
    }
  }

}
