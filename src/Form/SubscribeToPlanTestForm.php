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

namespace Drupal\apigee_m10n\Form;

use Drupal\apigee_m10n\Controller\PackagesController;
use Drupal\apigee_m10n\Entity\RatePlan;
use Drupal\apigee_m10n\Entity\Subscription;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

class SubscribeToPlanTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscribe_to_plan_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $packages_and_plans = \Drupal::cache()->get('packages_and_plans');

    if (!$packages_and_plans) {
      $controller = PackagesController::create(\Drupal::getContainer());
      $packages_and_plans = $controller->catalogPage(User::load(3));
      \Drupal::cache()->set('packages_and_plans', $packages_and_plans);
    }
    else {
      $packages_and_plans = $packages_and_plans->data;
    }

    $packages_and_plans = array_filter($packages_and_plans['package_list']['#plan_list']);

    $packages_and_plans = array_map(function($package) {
      unset($package['#sorted']);
      unset($package['#pre_render']);
      $package = array_map(function($package) {
        return $package['#rate_plan'];
      }, $package);
      return $package;
    }, $packages_and_plans);

    foreach ($packages_and_plans as &$package) {
      foreach ($package as $key => $plan) {
        $package[$plan->getPackage()->id() . "::" . $key] = $plan->id();
        unset($package[$key]);
      }
    }

    $form['plan_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Plans.'),
      '#options' => $packages_and_plans,
    ];

    $form['action'] = [
      '#type' => 'radios',
      '#options' => [
        'subscribe' => 'Subscribe',
        'unsubscribe' => 'Unsubscribe'
      ]
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit'
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    list($package_id, $plan_id) = explode('::', $values['plan_id']);

    $developer_email = 'chrisnovak@google.com';

    $subscription = new \stdClass();

    if ($values['action'] === 'subscribe') {

      $rate_plan = RatePlan::loadById($package_id, $plan_id);

      // @TODO create a `createWithDeveloperId` method and override create in storage, throwing an exception.
      $subscription = Subscription::create([
        'startDate' => new \DateTimeImmutable(),
        'ratePlan' => $rate_plan
      ]);

    }
    else if ($values['action'] === 'unsubscribe') {

      // @TODO ask Dezso about adding a loadAcceptedRatePlanById method to controller.
      $subscriptions = \Drupal::entityTypeManager('subscription')
        ->getStorage('subscription')
        ->loadByDeveloperId($developer_email);

      $subscription = array_filter($subscriptions, function($subscription) use ($plan_id) {
        $id = $subscription->getRatePlan()->id();
        if ($id === $plan_id){
          return $subscription;
        }
      });

      $subscription = array_pop($subscription);

      $timezone = \Drupal::service('apigee_edge.sdk_connector');

      // @TODO ensure that this end date is relative to the organization's timezone.
      $subscription->setEndDate(new \DateTimeImmutable('-1 day'));

    }

    $subscription->developerId = $developer_email;

    try {
      $subscription->save();
    }
    catch (\Exception $e) {
      // If rate plan has already been subscribed to or doesn't exist give the user a message.
    }

  }

}
