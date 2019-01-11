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
use Drupal\Core\Session\AccountProxy;
use Drupal\user\UserInterface;
use Drupal\apigee_edge\Entity\Developer;
use Drupal\Core\Logger\LoggerChannelFactory;

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
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

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
   * Constructs a CompanyProfileForm object.
   *
   * @param \Drupal\Core\Session\AccountProxy $account
   *   Current user service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   The logger.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   */
  public function __construct(AccountProxy $account = NULL, LoggerChannelFactory $loggerFactory = NULL, MessengerInterface $messenger = NULL) {
    $this->developer = Developer::load($account->getEmail());
    $this->loggerFactory = $loggerFactory->get('apigee_m10n');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('logger.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'developer_company_profile_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
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
