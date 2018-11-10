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

namespace Drupal\apigee_m10n\Controller;

use Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;
use Drupal\apigee_m10n\Entity\RatePlan;
use Drupal\apigee_m10n\Entity\Subscription;
use Drupal\apigee_m10n\Form\RatePlanConfigForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Generates the packages page.
 *
 * @package Drupal\apigee_m10n\Controller
 */
class PackagesController extends ControllerBase {

  /**
   * Service for instantiating SDK controllers.
   *
   * @var \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface
   */
  protected $controller_factory;

  /**
   * PackagesController constructor.
   *
   * @param \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface $sdk_controller_factory
   *   The SDK controller factory.
   */
  public function __construct(ApigeeSdkControllerFactoryInterface $sdk_controller_factory) {
    $this->controller_factory = $sdk_controller_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('apigee_m10n.sdk_controller_factory'));
  }

  /**
   * Redirect to the users catalog page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the current user's packages page.
   */
  public function myCatalog(): RedirectResponse {
    return $this->redirect(
      'apigee_monetization.packages',
      ['user' => \Drupal::currentUser()->id()],
      ['absolute' => TRUE]
    );
  }

  /**
   * Redirect to the users subscriptions page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the current user's subscriptions page.
   */
  public function mySubscriptions(): RedirectResponse {
    return $this->redirect(
      'entity.subscription.collection_by_developer',
      ['user' => \Drupal::currentUser()->id()],
      ['absolute' => TRUE]
    );
  }

  /**
   * Gets a list of available packages for this user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The drupal user/developer.
   *
   * @return array
   *   The pager render array.
   */
  public function catalogPage(UserInterface $user = NULL) {
    // Get the package controller.
    $package_controller = $this->controller_factory->apiPackageController();
    // Load purchased packages for comparison.
    $packages = $package_controller->getAvailableApiPackagesByDeveloper($user->getEmail(), TRUE, TRUE);

    // Get the view mode to use for rate plans.
    $view_mode = $this->config(RatePlanConfigForm::CONFIG_NAME)->get('product_rate_plan_view_mode');
    // Get an entity view builder for rate plans.
    $rate_plan_view_builder = $this->entityTypeManager()->getViewBuilder('rate_plan', $view_mode);

    // Load plans for each package.
    $plans = array_map(function ($package) use ($rate_plan_view_builder) {
      // Load the rate plans.
      $package_rate_plans = RatePlan::loadPackageRatePlans($package->id());
      if (!empty($package_rate_plans)) {
        // Return a render-able list of rate plans.
        return $rate_plan_view_builder->viewMultiple($package_rate_plans);
      }
    }, $packages);

    return [
      'package_list' => [
        '#theme' => 'package_list',
        '#package_list' => $packages,
        '#plan_list' => $plans,
      ],
    ];
  }

  /**
   * Get a rate plan controller.
   *
   * @param string $package_id
   *   The package ID.
   *
   * @return \Apigee\Edge\Api\Monetization\Controller\RatePlanControllerInterface
   *   The rate plan controller.
   */
  protected function packageRatePlanController($package_id): RatePlanControllerInterface {
    // Use static caching.
    static $controllers;

    // Controlelrs should be cached per package id.
    if (!isset($controllers[$package_id])) {
      $controllers[$package_id] = $this->controller_factory->packageRatePlanController($package_id);
    }

    return $controllers[$package_id];
  }

}
