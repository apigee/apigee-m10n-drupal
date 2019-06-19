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

namespace Drupal\apigee_m10n\Entity\Controller;

use Apigee\Edge\Api\Monetization\Entity\Developer;
use Drupal\apigee_m10n\Entity\RatePlanInterface;
use Drupal\apigee_m10n\Entity\PurchasedPlan;
use Drupal\apigee_m10n\Form\PurchasedPlanConfigForm;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for subscribing to rate plans.
 */
class PurchaseRatePlanController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * BillingController constructor.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entityFormBuilder
   *   Entity form builder service.
   */
  public function __construct(EntityFormBuilderInterface $entityFormBuilder) {
    $this->entityFormBuilder = $entityFormBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder')
    );
  }

  /**
   * Page callback to create a new purchased_plan.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user that will be purchasing the plan.
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan
   *   The rate plan.
   *
   * @return array
   *   A purchase form render array.
   *
   * @throws \Exception
   */
  public function purchaseForm(UserInterface $user, RatePlanInterface $rate_plan) {
    // Create a purchased_plan to pass to the purchased_plan edit form.
    $purchased_plan = PurchasedPlan::create([
      'ratePlan' => $rate_plan,
      'developer' => new Developer(['email' => $user->getEmail()]),
      'startDate' => new \DateTimeImmutable(),
    ]);

    // Get the save label from settings.
    $save_label = $this->config(PurchasedPlanConfigForm::CONFIG_NAME)->get('purchase_button_label');
    $save_label = $save_label ?? 'Purchase';

    // Return the purchase form with the label set.
    return $this->entityFormBuilder->getForm($purchased_plan, 'default', [
      'save_label' => $this->t($save_label, [
        '@rate_plan' => $rate_plan->getDisplayName(),
        '@username' => $user->label(),
      ]),
    ]);
  }

  /**
   * Gets the title for the purchase page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\user\UserInterface|null $user
   *   The user.
   * @param \Drupal\apigee_m10n\Entity\RatePlanInterface|null $rate_plan
   *   The rate plan.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title.
   */
  public function title(RouteMatchInterface $route_match, UserInterface $user = NULL, RatePlanInterface $rate_plan = NULL) {
    $title_template = $this->config(PurchasedPlanConfigForm::CONFIG_NAME)->get('purchase_form_title');
    $title_template = $title_template ?? 'Purchase @rate_plan';
    return $this->t($title_template, [
      '@rate_plan' => $rate_plan->getDisplayName(),
      '%rate_plan' => $rate_plan->getDisplayName(),
      '@username' => $user->label(),
      '%username' => $user->label(),
    ]);
  }

}
