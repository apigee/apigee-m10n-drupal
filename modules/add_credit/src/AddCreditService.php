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

namespace Drupal\apigee_m10n_add_credit;

use Drupal;
use Drupal\apigee_m10n_add_credit\Form\AddCreditAddToCartForm;
use Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeManagerInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowBase;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\Core\Session\AccountInterface;

/**
 * Helper service to handle basic module tasks.
 *
 * @package Drupal\apigee_m10n_add_credit
 */
class AddCreditService implements AddCreditServiceInterface {

  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $current_user;

  /**
   * The system theme config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The add credit plugin manager.
   *
   * @var \Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeManagerInterface
   */
  protected $addCreditPluginManager;

  /**
   * The add credit product manager.
   *
   * @var \Drupal\apigee_m10n_add_credit\AddCreditProductManagerInterface
   */
  protected $addCreditProductManager;

  /**
   * Constructor for the `apigee_m10n.add_credit` service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\apigee_m10n_add_credit\Plugin\AddCreditEntityTypeManagerInterface $add_credit_plugin_manager
   *   The add credit plugin manager.
   * @param \Drupal\apigee_m10n_add_credit\AddCreditProductManagerInterface $add_credit_product_manager
   *   The add credit product manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $user, AddCreditEntityTypeManagerInterface $add_credit_plugin_manager, AddCreditProductManagerInterface $add_credit_product_manager) {
    $this->config = $config_factory;
    $this->current_user = $user;
    $this->addCreditPluginManager = $add_credit_plugin_manager;
    $this->addCreditProductManager = $add_credit_product_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function mail($key, &$message, $params) {
    $params['@site'] = $this->config->get('system.site')->get('name');
    switch ($key) {
      case 'balance_adjustment_report':
        $options = ['langcode' => $message['langcode']];
        $message['subject'] = t('Add Credit successfully applied to account (@email@team_name) from @site', $params, $options);
        $message['body'][0] = t($params['report_text'], $params, $options);
        break;

      case 'balance_adjustment_error_report':
        $options = ['langcode' => $message['langcode']];
        $params['@site'] = $this->config->get('system.site')->get('name');
        $message['subject'] = t('Developer account add credit error from @site', $params, $options);
        $body = "There was an error applying a credit to an account. \n\r\n\r" . $params['report_text'] . "\n\r\n\r@error";
        $message['body'][0] = t($body, $params, $options);
        break;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    $fields = [];

    switch ($entity_type->id()) {
      case 'commerce_product':
        // The base field needs to be added to all product types for the storage
        // to be allocated but the option to enable will be hidden and unused
        // unless enabled for that bundle.
        $fields[AddCreditConfig::ADD_CREDIT_ENABLED_FIELD_NAME] = BaseFieldDefinition::create('boolean')
          ->setLabel(t('This is an Apigee add credit product'))
          ->setRevisionable(TRUE)
          ->setTranslatable(TRUE)
          ->setDefaultValue(FALSE);
        break;

      case 'commerce_product_variation':
        $fields['apigee_price_range'] = BaseFieldDefinition::create('apigee_price_range')
          ->setLabel(t('Price range'))
          ->setRevisionable(TRUE)
          ->setTranslatable(TRUE)
          ->setDisplayConfigurable('form', TRUE)
          ->setDisplayConfigurable('view', TRUE);
        break;

      case 'commerce_order_item':
        // Add a field to set the add credit target entity.
        $fields[AddCreditConfig::TARGET_FIELD_NAME] = BaseFieldDefinition::create('add_credit_target_entity')
          ->setLabel(t('Add credit target'))
          ->setRevisionable(TRUE)
          ->setTranslatable(TRUE)
          ->setRequired(TRUE)
          ->setDisplayConfigurable('form', TRUE)
          ->setDisplayConfigurable('view', TRUE);
        break;
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function entityBundleFieldInfo(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    // Make sure we are dealing with a product bundle that has Apigee add credit
    // enabled.
    if ($entity_type->id() === 'commerce_product'
      && ($product_type = ProductType::load($bundle))
      && $product_type->getThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_add_credit')
    ) {
      // Apigee add credit enabled products will automatically update a
      // developer's balance upon payment completion. This adds a base field to
      // the bundle to allow add credit to be enabled for products of the bundle
      // individually.
      $add_credit_base_def = clone $base_field_definitions[AddCreditConfig::ADD_CREDIT_ENABLED_FIELD_NAME];
      $add_credit_base_def
        ->setDefaultValue(TRUE)
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('form', ['weight' => 25])
        ->setDisplayConfigurable('view', TRUE);
      return [AddCreditConfig::ADD_CREDIT_ENABLED_FIELD_NAME => $add_credit_base_def];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formCommerceProductTypeEditFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    $product_type = $form_state->getFormObject()->getEntity();

    // Add an option to allow enabling Apigee add credit for a product type.
    $form['apigee_m10n_enable_add_credit'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable <em>Apigee Monetization Add Credit</em> for this product type.'),
      '#default_value' => $product_type->getThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_add_credit'),
    ];

    // Add an option to allow skip cart for a product type.
    $form['apigee_m10n_enable_skip_cart'] = [
      '#type' => 'checkbox',
      '#title' => t('Skip cart and go directly to checkout for this product type.'),
      '#default_value' => $product_type->getThirdPartySetting('apigee_m10n_add_credit', 'apigee_m10n_enable_skip_cart'),
    ];

    // Add our own callback so we can save the add_credit enabled setting.
    array_splice($form["actions"]["submit"]["#submit"], -1, 0, [[static::class, 'formCommerceProductTypeSubmit']]);
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeAlter(array &$entity_types) {
    // Update the form class for the add to cart form.
    $entity_types['commerce_order_item']->setFormClass('add_to_cart', AddCreditAddToCartForm::class);
  }

  /**
   * {@inheritdoc}
   */
  public function inlineEntityFormTableFieldsAlter(&$fields, $context) {
    if ($context['entity_type'] == 'commerce_product_variation') {
      if (isset($fields['price'])) {
        $fields['price']['type'] = 'callback';
        $fields['price']['callback'] = [static::class, 'inlineEntityFormTableFieldsPriceCallback'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fieldInfoAlter(&$info) {
    // Add a constraint to commerce_price.
    // This is used to validate the unit price if a price range is available.
    if (isset($info['commerce_price'])) {
      $info['commerce_price']['constraints']['PriceRangeUnitPrice'] = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fieldWidgetFormAlter(&$element, FormStateInterface $form_state, $context) {
    $field_definition = $context['items']->getFieldDefinition();
    $field_name = $field_definition->getName();
    $entity = $context['items']->getEntity();

    // No changes if field is not unit_price or entity is not commerce_order_item.
    if ($field_name != 'unit_price' || (!$entity instanceof OrderItemInterface)) {
      return;
    }

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $selected_variation */
    $selected_variation = $entity->getPurchasedEntity();
    $product = $selected_variation->getProduct();

    // Get the purchased entity from the form_state.
    $parents = ['purchased_entity', 0, 'variation'];
    if ($variation_id = NestedArray::getValue($form_state->getValues(), $parents)) {
      $selected_variations = array_filter($product->getVariations(), function (ProductVariationInterface $variation) use ($variation_id) {
        return $variation->id() == $variation_id;
      });
      $selected_variation = reset($selected_variations);
    }

    // Get the default value from the product variation.
    if ($selected_variation->hasField('apigee_price_range') && !$selected_variation->get('apigee_price_range')->isEmpty()) {
      $value = $selected_variation->get('apigee_price_range')->getValue();
      $value = reset($value);

      if (isset($value['default'])) {
        $element['amount']['#default_value'] = [
          'number' => $value['default'],
          'currency_code' => $value['currency_code'],
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    if (($flow = $form_state->getFormObject())
      && ($flow instanceof CheckoutFlowBase)
      && ($form['#step_id'] == 'review')
    ) {
      // Add a custom validation handler to check for add credit products.
      array_unshift($form['#validate'], [static::class, 'checkoutFormReviewValidate']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function apigeeM10nPrepaidBalanceListAlter(array &$build, EntityInterface $entity) {
    // TODO: This can be move to entity operations when/if prepaid balance are
    // made into entities.
    if ((count($build['table']['#rows']))) {
      $has_operations = FALSE;
      $config = $this->config->get(AddCreditConfig::CONFIG_NAME);
      $plugin_id = $entity->getEntityTypeId() === 'user' ? 'developer' : $entity->getEntityTypeId();
      $plugin = $this->addCreditPluginManager->getPluginById($plugin_id);

      $attributes['class'][] = 'button';
      if ($use_modal = $config->get('use_modal')) {
        $attributes['class'][] = 'use-ajax';
        $attributes['data-dialog-type'] = 'modal';
        $attributes['data-dialog-options'] = json_encode([
          'width' => 500,
          'height' => 500,
          'draggable' => FALSE,
          'autoResize' => FALSE,
        ]);
      }

      foreach ($build['table']['#rows'] as $currency_id => &$row) {
        if ($plugin->access($entity, $this->current_user)->isAllowed()
          && ($product = $this->addCreditProductManager->getProductForCurrency($currency_id))) {
          // Add cache tags even if product is not add credit enabled.
          // This allows cache invalidation when product is add credit enabled
          // at a later stage.
          $build['table']['#cache']['tags'] = Cache::mergeTags($build['table']['#cache']['tags'], $product->getCacheTags());

          // If the currency has a configured add credit product, add a link to
          // add credit to this balance.
          if ($this->addCreditProductManager->isProductAddCreditEnabled($product)) {
            $has_operations = TRUE;
            $row['data']['operations']['data'] = [
              '#type' => 'operations',
              '#links' => [
                'add_credit' => [
                  'title' => t('Add credit'),
                  'url' => $product->toUrl('canonical', [
                    'query' => [
                      AddCreditConfig::TARGET_FIELD_NAME => [
                        'target_type' => $plugin_id,
                        'target_id' => $plugin->getEntityId($entity),
                      ],
                    ],
                  ]),
                  'attributes' => $attributes,
                ],
              ],
              '#attributes' => [
                'class' => [
                  'add-credit',
                  'add-credit--' . $currency_id,
                ],
              ],
            ];
          }
        }
      }

      if ($has_operations) {
        $build['table']['#header']['operations'] = t('Operations');

        // Fill in empty operation column.
        foreach ($build['table']['#rows'] as $currency_id => &$row) {
          if (empty($row['data']['operations'])) {
            $row['data']['operations']['data'] = ['#markup' => ''];
          }
        }

        // Add modal libraries if there is at least one operation link.
        if ($use_modal) {
          $build['table']['#attached'] = [
            'library' => [
              'core/drupal.dialog.ajax',
              'core/jquery.ui.dialog',
            ],
          ];
        }
      }

      // Add cache contexts.
      $build['table']['#cache']['contexts'][] = 'user.permissions';
      $build['table']['#cache']['tags'] = Cache::mergeTags($build['table']['#cache']['tags'], ['config:' . AddCreditConfig::CONFIG_NAME]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function commerceProductAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // For add_credit_enabled products.
    if ($operation === 'view' && $this->addCreditProductManager->isProductAddCreditEnabled($entity)) {
      /** @var \Drupal\Core\Access\AccessResultReasonInterface $access */
      $access = AccessResult::allowedIfHasPermissions($account, array_keys($this->addCreditPluginManager->getPermissions()), 'OR');
      return $access->isAllowed() ? $access : AccessResult::forbidden($access->getReason());
    }

    return AccessResult::neutral();
  }

  /**
   * Custom validation handler for the checkout review step.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function checkoutFormReviewValidate(array $form, FormStateInterface $form_state) {
    if ($flow = $form_state->getFormObject()) {
      // Loop through all order items and see if we have any add_credit items.
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface[] $add_credit_items */
      $add_credit_items = [];
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $item */
      foreach ($flow->getOrder()->getItems() as $item) {
        if (Drupal::service('apigee_m10n_add_credit.product_manager')->isProductAddCreditEnabled($item->getPurchasedEntity()->getProduct())) {
          $add_credit_items[] = $item;
        }
      }

      if (count($add_credit_items)) {
        /** @var \Apigee\Edge\Api\Monetization\Entity\SupportedCurrencyInterface[] $supported_currencies */
        $supported_currencies = Drupal::service('apigee_m10n.monetization')
          ->getSupportedCurrencies();

        // Validate the total for order item against the minimum top up amount.
        foreach ($add_credit_items as $add_credit_item) {
          $price = $add_credit_item->getTotalPrice();
          $currency_code = strtolower($price->getCurrencyCode());
          // TODO: Fail validation if the currency does not exist.
          if (isset($supported_currencies[$currency_code])
            && ($supported_currency = $supported_currencies[$currency_code])
            && ($minimum_top_up_amount = $supported_currency->getMinimumTopUpAmount())
            && ((float) $price->getNumber() < $minimum_top_up_amount)
          ) {
            $form_state->setErrorByName('review', t('The minimum top up amount is @amount @currency_code.', [
              '@currency_code' => $supported_currency->getName(),
              '@amount' => Drupal::service('commerce_price.currency_formatter')->format($minimum_top_up_amount, $supported_currency->getName(), [
                'currency_display' => 'symbol',
              ]),
            ]));
          }
        }
      }
    }
  }

  /**
   * Submit callback for `::formCommerceProductTypeEditFormAlter()`.
   *
   * Add a third party setting to the product type to flag whether or not this
   * product type is should be used as an apigee to up product.
   *
   * @param array|null $form
   *   The add or edit form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function formCommerceProductTypeSubmit(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    if (($product_type = $form_state->getFormObject()->getEntity())) {
      // Save the enabled setting to third party settings.
      $product_type->setThirdPartySetting(
        'apigee_m10n_add_credit',
        'apigee_m10n_enable_add_credit',
        $form_state->getValue('apigee_m10n_enable_add_credit')
      );

      $product_type->setThirdPartySetting(
        'apigee_m10n_add_credit',
        'apigee_m10n_enable_skip_cart',
        $form_state->getValue('apigee_m10n_enable_skip_cart')
      );
    }
  }

  /**
   * Callback for the price inline table field.
   *
   * Formats the price field for the product variation when the variation is
   * in table mode on the product edit page.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The commerce variation entity.
   * @param array $variables
   *   The variables array.
   *
   * @return \Drupal\commerce_price\Price|null
   *   Renderable array of price.
   */
  public static function inlineEntityFormTableFieldsPriceCallback(ProductVariationInterface $variation, array $variables) {
    $formatter = \Drupal::service('commerce_price.currency_formatter');

    // If product variation has a price range return the default.
    if (($variation->hasField('apigee_price_range'))
      && ($price_range = $variation->get('apigee_price_range'))
      && (isset($price_range->default))
      && (isset($price_range->currency_code))
    ) {
      return $formatter->format($price_range->default, $price_range->currency_code);
    }

    // Fallback to the default price.
    if ($price = $variation->getPrice()) {
      return $formatter->format($price->getNumber(), $price->getCurrencyCode());
    }

    return t('N/A');
  }

}
