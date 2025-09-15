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

namespace Drupal\apigee_m10n_teams\Controller;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\Controller\PrepaidBalanceXControllerBase;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface;
use Drupal\apigee_m10n_teams\Form\TeamPrepaidBalanceReportsDownloadForm;
use Drupal\apigee_m10n_teams\TeamSdkControllerFactoryAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Controller for team balances.
 */
class TeamPrepaidBalanceXController extends PrepaidBalanceXControllerBase {

  use TeamSdkControllerFactoryAwareTrait;

  /**
   * The team access check.
   *
   * @var \Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface
   */
  protected $team_access_check;

  /**
   * Drupal core messenger service (for adding flash messages).
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * BillingController constructor.
   *
   * @param \Drupal\apigee_edge\SDKConnectorInterface $sdk_connector
   *   The `apigee_edge.sdk_connector` service.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The `apigee_m10n.monetization` service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface $team_access_check
   *   The team permission access checker.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Drupal core messenger service (for adding flash messages).
   */
  public function __construct(SDKConnectorInterface $sdk_connector, MonetizationInterface $monetization, FormBuilderInterface $form_builder, AccountInterface $current_user, ModuleHandlerInterface $module_handler, TeamPermissionAccessInterface $team_access_check, MessengerInterface $messenger) {
    parent::__construct($sdk_connector, $monetization, $form_builder, $current_user, $module_handler, $messenger);
    $this->team_access_check = $team_access_check;
  }

  /**
   * {@inheritdoc}  
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('apigee_edge.sdk_connector'),
      $container->get('apigee_m10n.monetization'),
      $container->get('form_builder'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('apigee_m10n_teams.access_check.team_permission'),
      $container->get('messenger')

    );
  }

  /**
   * View prepaid balance and account statements for teams.
   *
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The Apigee Edge team.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array or a redirect response.
   *
   * @throws \Exception
   */
  public function teamBalancePage(TeamInterface $team) {
    // Set the entity for this page call.
    $this->entity = $team;

    return $this->render();
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $balance_controller = $this->teamControllerFactory()->teamBalancexController($this->entity->id());
    // Make sure to return an array.
    return ($list = $balance_controller->getPrepaidBalance(new \DateTimeImmutable('now'))) ? $list : [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDownloadFormClass() {
    return TeamPrepaidBalanceReportsDownloadForm::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function canRefreshBalance() {

    return $this->team_access_check->hasTeamPermission($this->entity, $this->currentUser, 'refresh prepaid balance');
  }


}
