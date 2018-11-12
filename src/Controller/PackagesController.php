<?php

/**
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

use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
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
  protected $sdkControllerFactory;

  /**
   * Constructs a new ExampleController object.
   */
  public function __construct(ApigeeSdkControllerFactoryInterface $sdk_controller_factory) {
    $this->sdkControllerFactory = $sdk_controller_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('apigee_m10n.sdk_controller_factory')
    );
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
   * Redirect to the users purchased page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the current user's purchased plan page.
   */
  public function myPurchased(): RedirectResponse {
    return $this->redirect(
      'apigee_monetization.purchased',
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
    $package_controller = $this->sdkControllerFactory->apiPackageController();
    // Get all packages.
    $all_packages = $package_controller->getEntities();
    // Load purchased packages for comparison.
    $purchased_packages = $package_controller->getAvailableApiPackagesByDeveloper($user->getEmail());
    // We don't want to show packages that have already been purchased split the
    // difference.
    $available_packages = array_diff_key($all_packages, $purchased_packages);

    return [
      'package_list' => [
        '#theme' => 'package_list',
        '#package_list' => $available_packages,
      ],
    ];
  }

  /**
   * Gets a list of purchased packages for this user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The drupal user/developer.
   *
   * @return array
   *   The pager render array.
   */
  public function purchasedPage(UserInterface $user = NULL) {
    return ['#markup' => $this->t('Hello World')];
  }

}
