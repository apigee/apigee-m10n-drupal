<?php

/*
 * Copyright 2021 Google Inc.
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

namespace Drupal\apigee_m10n\Entity\Form;

use Apigee\Edge\Api\Monetization\Entity\LegalEntityInterface;
use Apigee\Edge\Exception\ClientErrorException;
use Apigee\Edge\Exception\ServerErrorException;
use Drupal\apigee_edge\Entity\ApiProductInterface;
use Drupal\apigee_edge\Entity\Developer;
use Drupal\apigee_edge\Entity\Form\FieldableEdgeEntityForm;
use Drupal\apigee_m10n\Form\PrepaidBalanceConfigForm;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Purchased plan entity form.
 */
class PurchasedProductForm extends FieldableEdgeEntityForm {

  /**
   * Insufficient funds API error code.
   */
  public const INSUFFICIENT_FUNDS_ERROR = 'mint.service.developer_usage_exceeds_balance';
  public const DEVELOPER_WALLET_DOES_NOT_EXIST = 'keymanagement.service.developer_wallet_does_not_exist';

  public const MY_PURCHASES_PRODUCT_CACHE_TAG = 'apigee_my_purchased_products';

  /**
   * Messanger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current_route_match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Apigee Monetization utility service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a PurchasedPlanForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current_route_match service.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   Apigee Monetization utility service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(MessengerInterface $messenger, RouteMatchInterface $current_route_match, MonetizationInterface $monetization, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    $this->messenger = $messenger;
    $this->currentRouteMatch = $current_route_match;
    $this->monetization = $monetization;
    $this->config = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('current_route_match'),
      $container->get('apigee_m10n.monetization'),
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // @TODO: Make sure we find a better way to handle names
    // without adding rate plan ID this form is getting cached
    // and when rendered as a formatter.
    // Also known issue in core @see https://www.drupal.org/project/drupal/issues/766146.
    return parent::getFormId() . '_' . $this->getEntity()->getRatePlan()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Redirect to Rate Plan detail page on submit.

    $form['#action'] = $this->getEntity()->getRatePlan()->toUrl('purchase')->toString();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // If the user has already purchased this plan, show a message instead.
    /** @var \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan */
    $rate_plan = $this->getEntity()->getRatePlan();

    if (($rate_plan = $this->getEntity()->getRatePlan()) && ($this->monetization->isDeveloperAlreadySubscribedX($this->currentUser->getEmail(), $rate_plan))) {
      return [
        '#markup' => $this->t('You have already purchased %rate_plan.', [
          '%rate_plan' => $rate_plan->getDisplayName(),
        ]),
      ];
    }

    $form = parent::buildForm($form, $form_state);

    // We can't alter the form in the form() method because the actions buttons
    // get added on buildForm().
    $this->insufficientFundsWorkflow($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    // Set the save label if one has been passed into storage.
    if (!empty($actions['submit']) && ($save_label = $form_state->get('save_label'))) {
      $actions['submit']['#value'] = $save_label;
      $actions['submit']['#button_type'] = 'primary';
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      // Auto assign legal name.

      $developer_id = $this->getEntity()->getDeveloper()->getEmail();
      $developer = Developer::load($developer_id);

      $display_name = $this->getEntity()->getRatePlan()->getDisplayName();
      Cache::invalidateTags([static::MY_PURCHASES_PRODUCT_CACHE_TAG]);

      if ($this->getEntity()->save()) {
        $this->messenger->addStatus($this->t('You have purchased %label plan', [
          '%label' => $display_name,
        ]));
        $form_state->setRedirect('entity.purchased_product.developer_product_collection', ['user' => $this->getEntity()->getOwnerId()]);
        // Clear the cache to change the buy product page.
        $renderCache = \Drupal::service('cache.render');
        $renderCache->invalidateAll();
      }
      else {
        $this->messenger->addWarning($this->t('Unable to purchase %label plan', [
          '%label' => $display_name,
        ]));
      }
    }
    catch (\Exception $e) {
      $client_error = $e->getPrevious();

      if (($client_error instanceof ClientErrorException && $client_error->getEdgeErrorCode() === static::INSUFFICIENT_FUNDS_ERROR) || ($client_error instanceof ClientErrorException && $client_error->getEdgeErrorCode() === static::DEVELOPER_WALLET_DOES_NOT_EXIST)) {

        $rate_plan = $this->getEntity()->getRatePlan();
        $minimum_amount = $rate_plan->getSetupFeesPriceValue();
        $minimum_amount = reset($minimum_amount);

        $currency_id = $rate_plan->getCurrencyCode();

        $amount_formatted = $this->monetization->formatCurrency($minimum_amount['amount'], $currency_id);
        $insufficient_funds_error_message = $this->t('You have insufficient funds to purchase plan %plan. @adenndum', [
          '%plan' => $rate_plan->label(),
          '%amount' => $amount_formatted,
          '@adenndum' => $this->t('To purchase this plan you are required to add at least %amount to your account.', ['%amount' => $amount_formatted]),
        ]);
        $purchased_product = $this->getEntity();
        $this->moduleHandler->alter('apigee_m10n_insufficient_balance_error_purchased_product_message', $insufficient_funds_error_message, $purchased_product);
        $this->messenger->addError($insufficient_funds_error_message);
      }
      else {
        $this->messenger->addError($e->getMessage());
      }
    }
  }

