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

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_order\Plugin\Field\FieldWidget\UnitPriceWidget;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'unit_price_range_default' widget.
 *
 * @FieldWidget(
 *   id = "unit_price_range_default",
 *   label = @Translation("Unit price (range)"),
 *   field_types = {
 *     "commerce_price",
 *   }
 * )
 */
class UnitPriceRangeDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [];

    $element['amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Custom amount'),
      '#available_currencies' => array_filter($this->getFieldSetting('available_currencies')),
      '#element_validate' => [[get_class($this), 'validate']],
    ];

    $orderItem = $items->getEntity();
    $purchasable_entity = $orderItem->getPurchasedEntity();
    $form_state->set('product', $purchasable_entity->getProduct());

    return $element;
  }

  /**
   * Validates the amount against the price range.
   *
   * @param array $element
   *   The element array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validate(&$element, FormStateInterface $form_state) {
    $amount = static::getAmount($form_state);

    if ($amount['number']) {
      $product = $form_state->get('product');
      $parents = ['purchased_entity', 0, 'variation'];
      if ($variation_id = NestedArray::getValue($form_state->getValues(), $parents)) {
        $selected_variations = array_filter($product->getVariations(), function (ProductVariationInterface $variation) use ($variation_id) {
          return $variation->id() == $variation_id;
        });
        $selected_variation = reset($selected_variations);

        // Get the top up price range.
        if (($selected_variation->hasField('apigee_price_range')) && ($price_range = $selected_variation->get('apigee_price_range'))) {
          if ($amount['number'] < $price_range->minimum || $amount['number'] > $price_range->maximum || $amount['currency_code'] !== $price_range->currency_code) {
            $form_state->setError($element, t('The custom amount must be between @minimum and @maximum.', [
              '@minimum' => \Drupal::service('commerce_price.currency_formatter')->format($price_range->minimum, $price_range->currency_code),
              '@maximum' => \Drupal::service('commerce_price.currency_formatter')->format($price_range->maximum, $price_range->currency_code),
            ]));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $amount = static::getAmount($form_state);
    if ($amount['number']) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $items[0]->getEntity();
      $unit_price = Price::fromArray($amount);
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
   * Helper to get amount from form_state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The amount entered.
   */
  protected static function getAmount(FormStateInterface $form_state) {
    return NestedArray::getValue($form_state->getValues(), [
      'unit_price',
      0,
      'amount',
    ]);
  }

}
