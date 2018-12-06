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

use Drupal;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a form to to download prepaid balances.
 */
class PrepaidBalanceReportsDownloadForm extends FormBase {

  /**
   * The Apigee Monetization base service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * The Apigee SDK controller factory.
   *
   * @var \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface
   */
  protected $sdkControllerFactory;

  /**
   * The user from route.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * PrepaidBalancesDownloadForm constructor.
   *
   * @param \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface $sdk_controller_factory
   *   The SDK controller factory.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The Apigee Monetization base service.
   */
  public function __construct(ApigeeSdkControllerFactoryInterface $sdk_controller_factory, MonetizationInterface $monetization) {
    $this->monetization = $monetization;
    $this->sdkControllerFactory = $sdk_controller_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('apigee_m10n.sdk_controller_factory'),
      $container->get('apigee_m10n.monetization')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'previous_balances_reports_download_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    // Set the user.
    $this->user = $user;

    $form['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Previous Prepaid Statements'),
    ];

    // Get supported currencies.
    $supported_currency_controller = $this->sdkControllerFactory->supportedCurrencyController();

    /** @var \Apigee\Edge\Api\Monetization\Entity\SupportedCurrencyInterface[] $currencies */
    $currencies = $supported_currency_controller->getEntities();

    // No form if there's no supported currency.
    if (!count($currencies)) {
      $form['currency'] = [
        '#markup' => $this->t('There are no supported currencies for your account.'),
      ];

      return $form;
    }

    // Get billing documents.
    $billing_document_controller = $this->sdkControllerFactory->billingDocumentsController();
    $documents = $billing_document_controller->getEntities();

    if (!count($documents)) {
      $form['year'] = [
        '#markup' => $this->t('There are no billing documents for your account.'),
      ];

      return $form;
    }

    // Build currency options.
    $currency_options = [];
    foreach ($currencies as $currency) {
      $currency_options[$currency->id()] = "{$currency->getName()} ({$currency->getDisplayName()})";
    }

    // Save this to form state to be available in submit callback.
    $form_state->set('currency_options', $currency_options);

    $form['currency'] = [
      '#title' => $this->t('Select an account'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        '' => $this->t('Select an account'),
      ] + $currency_options,
    ];

    // Build date options.
    $options = [];
    array_map(function ($document) use (&$options) {
      $options[$document->year][$document->monthEnum] = ucwords(strtolower($document->monthEnum));
    }, $documents);

    $form['year'] = [
      '#title' => $this->t('Select a year'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        '' => $this->t('Select a year'),
      ] + array_combine(array_keys($options), array_keys($options)),
      '#ajax' => [
        'callback' => '::updateOptions',
        'wrapper' => 'month-container',
      ],
    ];

    $form['month_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'month-container',
      ],
    ];

    if (($year = $form_state->getValue('year')) && (isset($options[$year]))) {
      $form['month_container']['month'] = [
        '#title' => $this->t('Select a month'),
        '#type' => 'select',
        '#required' => TRUE,
        '#options' => [
          '' => $this->t('Select a month'),
        ] + $options[$year],
      ];
    };

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download CSV'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Rebuilds the month options.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return mixed
   *   The month_container form element.
   */
  public static function updateOptions(array $form, FormStateInterface $form_state) {
    return $form['month_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (($year = $form_state->getValue('year')) && ($month = $form_state->getValue('month')) && ($currency = $form_state->getValue('currency'))) {
      $date = new \DateTimeImmutable("$year-$month-1");

      $prepaid_balance_reports_controller = $this->sdkControllerFactory->prepaidBalanceReportsController($this->user->getEmail());
      if ($report = $prepaid_balance_reports_controller->getReport($date, $currency)) {
        $filename = "prepaid-balance-report-$year-$month.csv";
        $response = new Response($report);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename='$filename'");
        $form_state->setResponse($response);
      }
      else {
        // Show an error message if no reports found.
        \Drupal::messenger()
          ->addError($this->t('There are no prepaid balance reports for account @account for @month @year.', [
            '@account' => $form_state->get('currency_options')[$currency],
            '@month' => $month,
            '@year' => $year,
          ]));
      }
    }
  }

}
