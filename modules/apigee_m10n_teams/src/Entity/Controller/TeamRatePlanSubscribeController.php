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

namespace Drupal\apigee_m10n_teams\Entity\Controller;

use Apigee\Edge\Api\Monetization\Entity\Company;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\Entity\Controller\RatePlanSubscribeController;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\apigee_m10n\Entity\Subscription;
use Drupal\apigee_m10n\Form\SubscriptionConfigForm;

/**
 * Controller for subscribing to rate plans.
 */
class TeamRatePlanSubscribeController extends RatePlanSubscribeController {

  /**
   * Page callback to create a new team subscription.
   *
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The team to subscribe.
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan
   *   The rate plan.
   *
   * @return array
   *   A subscribe form render array.
   *
   * @throws \Exception
   */
  public function teamSubscribeForm(TeamInterface $team, RatePlanInterface $rate_plan) {
    // Create a subscription to pass to the subscription edit form.
    $subscription = Subscription::create([
      'ratePlan' => $rate_plan,
      'company' => new Company(['id' => $team->id()]),
      'startDate' => new \DateTimeImmutable(),
    ]);

    // Get the save label from settings.
    $save_label = $this->config(SubscriptionConfigForm::CONFIG_NAME)->get('subscribe_button_label');
    $save_label = $save_label ?? 'Subscribe';

    // Return the subscribe form with the label set.
    return $this->entityFormBuilder->getForm($subscription, 'default', [
      'save_label' => $this->t($save_label, [
        '@rate_plan' => $rate_plan->getDisplayName(),
        '@team' => $team->label(),
        '@username' => '',
      ]),
    ]);
  }

  /**
   * Gets the title for the subscribe page.
   *
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The team to subscribe.
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan
   *   The rate plan.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title.
   */
  public function teamTitle(TeamInterface $team, RatePlanInterface $rate_plan) {
    $title_template = $this->config(SubscriptionConfigForm::CONFIG_NAME)->get('subscribe_form_title');
    $title_template = $title_template ?? 'Subscribe to @rate_plan';
    // TODO: Add informaiton about the availability of `@teamname`.
    return $this->t($title_template, [
      '@rate_plan' => $rate_plan->getDisplayName(),
      '%rate_plan' => $rate_plan->getDisplayName(),
      '@teamname' => $team->label(),
      '%teamname' => $team->label(),
      '@username' => '',
      '%username' => '',
    ]);
  }

}
