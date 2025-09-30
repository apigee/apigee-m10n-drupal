<?php

/**
 * Copyright 2021 Google Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

namespace Drupal\apigee_m10n_teams\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\apigee_m10n_teams\MonetizationTeamsInterface;
use Drupal\apigee_edge_teams\Entity\Team;
use Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a confirmation form to confirm updating of team billing type.
 */
class ConfirmUpdateForm extends ConfirmFormBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Apigee Monetization base service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $team_monetization;

  /**
   * The billingtype selected.
   *
   * @var string
   */
  protected $billingtype_selected;

  /**
   * The team is.
   *
   * @var string
   */
  protected $teamId;

  /**
   * The monetization service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * The team access service.
   *
   * @var \Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface
   */
  protected $teamAccess;

  /**
    * The entity type manager.
    *
    * @var \Drupal\Core\Entity\EntityTypeManagerInterface
    */
  private $entityTypeManager;

  /**
   * Constructs a Confirmation object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\apigee_m10n_teams\MonetizationTeamsInterface $team_monetization
   *   Teams monetization factory.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The monetization service.
   * @param \Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface $team_access
   *   The team access service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(MessengerInterface $messenger, RouteMatchInterface $routeMatch, MonetizationTeamsInterface $team_monetization, MonetizationInterface $monetization, TeamPermissionAccessInterface $team_access, EntityTypeManagerInterface $entity_type_manager) {
    $this->messenger = $messenger;
    $this->routeMatch = $routeMatch;
    $this->team_monetization = $team_monetization;
    $this->teamId = $routeMatch->getParameter('team');
    $this->billingtype_selected = $routeMatch->getParameter('billingtype');
    $this->monetization = $monetization;
    $this->teamAccess = $team_access;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('current_route_match'),
      $container->get('apigee_m10n.teams'),
      $container->get('apigee_m10n.monetization'),
      $container->get('apigee_m10n_teams.access_check.team_permission'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Checks current team access to Billing Profile page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Grants access to the route if passed permissions are present.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $team_id = $route_match->getParameter('team');
    $team = $this->entityTypeManager->getStorage('team')->load($team_id);

    if (!$this->monetization->isOrganizationApigeeXorHybrid()) {
      return AccessResult::forbidden('Only accessible for ApigeeX organization');
    }
    if (!$this->teamAccess->allowedIfHasTeamPermissions($team, $account, ['update billing type'])) {
      return AccessResult::forbidden();

    }

    return AccessResult::allowed();

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->team_monetization->updateBillingtype($this->teamId, strtoupper($this->billingtype_selected));
      $this->messenger->addStatus($this->t('Billing type of the team is saved.'));
      $form_state->setRedirect('apigee_m10n_teams.teambillingtype', ['team' => $this->teamId]);
      drupal_flush_all_caches();
    }
    catch (\Exception $e) {
      $this->messenger->addError($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "team_confirm_change_billing_type_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('apigee_m10n_teams.teambillingtype', ['team' => $this->teamId]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {

    return $this->t('Are you sure you want to change the billing type for team - %teamname?', ['%teamname' => $this->teamId]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $team = Team::load($this->teamId);
    // Fetch the billing type of the team.
    $original = $this->team_monetization->getAppGroupBillingtype($team);
    if ('prepaid' == strtolower($original) && 'postpaid' == strtolower($this->billingtype_selected)) {
      return $this->t('If the team billing type is changed from "prepaid" to "postpaid," any existing prepaid balance will be treated as a credit transaction when calculating amounts due.');
    }
    elseif (('postpaid' == strtolower($original) || !($original))&& 'prepaid' == strtolower($this->billingtype_selected)) {
      return $this->t('If the team billing type is changed from "postpaid" to "prepaid," team should perform a balance top-up to ensure that API calls are not blocked due to an insufficient balance.');
    }
    elseif (!($original) && 'postpaid' == strtolower($this->billingtype_selected)) {
      return $this->t('The billing type will to switched to Postpaid.');
    }
  }

}
