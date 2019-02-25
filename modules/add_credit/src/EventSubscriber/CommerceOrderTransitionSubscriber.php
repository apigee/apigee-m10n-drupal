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
use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\apigee_m10n_add_credit\Job\BalanceAdjustmentJob;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
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

    // Do nothing if order is not completed.
    if ($order->getState()->value !== 'completed') {
      return;
    }

    // Create a new job update the account balance.
    $totals = $this->getCreditTotalsForOrder($order);

    foreach ($totals as ['target' => $target, 'amount' => $amount]) {
      if (!empty((double) $amount->getNumber())) {
        // Use a custom adjustment type because it can support a credit or a debit.
        $job = new BalanceAdjustmentJob($target, new Adjustment([
          'type' => 'apigee_balance',
          'label' => 'Apigee balance adjustment',
          'amount' => $amount,
        ]));

        // Save and execute the job.
        $this->getExecutor()->call($job);
      }
    }
  }

  /**
   * Builds an array of targets and their total amount for each orde item.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   *
   * @return array
   *   An array of targets and their total amount.
   */
  protected function getCreditTotalsForOrder(OrderInterface $order) {
    $total = [];
    foreach ($order->getItems() as $order_item) {
      if (($variant = $order_item->getPurchasedEntity())
        && ($product = $variant->getProduct())
        && !empty($product->apigee_add_credit_enabled->value)) {

        /** @var \Apigee\Edge\Entity\EntityInterface[] $targets */
        if (($order_item->hasField(AddCreditConfig::TARGET_FIELD_NAME))
          && ($targets = $order_item->get(AddCreditConfig::TARGET_FIELD_NAME)->referencedEntities())
        ) {
          foreach ($targets as $target) {
            // Add the target entity for the amount.
            if (empty($total[$target->id()])) {
              $total[$target->id()] = ['target' => $target];
            }

            // Add the line item total to the tally.
            $total[$target->id()]['amount'] = !empty($total[$target->id()]['amount'])
              ? $total[$target->id()]['amount']->add($order_item->getTotalPrice())
              : $order_item->getTotalPrice();
          }
        }
      }
    }

    return $total;
  }

}
