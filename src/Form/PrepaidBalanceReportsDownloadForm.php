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

namespace Drupal\apigee_m10n\Form;

use Apigee\Edge\Exception\ClientErrorException;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = NULL, array $supported_currencies = [], array $billing_documents = []) {
    $form['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Previous Prepaid Statements'),
      '#attributes' => ['class' => ['label']],
    ];

    // No form if there's no supported currency.
    if (!count($supported_currencies)) {
      $form['currency'] = [
        '#markup' => $this->t('There are no supported currencies for your account.'),
      ];

      return $form;
    }

    if (!count($billing_documents)) {
      $form['year'] = [
        '#markup' => $this->t('There are no billing documents for your account.'),
      ];

      return $form;
    }

    // Build currency options.
    $currency_options = [];
    foreach ($supported_currencies as $currency) {
      $currency_options[$currency->id()] = "{$currency->getName()} ({$currency->getDisplayName()})";
    }

    $form['currency'] = [
      '#title' => $this->t('Select an account'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        '' => $this->t('Select an account'),
      ] + $currency_options,
    ];

    // Build date options.
    $date_options = [];
    array_map(function ($document) use (&$date_options) {
      $date_options[$document->year][$document->year . '-' . $document->month] = ucwords(strtolower($document->monthEnum));
    }, $billing_documents);

    // Save this to form state to be available in submit callback.
    $form_state->set('entity', $entity);
    $form_state->set('currency_options', $currency_options);
    $form_state->set('date_options', $date_options);

    $form['year'] = [
      '#title' => $this->t('Select a year'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        '' => $this->t('Select a year'),
      ] + array_combine(array_keys($date_options), array_keys($date_options)),
    ];

    foreach ($date_options as $year => $date_option) {
      $form["month_$year"] = [
        '#title' => $this->t('Select a month'),
        '#type' => 'select',
        '#options' => [
          '' => $this->t('Select a month'),
        ] + $date_option,
        '#states' => [
          'required' => [
            'select[name="year"]' => ['value' => $year],
          ],
          'visible' => [
            'select[name="year"]' => ['value' => $year],
          ],
        ],
      ];
    }

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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (($year = $form_state->getValue('year')) && ($currency = $form_state->getValue('currency')) && ($date = $form_state->getValue("month_$year"))) {
      try {
        $billing_date = new \DateTimeImmutable($date);

        if ($report = $this->getReport($form_state->get('entity'), $billing_date, $currency)) {
          $filename = "prepaid-balance-report-$date.csv";
          $response = new Response($report);
          $response->headers->set('Content-Type', 'text/csv');
          $response->headers->set('Content-Disposition', "attachment; filename=$filename");
          $form_state->setResponse($response);
        }
      }
      catch (ClientErrorException $exception) {
        $currency_options = $form_state->get('currency_options');
        $date_options = $form_state->get('date_options');
        $this->messenger()->addError($this->t('There are no prepaid balance reports for account @account for @month @year.', [
          '@account' => $currency_options[$currency],
          '@month' => $date_options[$year][$date],
          '@year' => $year,
        ]));
      }
    }
  }

  /**
   * Generate a balance report.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The route entity for the report.
   * @param \DateTimeImmutable $billing_date
   *   The billing date.
   * @param string $currency
   *   The currency for the report.
   *
   * @return null|string
   *   A CSV string of prepaid balances.
   */
  public function getReport(EntityInterface $entity, \DateTimeImmutable $billing_date, $currency) {
    return $this->monetization->getPrepaidBalanceReports($entity->getEmail(), $billing_date, $currency);
  }

}
