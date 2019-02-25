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

use Drupal\apigee_m10n_add_credit\Form\ApigeeAddCreditAddToCartForm;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

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
   * Constructor for the `apigee_m10n.add_credit` service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $user) {
    $this->config = $config_factory;
    $this->current_user = $user;
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
        $fields['apigee_add_credit_enabled'] = BaseFieldDefinition::create('boolean')
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
      $add_credit_base_def = clone $base_field_definitions['apigee_add_credit_enabled'];
      $add_credit_base_def
        ->setDefaultValue(TRUE)
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('form', ['weight' => 25])
        ->setDisplayConfigurable('view', TRUE);
      return ['apigee_add_credit_enabled' => $add_credit_base_def];
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
    $entity_types['commerce_order_item']->setFormClass('add_to_cart', ApigeeAddCreditAddToCartForm::class);
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
  public function apigeeM10nPrepaidBalancePageAlter(array &$build, EntityInterface $entity) {
    if ((count($build['table']['#rows'])) && ($entity instanceof UserInterface) && ($this->current_user->hasPermission('add credit to any developer prepaid balance') ||
        ($this->current_user->hasPermission('add credit to own developer prepaid balance') && $this->current_user->id() === $entity->id()))) {
      $build['table']['#header']['operations'] = t('Operations');
      $destination = \Drupal::destination()->getAsArray();
      $config = \Drupal::configFactory()->get('apigee_m10n_add_credit.config');

      foreach ($build['table']['#rows'] as $currency_id => $row) {
        $url = Url::fromRoute('apigee_m10n_add_credit.add_credit', [
          'user' => $entity->id(),
          'currency_id' => $currency_id,
        ], [
          'query' => $destination
        ]);

        // If the currency has a configured product, add a link to add credit to this balance.
        $build['table']['#rows'][$currency_id]['data']['operations']['data'] = $config->get("products.$currency_id.product_id") ? [
          '#type' => 'operations',
          '#links' => [
            'add_credit' => [
              'title' => t('Add credit'),
              'url' => $url,
              'attributes' => [
                'class' => [
                  'use-ajax',
                  'button',
                ],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => json_encode([
                  'width' => 500,
                  'height' => 500,
                  'draggable' => FALSE,
                  'autoResize' => FALSE,
                ]),
              ],
            ],
          ],
          '#attached' => [
            'library' => [
              'core/drupal.dialog.ajax',
              'core/jquery.ui.dialog',
            ],
          ],
        ] : ['#markup' => ''];
      }
    }

    // Add cache contexts.
    $build['table']['#cache']['contexts'][] = 'user.permissions';
    $build['table']['#cache']['tags'][] = 'config:' . AddCreditConfig::CONFIG_NAME;
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
