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

namespace Drupal\apigee_m10n\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\UserInterface;
use Drupal\apigee_edge\Entity\Developer;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a form to edit developer profile information.
 */
class BillingDetailsForm extends FormBase {

  /**
   * Developer legal name attribute name.
   */
  const LEGAL_NAME_ATTR = 'MINT_DEVELOPER_LEGAL_NAME';

  /**
   * Developer billing type attribute name.
   */
  const BILLING_TYPE_ATTR = 'MINT_BILLING_TYPE';

  /**
   * Developer.
   *
   * @var \Drupal\apigee_edge\Entity\Developer
   */
  protected $developer;

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
   * Constructs a CompanyProfileForm object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   The logger.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(LoggerChannelFactory $loggerFactory, MessengerInterface $messenger, RouteMatchInterface $routeMatch) {
    $this->loggerFactory = $loggerFactory->get('apigee_m10n');
    $this->messenger = $messenger;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('current_route_match')
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
    $user = $route_match->getParameter('user');
    return AccessResult::allowedIf(
      $account->hasPermission('view any billing details') ||
      ($account->hasPermission('view own billing details') && $account->id() === $user->id())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_billing_details_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $this->developer = Developer::load($user->getEmail());

    $form['company'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Company Details'),
    ];
    $form['company']['legal_company_name'] = [
      '#title' => $this->t('Legal Company Name'),
      '#type' => 'textfield',
      '#default_value' => $this->developer->getAttributeValue(static::LEGAL_NAME_ATTR),
      '#required' => TRUE,
    ];
    $form['billing'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Billing Type'),
    ];
    $form['billing']['billing_type'] = [
      '#markup' => $this->developer->getAttributeValue(static::BILLING_TYPE_ATTR),
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->developer->setAttribute(static::LEGAL_NAME_ATTR, $form_state->getValue('legal_company_name'));
      if ($this->developer->save()) {
        $this->messenger->addStatus($this->t('The changes have been saved.'));
      }
    }
    catch (\Throwable $t) {
      $this->loggerFactory->error('Could not save company profile information: ' . $t->getMessage());
    }
  }

}
