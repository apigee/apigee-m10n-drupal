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

namespace Drupal\apigee_m10n_add_credit\Controller;

use Drupal\apigee_edge_teams\TeamMembershipManagerInterface;
use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AddCreditController.
 */
class AddCreditController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The commerce_product entity view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The team membership manager.
   *
   * @var \Drupal\apigee_edge_teams\TeamMembershipManagerInterface
   */
  protected $teamMembershipManager;

  /**
   * AddCreditController constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $view_builder
   *   The commerce_product entity view builder.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\apigee_edge_teams\TeamMembershipManagerInterface $team_membership_manager
   *   The team membership manager.
   */
  public function __construct(EntityStorageInterface $storage, EntityViewBuilderInterface $view_builder, ConfigFactoryInterface $config_factory, RouteMatchInterface $route_match, TeamMembershipManagerInterface $team_membership_manager) {
    $this->viewBuilder = $view_builder;
    $this->configFactory = $config_factory;
    $this->storage = $storage;
    $this->routeMatch = $route_match;
    $this->teamMembershipManager = $team_membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('commerce_product'),
      $container->get('entity.manager')->getViewBuilder('commerce_product'),
      $container->get('config.factory'),
      $container->get('current_route_match'),
      $container->get('apigee_edge_teams.team_membership_manager')
    );
  }

  /**
   * Returns a renderable array for the add credit page.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The add credit entity.
   * @param string $currency_id
   *   The currency id.
   *
   * @return array
   *   A renderable array.
   */
  public function view(EntityInterface $entity = NULL, string $currency_id = NULL) {
    // Throw an exception if a product has not been configured for the currency.
    if (!($product = $this->getProductForCurrency($currency_id))) {
      $this->messenger()->addError($this->t('Cannot add credit to currency @currency_id.', [
        '@currency_id' => $currency_id,
      ]));
      throw new NotFoundHttpException();
    }

    return $this->viewBuilder->view($product);
  }

  /**
   * Checks access for the add credit routes.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    // Developer.
    if ($user = $this->routeMatch->getParameter('user')) {
      return AccessResult::allowedIf(
        $account->hasPermission('add credit to any developer prepaid balance')
        || ($account->hasPermission('add credit to own developer prepaid balance') && $account->id() === $user->id())
      );
    }

    // Team.
    if ($team = $this->routeMatch->getParameter('team')) {
      if ($account->hasPermission('add credit to any team prepaid balance')) {
        return AccessResult::allowed();
      }

      if ($account->hasPermission('add credit to own team prepaid balance')) {
        // Check if the user belongs to this team.
        if ($team_ids = \Drupal::service('apigee_edge_teams.team_membership_manager')
          ->getTeams($account->getEmail())) {
          return AccessResult::allowedIf(in_array($team->id(), $team_ids));
        }
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * Helper to get the configured product from the currency id.
   *
   * @param string $currency_id
   *   The currency id.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   A product entity if found. Otherwise null.
   */
  protected function getProductForCurrency(string $currency_id): ?ProductInterface {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    if (($product_id = $this->configFactory->get(AddCreditConfig::CONFIG_NAME)->get("products.$currency_id.product_id"))
      && ($product = $this->storage->load($product_id))) {
      return $product;
    }

    return NULL;
  }

}
