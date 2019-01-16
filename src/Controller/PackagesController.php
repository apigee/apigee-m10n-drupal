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
use Drupal\apigee_m10n\Entity\Package;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Element;
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
   * @param \Drupal\user\UserInterface $user
   *   The drupal user/developer.
   *
   * @return array
   *   The pager render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function catalogPage(UserInterface $user) {
    // Load purchased packages for comparison.
    $packages = Package::getAvailableApiPackagesByDeveloper($user->getEmail());

    $build = ['package_list' => $this->entityTypeManager()->getViewBuilder('package')->viewMultiple($packages)];
    $build['package_list']['#pre_render'][] = [$this, 'preRender'];

    return $build;
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
    return $this->controller_factory->ratePlanController($package_id);
  }

  /**
   * Call back to pre-process the entity list.
   *
   * @param array $build
   *   A render array as processed by
   *   `\Drupal\Core\Entity\EntityViewBuilder::buildMultiple()`.
   *
   * @return array
   *   The processed render array.
   */
  public function preRender(array $build) {
    // Add a wrapper around the list.
    $build['#prefix'] = '<ul class="apigee-package-list">';
    $build['#suffix'] = '</ul>';
    // Wrap each package as a list item.
    foreach (Element::children($build) as $index) {
      // Header HTML.
      $header_html = '
        <div class="apigee-sdk-package-basic">
          <div class="apigee-package-label">@package_label</div>
          <div class="apigee-sdk-product-list-basic">(<span class="apigee-sdk-product"> @product_labels </span>)</div>
        </div>
        <div class="apigee-package-details">';
      // Build a list of product labels for the basic info header.
      $product_labels = [];
      foreach ($build[$index]['#package']->get('apiProducts') as $item) {
        $product_labels[] = $item->entity->label();
      }
      $build[$index]['#prefix'] = new FormattableMarkup('<li class="apigee-sdk-package">' . $header_html, [
        '@package_label' => $build[$index]['#package']->label(),
        '@product_labels' => implode(', ', $product_labels),
      ]);
      $build[$index]['#suffix'] = '</div></li>';
    }

    return $build;
  }

}
