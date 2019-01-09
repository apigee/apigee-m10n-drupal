<?php

/**
 * @file
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

namespace Drupal\apigee_m10n_add_credit\Plugin\Field\FieldWidget;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'custom_amount' widget.
 *
 * @FieldWidget(
 *   id = "apigee_m10n_add_credit_custom_amount",
 *   label = @Translation("Custom amount"),
 *   field_types = {
 *     "commerce_price",
 *   }
 * )
 */
class CustomAmountWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [];

    /** @var \Drupal\commerce_order\Entity\OrderItem $orderItem */
    $orderItem = $items->getEntity();

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchasable_entity */
    $purchasable_entity = $orderItem->getPurchasedEntity();
    $product = $purchasable_entity->getProduct();

    // No changes if custom amount is not enabled.
    if ($product->hasField('apigee_add_credit_custom_amount') && $product->get('apigee_add_credit_custom_amount')->value == 0) {
      return $element;
    }

    $element['amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Enter credit amount'),
      '#element_validate' => [
        [get_class($this), 'validatePrice'],
      ],
    ];

    // Set the minimum amount.
    if ($miminum_amount = $this->getMinimumAmountForProduct($product)) {
      $element['amount']['#default_value'] = [
        'number' => $miminum_amount->getNumber(),
        'currency_code' => $miminum_amount->getCurrencyCode(),
      ];
    }

    return $element;
  }

  /**
   * Validates the custom amount.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validatePrice(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $form_state->get('product');

    // Validates if the amount is greater than or equal to the minimum amount.
    if ($minimum_amount = static::getMinimumAmountForProduct($product)) {
      $value = $form_state->getValue($element['#parents']);
      if ($value['number'] < $minimum_amount->getNumber()) {
        $form_state->setError($element, t('The minimum credit amount is @amount.', [
          '@amount' => \Drupal::service('commerce_price.currency_formatter')->format($minimum_amount->getNumber(), $minimum_amount->getCurrencyCode(), [
            'maximum_fraction_digits' => 2,
          ]),
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $path = array_merge($form['#parents'], [$field_name, 0]);
    $values = NestedArray::getValue($form_state->getValues(), $path);
    if ($values && $values['amount']['number']) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $items[0]->getEntity();
      $unit_price = new Price($values['amount']['number'], $values['amount']['currency_code']);
      $order_item->setUnitPrice($unit_price, TRUE);
      // Put delta mapping in $form_state, so that flagErrors() can use it.
      $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = $delta;
      }
      static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_order_item' && $field_name == 'unit_price';
  }

  /**
   * Helper to find the minimum amount for a product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product entity.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The minimum amount.
   */
  protected static function getMinimumAmountForProduct(ProductInterface $product) {
    // Set the minimum amount.
    if (($product->hasField('apigee_add_credit_minimum_amount')) && ($number = $product->get('apigee_add_credit_minimum_amount')->number) && ($currency_code = $product->get('apigee_add_credit_minimum_amount')->currency_code)) {
      return new Price($number, $currency_code);
    }

    return NULL;
  }

}
