<?php

/*
 * Copyright 2026 Google LLC
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

namespace Drupal\Tests\apigee_m10n\Unit;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Drupal\apigee_m10n\ApigeeSdkControllerFactory;
use Drupal\apigee_m10n\Entity\Form\CancelPurchaseConfirmForm;
use Drupal\apigee_m10n\Entity\PurchasedPlan;
use Drupal\apigee_m10n\Entity\RatePlan;
use Drupal\user\UserInterface;

/**
 * Tests for CancelPurchaseConfirmForm redirection and cancel URLs.
 *
 * @group apigee_m10n
 * @group apigee_m10n_unit
 *
 * @coversDefaultClass \Drupal\apigee_m10n\Entity\Form\CancelPurchaseConfirmForm
 */
class CancelPurchaseConfirmFormTest extends UnitTestCase {

  /**
   * Route match prophecy.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $routeMatch;

  /**
   * Messenger prophecy.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $messenger;

  /**
   * SDK controller factory prophecy.
   *
   * @var \Drupal\apigee_m10n\ApigeeSdkControllerFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $sdkControllerFactory;

  /**
   * Purchased plan entity prophecy.
   *
   * @var \Drupal\apigee_m10n\Entity\PurchasedPlan|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $purchasedPlan;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->routeMatch = $this->prophesize(RouteMatchInterface::class);
    $this->messenger = $this->prophesize(MessengerInterface::class);
    $this->sdkControllerFactory = $this->prophesize(ApigeeSdkControllerFactory::class);
    $this->purchasedPlan = $this->prophesize(PurchasedPlan::class);

    $this->routeMatch->getParameter('purchased_plan')->willReturn($this->purchasedPlan->reveal());

    $container = new ContainerBuilder();
    $cache_invalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);
    $container->set('cache_tags.invalidator', $cache_invalidator->reveal());

    $current_user = $this->prophesize(AccountInterface::class);
    $current_user->id()->willReturn(10);
    $container->set('current_user', $current_user->reveal());

    $string_translation = $this->getStringTranslationStub();
    $container->set('string_translation', $string_translation);

    \Drupal::setContainer($container);
  }

  /**
   * Tests getCancelUrl with team parameter as an object with id().
   *
   * @covers ::getCancelUrl
   */
  public function testGetCancelUrlWithTeamObject(): void {
    $team = new class {
      public function id() {
        return 'team-abc';
      }
    };
    $this->routeMatch->getParameter('team')->willReturn($team);
    $this->routeMatch->getParameter('user')->willReturn(NULL);

    $form = new CancelPurchaseConfirmForm(
      $this->routeMatch->reveal(),
      $this->messenger->reveal(),
      $this->sdkControllerFactory->reveal()
    );
    $form->setEntity($this->purchasedPlan->reveal());
    $form->setStringTranslation($this->getStringTranslationStub());

    $url = $form->getCancelUrl();
    $this->assertInstanceOf(Url::class, $url);
    $this->assertSame('entity.purchased_plan.team_collection', $url->getRouteName());
    $this->assertSame(['team' => 'team-abc'], $url->getRouteParameters());
  }

  /**
   * Tests getCancelUrl with team parameter as a string ID.
   *
   * @covers ::getCancelUrl
   */
  public function testGetCancelUrlWithTeamString(): void {
    $this->routeMatch->getParameter('team')->willReturn('team-xyz');
    $this->routeMatch->getParameter('user')->willReturn(NULL);

    $form = new CancelPurchaseConfirmForm(
      $this->routeMatch->reveal(),
      $this->messenger->reveal(),
      $this->sdkControllerFactory->reveal()
    );
    $form->setEntity($this->purchasedPlan->reveal());
    $form->setStringTranslation($this->getStringTranslationStub());

    $url = $form->getCancelUrl();
    $this->assertInstanceOf(Url::class, $url);
    $this->assertSame('entity.purchased_plan.team_collection', $url->getRouteName());
    $this->assertSame(['team' => 'team-xyz'], $url->getRouteParameters());
  }

  /**
   * Tests getCancelUrl with user parameter as UserInterface object.
   *
   * @covers ::getCancelUrl
   */
  public function testGetCancelUrlWithUserObject(): void {
    $user = $this->prophesize(UserInterface::class);
    $user->id()->willReturn(42);

    $this->routeMatch->getParameter('team')->willReturn(NULL);
    $this->routeMatch->getParameter('user')->willReturn($user->reveal());

    $form = new CancelPurchaseConfirmForm(
      $this->routeMatch->reveal(),
      $this->messenger->reveal(),
      $this->sdkControllerFactory->reveal()
    );
    $form->setEntity($this->purchasedPlan->reveal());
    $form->setStringTranslation($this->getStringTranslationStub());

    $url = $form->getCancelUrl();
    $this->assertInstanceOf(Url::class, $url);
    $this->assertSame('entity.purchased_plan.developer_collection', $url->getRouteName());
    $this->assertSame(['user' => 42], $url->getRouteParameters());
  }

