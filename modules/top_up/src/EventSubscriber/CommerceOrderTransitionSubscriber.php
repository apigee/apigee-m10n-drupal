<?php
/**
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

namespace Drupal\apigee_m10n_top_up\EventSubscriber;

use Drupal\apigee_edge\Job\JobCreatorTrait;
use Drupal\apigee_edge\JobExecutor;
use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\apigee_m10n_top_up\Job\BalanceAdjustmentJob;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A state transition subscriber for `commerce_order` entities. See:
 * <https://docs.drupalcommerce.org/commerce2/developer-guide/orders/react-to-workflow-transitions>
 *
 * @package Drupal\apigee_m10n_top_up\EventSubscriber
 */
class CommerceOrderTransitionSubscriber implements EventSubscriberInterface {
  use JobCreatorTrait;

  /**
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
   * @param \Drupal\apigee_edge\JobExecutor $job_executor
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
   * Checks if the order is completed and checks for Apigee Top Up products that
   * need to be applied to a developer.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *  The transition event.
   *
   * @throws \Exception
   */
  public function handleOrderStateChange(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    // Make sure this transition is to the `completed` state.
    if ($order->getState()->value === 'completed') {
      // Create a new price for the tally.
      /** @var Price[] $top_up_totals */
      $top_up_totals = [];
      // We need to loop through all of the products
      foreach ($order->getItems() as $order_item) {
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $varient */
        $variant = $order_item->getPurchasedEntity();
        // Get the product from the line item variant.
        $product = $variant instanceof ProductVariationInterface ? $variant->getProduct() : FALSE;
        // Check to see if this is a top up product.
        if ($product && !empty($product->apigee_top_up_enabled->value)) {
          // Since this is a top up product, we add the line item total to the
          // tally.
          $recipient_id = $order_item->getData('top_up_account');
          $top_up_totals[$recipient_id] = !empty($top_up_totals[$recipient_id])
            ? $top_up_totals[$recipient_id]->add($order_item->getTotalPrice())
            : $order_item->getTotalPrice();
        }
      }
      // Checks to see if there are any top up totals that need to be applied to
      // a developer's account balance.
      foreach ($top_up_totals as $account_id => $top_up_total) {
        if (!empty((double) $top_up_total->getNumber())) {
          // Load the user this top up item is for. TODO: Handle companies here.
          $user = user_load_by_mail($account_id);
          // Create a new job update the account balance. Use a custom adjustment
          // type because it can support a credit or a debit.
          $job = new BalanceAdjustmentJob($user, new Adjustment([
            'type' => 'top_up',
            'label' => 'Top Up Adjustment',
            'amount' => $top_up_total,
          ]));
          // Save and execute the job.
          $this->getExecutor()->call($job);
        }
      }
    }
  }
}
