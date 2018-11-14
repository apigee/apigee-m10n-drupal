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

namespace Drupal\apigee_m10n_add_credit\EventSubscriber;

use Drupal\apigee_edge\Job\JobCreatorTrait;
use Drupal\apigee_edge\JobExecutor;
use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\apigee_m10n_add_credit\Job\BalanceAdjustmentJob;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A state transition subscriber for `commerce_order` entities.
 *
 * @see: <https://docs.drupalcommerce.org/commerce2/developer-guide/orders/react-to-workflow-transitions>
 */
class CommerceOrderTransitionSubscriber implements EventSubscriberInterface {
  use JobCreatorTrait;

  /**
   * The apigee edge SDK connector.
   *
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  protected $sdk_connector;

  /**
   * The apigee job executor.
   *
   * @var \Drupal\apigee_edge\JobExecutor
   */
  protected $jobExecutor;

  /**
   * CommerceOrderTransitionSubscriber constructor.
   *
   * @param \Drupal\apigee_edge\SDKConnectorInterface $sdk_connector
   *   The apigee edge SDK connector.
   * @param \Drupal\apigee_edge\JobExecutor $job_executor
   *   The apigee job executor.
   */
  public function __construct(SDKConnectorInterface $sdk_connector, JobExecutor $job_executor) {
    $this->sdk_connector = $sdk_connector;
    $this->jobExecutor = $job_executor;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.place.post_transition' => ['handleOrderStateChange', -100],
      'commerce_order.validate.post_transition' => ['handleOrderStateChange', -100],
      'commerce_order.fulfill.post_transition' => ['handleOrderStateChange', -100],
    ];
  }

  /**
   * Handles commerce order state change.
   *
   * Checks if the order is completed and checks for Apigee add credit products
   * that need to be applied to a developer.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   *
   * @throws \Exception
   */
  public function handleOrderStateChange(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    // Make sure this transition is to the `completed` state.
    if ($order->getState()->value === 'completed') {
      // Create a new price for the tally.
      /** @var \Drupal\commerce_price\Price[] $add_credit_totals */
      $add_credit_totals = [];
      // We need to loop through all of the products.
      foreach ($order->getItems() as $order_item) {
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variant */
        $variant = $order_item->getPurchasedEntity();
        // Get the product from the line item variant.
        $product = $variant instanceof ProductVariationInterface ? $variant->getProduct() : FALSE;
        // Check to see if this is an add credit product.
        if ($product && !empty($product->apigee_add_credit_enabled->value)) {
          // Since this is an add credit product, we add the line item total to
          // the tally.
          $recipient_id = $order_item->getData('add_credit_account');
          $add_credit_totals[$recipient_id] = !empty($add_credit_totals[$recipient_id])
            ? $add_credit_totals[$recipient_id]->add($order_item->getTotalPrice())
            : $order_item->getTotalPrice();
        }
      }
      // Checks to see if there are any add credit totals that need to be
      // applied to a developer's account balance.
      foreach ($add_credit_totals as $account_id => $add_credit_total) {
        if (!empty((double) $add_credit_total->getNumber())) {
          // Load the user this add credit item is for. TODO: Handle teams here.
          $user = user_load_by_mail($account_id);
          // Create a new job update the account balance. Use a custom
          // adjustment type because it can support a credit or a debit.
          $job = new BalanceAdjustmentJob($user, new Adjustment([
            'type' => 'apigee_balance',
            'label' => 'Apigee balance adjustment',
            'amount' => $add_credit_total,
          ]));
          // Save and execute the job.
          $this->getExecutor()->call($job);
        }
      }
    }
  }

}
