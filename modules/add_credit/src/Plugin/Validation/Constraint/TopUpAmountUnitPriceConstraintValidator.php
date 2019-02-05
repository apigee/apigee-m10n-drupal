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

namespace Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\apigee_m10n_add_credit\AddCreditProductEntityManagerInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Plugin\Field\FieldType\PriceItem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the TopUpAmountUnitPrice constraint.
 */
class TopUpAmountUnitPriceConstraintValidator extends TopUpAmountNumberOutOfRangeConstraintValidator {

  /**
   * The add credit product entity manager.
   *
   * @var \Drupal\apigee_m10n_add_credit\AddCreditProductEntityManagerInterface
   */
  protected $productEntityManager;

  /**
   * TopUpAmountUnitPriceConstraintValidator constructor.
   *
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter service.
   * @param \Drupal\apigee_m10n_add_credit\AddCreditProductEntityManagerInterface $add_credit_product_entity_manager
   *   The add credit product entity manager.
   */
  public function __construct(CurrencyFormatterInterface $currency_formatter, AddCreditProductEntityManagerInterface $add_credit_product_entity_manager) {
    parent::__construct($currency_formatter);
    $this->productEntityManager = $add_credit_product_entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_price.currency_formatter'),
      $container->get('apigee_m10n_add_credit.product_entity_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateInstance($value): void {
    if (!($value instanceof PriceItem)) {
      throw new UnexpectedTypeException($value, PriceItem::class);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRange($value): array {
    // This finds the apigee_top_up_amount field from the purchased entity.
    // Skipped if no valid field of type apigee_top_up_amount is found.
    if (($purchased_entity = $this->getPurchasedEntity($value)) && ($top_up_field = $this->productEntityManager->getApigeeTopUpAmountField($purchased_entity))) {
      return $top_up_field->toRange();
    }

    return [];
  }

  /**
   * Returns the purchased entity.
   *
   * @param mixed $value
   *   The value instance.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface|null
   *   The purchasable entity.
   */
  protected function getPurchasedEntity($value) {
    $order = $value->getParent()->getEntity();
    if ($order instanceof OrderItemInterface) {
      return $order->getPurchasedEntity();
    }

    return NULL;
  }

}