  /**
   * Tests getCancelUrl with user parameter as a scalar ID.
   *
   * @covers ::getCancelUrl
   */
  public function testGetCancelUrlWithUserId(): void {
    $this->routeMatch->getParameter('team')->willReturn(NULL);
    $this->routeMatch->getParameter('user')->willReturn(55);

    $form = new CancelPurchaseConfirmForm(
      $this->routeMatch->reveal(),
      $this->messenger->reveal(),
      $this->sdkControllerFactory->reveal()
    );
    $form->setEntity($this->purchasedPlan->reveal());
    $form->setStringTranslation($this->getStringTranslationStub());

    $url = $form->getCancelUrl();
    $this->assertInstanceOf(Url::class, $url);
    $this->assertSame('entity.purchased_plan.developer_collection', $url->getRouteName());
    $this->assertSame(['user' => 55], $url->getRouteParameters());
  }

  /**
   * Tests getCancelUrl fallback to entity owner ID when route params are empty.
   *
   * @covers ::getCancelUrl
   */
  public function testGetCancelUrlFallbackToOwnerId(): void {
    $this->routeMatch->getParameter('team')->willReturn(NULL);
    $this->routeMatch->getParameter('user')->willReturn(NULL);
    $this->purchasedPlan->getOwnerId()->willReturn(99);

    $form = new CancelPurchaseConfirmForm(
      $this->routeMatch->reveal(),
      $this->messenger->reveal(),
      $this->sdkControllerFactory->reveal()
    );
    $form->setEntity($this->purchasedPlan->reveal());
    $form->setStringTranslation($this->getStringTranslationStub());

    $url = $form->getCancelUrl();
    $this->assertInstanceOf(Url::class, $url);
    $this->assertSame('entity.purchased_plan.developer_collection', $url->getRouteName());
    $this->assertSame(['user' => 99], $url->getRouteParameters());
  }

  /**
   * Tests submitForm redirect for team.
   *
   * @covers ::submitForm
   */
  public function testSubmitFormRedirectsToTeamCollection(): void {
    $rate_plan = $this->prophesize(RatePlan::class);
    $rate_plan->getDisplayName()->willReturn('Test Plan');

    $this->purchasedPlan->save()->willReturn(1);
    $this->purchasedPlan->getRatePlan()->willReturn($rate_plan->reveal());

    $this->routeMatch->getParameter('team')->willReturn('team-123');
    $this->routeMatch->getParameter('user')->willReturn(NULL);

    $form = new CancelPurchaseConfirmForm(
      $this->routeMatch->reveal(),
      $this->messenger->reveal(),
      $this->sdkControllerFactory->reveal()
    );
    $form->setEntity($this->purchasedPlan->reveal());
    $form->setStringTranslation($this->getStringTranslationStub());

    $form_array = [];
    $form_state = new FormState();
    $form->submitForm($form_array, $form_state);

    $redirect = $form_state->getRedirect();
    $this->assertInstanceOf(Url::class, $redirect);
    $this->assertSame('entity.purchased_plan.team_collection', $redirect->getRouteName());
    $this->assertSame(['team' => 'team-123'], $redirect->getRouteParameters());
  }

  /**
   * Tests submitForm redirect for user object.
   *
   * @covers ::submitForm
   */
  public function testSubmitFormRedirectsToUserCollection(): void {
    $rate_plan = $this->prophesize(RatePlan::class);
    $rate_plan->getDisplayName()->willReturn('Test Plan');

    $this->purchasedPlan->save()->willReturn(1);
    $this->purchasedPlan->getRatePlan()->willReturn($rate_plan->reveal());

    $user = $this->prophesize(UserInterface::class);
    $user->id()->willReturn(42);

    $this->routeMatch->getParameter('team')->willReturn(NULL);
    $this->routeMatch->getParameter('user')->willReturn($user->reveal());

    $form = new CancelPurchaseConfirmForm(
      $this->routeMatch->reveal(),
      $this->messenger->reveal(),
      $this->sdkControllerFactory->reveal()
    );
    $form->setEntity($this->purchasedPlan->reveal());
    $form->setStringTranslation($this->getStringTranslationStub());

    $form_array = [];
    $form_state = new FormState();
    $form->submitForm($form_array, $form_state);

    $redirect = $form_state->getRedirect();
    $this->assertInstanceOf(Url::class, $redirect);
    $this->assertSame('entity.purchased_plan.developer_collection', $redirect->getRouteName());
    $this->assertSame(['user' => 42], $redirect->getRouteParameters());
  }

}