  /**
   * Insufficient funds workflow.
   *
   * Handles the "add credit" link and purchase button status on purchase rate plan forms.
   *
   * @param array $form
   *   The form to alter.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Exception
   */
  protected function insufficientFundsWorkflow(array &$form, FormStateInterface $form_state) {
    // Check if insufficient_funds_workflow is disabled, and do nothing if so.
    if (!$this->config->get(PrepaidBalanceConfigForm::CONFIG_NAME)->get('enable_insufficient_funds_workflow') !== TRUE) {
      return;
    }

    /* @var \Drupal\apigee_m10n\Entity\PurchasedProduct $purchased_product */
    $purchased_product = $form_state->getFormObject()->getEntity();
    $rate_plan = $purchased_product->getRatePlan();
    $user = $purchased_product->getOwner();

    /* @var \Drupal\apigee_m10n\ApigeeSdkControllerFactory $sdk */
    $sdk = \Drupal::service('apigee_m10n.sdk_controller_factory');
    try {
      $developer_billing_type = $sdk->developerBillingTypeController($user->getEmail())->getAllBillingDetails();
    }
    catch (\Exception $e) {
      $developer_billing_type = NULL;
    }

    // If developer is prepaid, check for sufficient balance to purchase to the
    // rate plan.
    if ($developer_billing_type && $developer_billing_type->getbillingType() == LegalEntityInterface::BILLING_TYPE_PREPAID) {
      $prepaid_balances = [];
      foreach ($this->monetization->getDeveloperPrepaidBalancesX($user) as $prepaid_balance) {
        $prepaid_balances[$prepaid_balance->getBalance()->getCurrencyCode()] = $prepaid_balance->getBalance()->getUnits() + $prepaid_balance->getBalance()->getNanos();
      }

      // Minimum balance needed is at least the setup fee.
      // @see https://docs.apigee.com/api-platform/monetization/create-rate-plans.html#rateplanops
      $setup_fee = $rate_plan->getRatePlanxFee();

      $min_balance_needed = 0;

      foreach ($setup_fee as $key => $value) {
        $nanos = $value->getNanos() ?? 0;
        $units = $value->getUnits() ?? 0;
        $min_balance_needed = $units + $nanos;
      }

      $currency_id = $rate_plan->getCurrencyCode();

      $prepaid_balances[$currency_id] = $prepaid_balances[$currency_id] ?? 0;

      if ($min_balance_needed > $prepaid_balances[$currency_id]) {
        $form['insufficient_balance'] = [
          '#type' => 'container',
        ];

        $form['insufficient_balance'][strtolower($currency_id)] = [
          'message' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t('You have insufficient funds to purchase this rate plan.'),
          ],
        ];

        $form['startDate']['#access'] = FALSE;
        $form['actions']['submit']['#access'] = FALSE;
      }
    }
  }

}
