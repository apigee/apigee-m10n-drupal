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

namespace Drupal\apigee_m10n\Entity\Form;

use Apigee\Edge\Api\Monetization\Entity\LegalEntityInterface;
use Apigee\Edge\Exception\ClientErrorException;
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
class PurchasedPlanForm extends FieldableEdgeEntityForm {

  /**
   * Developer legal name attribute name.
   */
  public const LEGAL_NAME_ATTR = 'MINT_DEVELOPER_LEGAL_NAME';

  /**
   * Insufficient funds API error code.
   */
  public const INSUFFICIENT_FUNDS_ERROR = 'mint.insufficientFunds';

  public const MY_PURCHASES_CACHE_TAG = 'apigee_my_purchased_plans';

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
    return $this->conflictForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // If the user has already purchased this plan, show a message instead.
    /** @var \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan */
    if (($rate_plan = $this->getEntity()->getRatePlan()) && ($this->monetization->isDeveloperAlreadySubscribed($this->currentUser->getEmail(), $rate_plan))) {
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

      if ($form_state->get('planConflicts')) {
        $parameters = $this->currentRouteMatch->getParameters()->all();
        $actions['cancel'] = [
          '#title' => $this->t('Cancel'),
          '#type'  => 'link',
          '#url'   => Url::fromRoute('apigee_monetization.plans', ['user' => $parameters['user']->id()]),
        ];
      }
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
      // Auto-populate legal name when developer has no legal name attribute.
      if (empty($developer->getAttributeValue(static::LEGAL_NAME_ATTR))) {
        $developer->setAttribute(static::LEGAL_NAME_ATTR, $developer_id);
        $developer->save();
      }

      $display_name = $this->getEntity()->getRatePlan()->getDisplayName();
      Cache::invalidateTags([static::MY_PURCHASES_CACHE_TAG]);

      // This means the user has confirmed purchase. We can suppress warning and
      // terminates all purchased rate plans that the developer has two product
      // bundles that contain the conflicting products. It then purchases the
      // rate plan for the developer.
      if ($form_state->get('confirm')) {
        $this->getEntity()->setSuppressWarning(TRUE);
      }

      if ($this->getEntity()->save()) {
        $this->messenger->addStatus($this->t('You have purchased %label plan', [
          '%label' => $display_name,
        ]));
        $form_state->setRedirect('entity.purchased_plan.developer_collection', ['user' => $this->getEntity()->getOwnerId()]);
      }
      else {
        $this->messenger->addWarning($this->t('Unable to purchase %label plan', [
          '%label' => $display_name,
        ]));
      }
    }
    catch (\Exception $e) {
      $client_error = $e->getPrevious();

      if ($client_error instanceof ClientErrorException
        && $client_error->getEdgeErrorCode() === 'mint.developerHasFollowingOverlapRatePlans'
      ) {
        $form_state->set('planConflicts', $this->getOverlappingProducts($e->getMessage()));
        $form_state->setRebuild(TRUE);
      }
      // If insufficient funds error, format nicely and add link to add credit.
      if ($client_error instanceof ClientErrorException && $client_error->getEdgeErrorCode() === static::INSUFFICIENT_FUNDS_ERROR) {
        preg_match_all('/\[(?\'amount\'.+)\]/', $e->getMessage(), $matches);
        $amount = $matches['amount'][0] ?? NULL;
        $rate_plan = $this->getEntity()->getRatePlan();
        $currency_id = $rate_plan->getCurrency()->id();

        $amount_formatted = $this->monetization->formatCurrency($matches['amount'][0], $currency_id);
        $insufficient_funds_error_message = $this->t('You have insufficient funds to purchase plan %plan. @adenndum', [
          '%plan' => $rate_plan->label(),
          '%amount' => $amount_formatted,
          '@adenndum' => $amount ? $this->t('To purchase this plan you are required to add at least %amount to your account.', ['%amount' => $amount_formatted]) : '',
        ]);
        $purchased_plan = $this->getEntity();
        $this->moduleHandler->alter('apigee_m10n_insufficient_balance_error_message', $insufficient_funds_error_message, $purchased_plan);
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

    /* @var \Drupal\apigee_m10n\Entity\PurchasedPlan $purchased_plan */
    $purchased_plan = $form_state->getFormObject()->getEntity();
    $rate_plan = $purchased_plan->getRatePlan();
    $user = $purchased_plan->getOwner();

    /* @var \Drupal\apigee_m10n\ApigeeSdkControllerFactory $sdk */
    $sdk = \Drupal::service('apigee_m10n.sdk_controller_factory');
    try {
      $developer = $sdk->developerController()->load($user->getEmail());
    }
    catch (\Exception $e) {
      $developer = NULL;
    }

    // If developer is prepaid, check for sufficient balance to purchase to the
    // rate plan.
    if ($developer && $developer->getBillingType() == LegalEntityInterface::BILLING_TYPE_PREPAID) {
      $prepaid_balances = [];
      foreach ($this->monetization->getDeveloperPrepaidBalances($user, new \DateTimeImmutable('now')) as $prepaid_balance) {
        $prepaid_balances[$prepaid_balance->getCurrency()->id()] = $prepaid_balance->getCurrentBalance();
      }

      // Minimum balance needed is at least the setup fee.
      // @see https://docs.apigee.com/api-platform/monetization/create-rate-plans.html#rateplanops
      $min_balance_needed = $rate_plan->getSetUpFee();
      $currency_id = $rate_plan->getCurrency()->id();
      $prepaid_balances[$currency_id] = $prepaid_balances[$currency_id] ?? 0;
      if ($min_balance_needed > $prepaid_balances[$currency_id]) {
        $form['insufficient_balance'] = [
          '#type' => 'container',
        ];

        $form['insufficient_balance'][$currency_id] = [
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

  /**
   * Generate conflict form.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  protected function conflictForm(array $form, FormStateInterface $form_state) {
    if ($items = $form_state->get('planConflicts')) {
      $form['conflicting'] = [
        '#theme' => 'conflicting_products',
        '#items' => $items,
      ];

      $form['warning'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            $this->t('<strong>Warning:</strong> This action cannot be undone.'),
          ],
        ],
      ];

      $form_state->set('confirm', TRUE);

      unset($form['startDate']);
    }
    return $form;
  }

  /**
   * Parse overlap error message.
   *
   * @param string $message
   *   Error message.
   *
   * @return array
   *   Conflicting/overlapping plans.
   */
  protected function getOverlappingProducts($message) {
    $overlaps = json_decode(substr($message, strpos($message, '=') + 1), TRUE);

    // Remove prefix xxx@ from product ids.
    $overlaps = array_map(function ($products) {
      $values = [];
      foreach ($products as $product_id => $product_name) {
        $values[substr($product_id, strrpos($product_id, '@') + 1)] = $product_name;
      }
      return $values;
    }, $overlaps);

    $product_bundle = $this->getEntity()->getRatePlan()->getPackage();

    // Process products in attempted purchased plan.
    $products = [];
    foreach ($product_bundle->getApiProducts() as $product) {
      $products[$product->id()] = $product;
    }

    $plan_items = [];
    foreach ($overlaps as $plan_id => $overlapping_products) {
      [$id, $name] = explode('|', $plan_id);
      $plan_item = [
        'data' => $name,
        'children' => [],
      ];
      $additional = array_diff_key($products, $overlapping_products);
      $excluded = array_diff_key($overlapping_products, $products);
      $conflicting = array_intersect_key($products, $overlapping_products);
      $overlapping = [
        'Conflicting products' => $conflicting,
        'Additional products' => $additional,
        'Excluded products' => $excluded,
      ];
      foreach ($overlapping as $situation => $situation_products) {
        if (!empty($situation_products)) {
          $product_items = [
            'data' => $this->t($situation),
            'children' => [],
          ];
          foreach ($situation_products as $situation_product) {
            if ($situation_product instanceof ApiProductInterface && !in_array($situation_product->getDisplayName(), $product_items['children'])) {
              $product_items['children'][] = $situation_product->getDisplayName();
            }
          }
          $plan_item['children'][] = $product_items;
        }
      }
      $plan_items[] = $plan_item;
    }

    return $plan_items;

  }

}
