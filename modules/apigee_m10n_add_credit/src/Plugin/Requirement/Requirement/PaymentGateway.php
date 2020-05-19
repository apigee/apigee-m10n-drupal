<?php

/*
 * Copyright 2019 Google Inc.
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

namespace Drupal\apigee_m10n_add_credit\Plugin\Requirement\Requirement;

use Drupal\commerce_payment\PaymentGatewayManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\requirement\Plugin\RequirementBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check that at least a commerce payment gateway exists.
 *
 * @Requirement(
 *   id = "payment_gateway",
 *   group="apigee_m10n_add_credit",
 *   label = "Payment gateway",
 *   description = "Configure a manual payment gateway to handle prepaid balance checkouts (useful for tests).",
 *   action_button_label="Create payment gateway",
 *   severity="error"
 * )
 */
class PaymentGateway extends RequirementBase implements ContainerFactoryPluginInterface {

  /**
   * The payment gateway plugin manager.
   *
   * @var \Drupal\commerce_payment\PaymentGatewayManager
   */
  protected $paymentGatewayManager;

  /**
   * PaymentGateway constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_payment\PaymentGatewayManager $payment_gateway_manager
   *   The payment gateway plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PaymentGatewayManager $payment_gateway_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->paymentGatewayManager = $payment_gateway_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.commerce_payment_gateway')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['gateway'] = [
      '#markup' => $this->t('Configure a payment gateway to handle prepaid balance checkouts.'),
    ];

    $form['warning'] = [
      '#theme' => 'status_messages',
      '#message_list' => [
        'warning' => [
          $this->t('Note: a manual payment gateway will be created. This is useful for tests only. <a href=":url">Click here</a> if you need to setup a different payment gateway.', [
            ':url' => Url::fromRoute('entity.commerce_payment_gateway.add_form')->toString(),
          ]),
        ],
      ],
    ];

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $this->t('Test gateway'),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => '\Drupal\commerce_payment\Entity\PaymentGateway::load',
      ],
    ];

    $form['plugin'] = [
      '#type' => 'hidden',
      '#value' => 'manual',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    try {
      $gateway = $this->getEntityTypeManager()
        ->getStorage('commerce_payment_gateway')
        ->create($form_state->getValues());
      $gateway->save();
    }
    catch (\Exception $exception) {
      watchdog_exception('apigee_m10n_add_credit', $exception);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(): bool {
    return $this->getModuleHandler()->moduleExists('apigee_m10n_add_credit') && !empty($this->paymentGatewayManager->getDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  public function isCompleted(): bool {
    return count($this->getEntityTypeManager()->getStorage('commerce_payment_gateway')->loadMultiple());
  }

}
