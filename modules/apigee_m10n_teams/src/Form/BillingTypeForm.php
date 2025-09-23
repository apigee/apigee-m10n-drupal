<?php

/**
 * Copyright 2025 Google Inc.
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

namespace Drupal\apigee_m10n_teams\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\apigee_m10n\MonetizationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\apigee_m10n_teams\MonetizationTeamsInterface;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_edge_teams\Entity\Team;

/**
 * Provides a form to edit billing type of team.
 */
class BillingTypeForm extends FormBase {

  /**
   * Team.
   *
   * @var \Drupal\apigee_edge_teams\Entity\Team
   */
  protected $team;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

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
   * The Monetization base.
   *
   * @var \Drupal\apigee_m10n_teams\MonetizationTeamsInterface
   */
  protected $team_monetization;

  /**
   * The monetization service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * Constructs a CompanyProfileForm object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   The logger.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\apigee_m10n_teams\MonetizationTeamsInterface $team_monetization
   *   Teams monetization factory.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The monetization service.
   */
  public function __construct(LoggerChannelFactory $loggerFactory, MessengerInterface $messenger, RouteMatchInterface $routeMatch, MonetizationTeamsInterface $team_monetization, MonetizationInterface $monetization) {
    $this->loggerFactory = $loggerFactory->get('apigee_m10n');
    $this->messenger = $messenger;
    $this->routeMatch = $routeMatch;
    $this->team_monetization = $team_monetization;
    $this->monetization = $monetization;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $user = $container->get('current_route_match')->getParameter('user');

    return new static(
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('current_route_match'),
      $container->get('apigee_m10n.teams'),
      $container->get('apigee_m10n.monetization')
    );
  }

  /**
   * Checks current users access to Billing Profile page.
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
    if (!$this->monetization->isOrganizationApigeeXorHybrid()) {
      return AccessResult::forbidden('Only accessible for ApigeeX organization');
    }

    return AccessResult::allowedIf(
      $account->hasPermission('update any billing type')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_billing_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?TeamInterface $team = NULL) {
    $this->team = Team::load($team->id());
    $billingType = ['postpaid' => 'Postpaid' , 'prepaid' => 'Prepaid'];
    $team_billingtype = $this->team_monetization->getAppGroupBillingtype($team);
    $form['billingtype'] = [
      '#type' => 'radios',
      '#title' => $this->t('Billing Type'),
      '#options' => $billingType,
      '#default_value' => $team_billingtype ? strtolower($team_billingtype) : 'postpaid',
      '#description' => $this->t('Select the billing type for the team.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
      '#button_type' => 'primary',
      '#states' => [
        'disabled' => [
          ':input[name="billingtype"]' => ['value' => strtolower($team_billingtype)],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $team_id = $this->team->id();
    $billingtype_selected = $form_state->getValue('billingtype');
    $form_state->setRedirect('apigee_m10n_teams.teambillingtype.confirm', ['team' => $team_id, 'billingtype' => $billingtype_selected]);
  }

}
