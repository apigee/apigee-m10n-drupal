<?php

/**
 * Copyright 2020 Google Inc.
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

namespace Drupal\apigee_m10n\Form;

use Apigee\Edge\Api\Monetization\Structure\Reports\Criteria\RevenueReportCriteria;
use Apigee\Edge\Exception\ClientErrorException;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a base form for reports download.
 */
abstract class ReportsDownloadFormBase extends FormBase {

  /**
   * The Apigee Monetization base service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity to generate reports for.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The SDK controller factory.
   *
   * @var \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface
   */
  protected $sdkControllerFactory;

  /**
   * ReportsForm constructor.
   *
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The Apigee Monetization base service.
   * @param \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface $sdk_controller_factory
   *   The SDK controller factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(MonetizationInterface $monetization, ApigeeSdkControllerFactoryInterface $sdk_controller_factory, RouteMatchInterface $route_match, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->monetization = $monetization;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->sdkControllerFactory = $sdk_controller_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('apigee_m10n.monetization'),
      $container->get('apigee_m10n.sdk_controller_factory'),
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reports_download_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state, EntityInterface $entity) {
    $this->entity = $entity;

    $form['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Download revenue reports'),
      '#attributes' => ['class' => ['label']],
    ];

    // Get supported currencies.
    $supported_currencies = $this->monetization->getSupportedCurrencies();

    if (empty($supported_currencies)) {
      $form['currency'] = [
        '#markup' => $this->t('There are no supported currencies for your account.'),
      ];

      return $form;
    }

    // Build currency options.
    $currency_options = [];
    /** @var \Apigee\Edge\Api\Monetization\Entity\SupportedCurrencyInterface $currency */
    foreach ($supported_currencies as $currency) {
      $currency_options[$currency->id()] = "{$currency->getName()} ({$currency->getDisplayName()})";
    }

    $form['entity_id'] = [
      '#type' => 'value',
      '#value' => $this->getEntityId(),
    ];

    $form['currency'] = [
      '#title' => $this->t('Select currency'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        '' => $this->t('Select currency'),
      ] + $currency_options,
    ];

    $form['from_date'] = [
      '#title' => $this->t('From date'),
      '#type' => 'datelist',
      '#required' => TRUE,
      '#date_part_order' => ['month', 'day', 'year'],
      '#date_year_range' => '2010:+',
      '#default_value' => (new DrupalDateTime())->modify('first day of this month'),
    ];

    $form['to_date'] = [
      '#title' => $this->t('To date'),
      '#type' => 'datelist',
      '#required' => TRUE,
      '#date_part_order' => ['month', 'day', 'year'],
      '#date_year_range' => '2010:+',
      '#default_value' => new DrupalDateTime(),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download report'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\Core\Datetime\DrupalDateTime $from_date */
    $from_date = $form_state->getValue('from_date');
    /** @var \Drupal\Core\Datetime\DrupalDateTime $to_date */
    $to_date = $form_state->getValue('to_date');

    // Validate date range.
    if ($from_date->diff($to_date)->invert === 1) {
      $form_state->setErrorByName('to_date', $this->t('The to date cannot be before the from date.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $currency = $form_state->getValue('currency');
    /** @var \Drupal\Core\Datetime\DrupalDateTime $from_date */
    $from_date = $form_state->getValue('from_date');
    /** @var \Drupal\Core\Datetime\DrupalDateTime $to_date */
    $to_date = $form_state->getValue('to_date');
    $entity_id = $form_state->getValue('entity_id');

    try {
      if ($report = $this->getRevenueReport($entity_id, new \DateTimeImmutable("@{$from_date->getTimestamp()}"), new \DateTimeImmutable("@{$to_date->getTimestamp()}"), $currency)) {
        $filename = "revenue-report-{$from_date->format('Y-m-d')}-{$to_date->format('Y-m-d')}.csv";
        $response = new Response($report);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=$filename");
        $form_state->setResponse($response);
      }
    }
    catch (ClientErrorException $exception) {
      $form_state->setRebuild(TRUE);
      $this->messenger()
        ->addError($this->t('There are no revenue reports for the selected dates.'));
    }
  }

  /**
   * Checks if current user can access reports download.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Grants access to the route if passed permissions are present.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface {
    $user = $route_match->getParameter('user');
    return AccessResult::allowedIf(
      $account->hasPermission('download any reports') ||
      ($account->hasPermission('download own reports') && $account->id() === $user->id())
    );
  }

  /**
   * Returns a CSV string for revenue.
   *
   * TODO: Refactor this to an MonetizationInterface.
   *
   * @param string $developer_id
   *   The developer id.
   * @param \DateTimeImmutable $from_date
   *   The from month for the report.
   * @param \DateTimeImmutable $to_date
   *   The to month for the report.
   * @param string $currency
   *   The currency id. Example: usd.
   *
   * @return null|string
   *   A CSV string of revenue report.
   */
  private function getRevenueReport(string $developer_id, \DateTimeImmutable $from_date, \DateTimeImmutable $to_date, string $currency): ?string {
    $controller = $this->sdkControllerFactory->developerReportDefinitionController($developer_id);
    $criteria = new RevenueReportCriteria($from_date, $to_date);
    $criteria
      ->developers($developer_id)
      ->currencies($currency)
      ->showTransactionDetail(TRUE);
    return $controller->generateReport($criteria);
  }

  /**
   * Returns the entity id for which the report should be generated for.
   *
   * @return string
   *   The email of the developer or the name of the team.
   */
  abstract protected function getEntityId(): string;

}
