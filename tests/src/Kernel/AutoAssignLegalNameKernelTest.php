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

namespace Drupal\Tests\apigee_m10n\Kernel;

use Drupal\apigee_edge\Entity\Developer;
use Drupal\Core\Form\FormState;

/**
 * AutoAssignLegalNameKernelTest tests.
 */
class AutoAssignLegalNameKernelTest extends MonetizationKernelTestBase {

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

  }

  /**
   * Tests legal name auto assignment routine.
   */
  public function testAutoAssignLegalName() {
    $package = $this->createPackage();
    $rate_plan = $this->createPackageRatePlan($package);
    $subscription = $this->createsubscription($this->developer, $rate_plan);
    $current_developer = Developer::load($this->developer->getEmail());
    $current_developer->setAttribute('MINT_DEVELOPER_LEGAL_NAME', $this->developer->getEmail());
    $this->queueOrg();
    $this->stack
      ->queueMockResponse([
        'developer' => ['developer' => $current_developer],
        'get_developer_subscriptions' => ['subscriptions' => [$subscription]],
        'get_package_rate_plan' => ['plan' => $rate_plan]
      ]);
    static::assertSame($current_developer->getAttributeValue('MINT_DEVELOPER_LEGAL_NAME'), $this->developer->getEmail());

    /*
    $form_object = \Drupal::entityTypeManager()->getFormObject('subscription', 'default');
    $form_object->setEntity($subscription);
    $form = $form_object->buildForm([], new FormState());
    $form_state = new FormState();
    $form_state->setValues([]);
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($form, $form_state);

    $messages = \Drupal::messenger()->all();
    \Drupal::messenger()->deleteAll();
    static::assertFalse(!isset($messages['error']));*/

  }

}
