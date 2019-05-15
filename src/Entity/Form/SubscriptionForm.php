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

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\apigee_edge\Entity\Developer;
use Drupal\Core\Url;

/**
 * Subscription entity form.
 */
class SubscriptionForm extends FieldableMonetizationEntityForm {

  /**
   * Developer legal name attribute name.
   */
  const LEGAL_NAME_ATTR = 'MINT_DEVELOPER_LEGAL_NAME';

  /**
   * Messanger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a SubscriptionEditForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   */
  public function __construct(MessengerInterface $messenger = NULL) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
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
    return parent::getFormId() . '_' . $this->entity->getRatePlan()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Redirect to Rate Plan detail page on submit.
    $form['#action'] = $this->getEntity()->getRatePlan()->url('subscribe');
    return $this->conflictForm($form, $form_state);
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
      if ($items = $form_state->get('planConflicts')) {
        $parameters = \Drupal::routeMatch()->getParameters()->all();
        $actions['cancel'] = [
          '#title' => $this->t('Cancel'),
          '#type'  => 'link',
          '#url'   => Url::fromRoute('apigee_monetization.packages', ['user' => $parameters['user']->id()]),
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
      $developer_id = $this->entity->getDeveloper()->getEmail();
      $developer = Developer::load($developer_id);
      // Autopopulate legal name when developer has no legal name attribute set.
      if (empty($developer->getAttributeValue(static::LEGAL_NAME_ATTR))) {
        $developer->setAttribute(static::LEGAL_NAME_ATTR, $developer_id);
        $developer->save();
      }

      $display_name = $this->entity->getRatePlan()->getDisplayName();
      Cache::invalidateTags(['apigee_my_subscriptions']);

      if ($this->entity->save()) {
        $this->messenger->addStatus($this->t('You have purchased %label plan', [
          '%label' => $display_name,
        ]));
        $form_state->setRedirect('entity.subscription.developer_collection', ['user' => $this->entity->getOwnerId()]);
      }
      else {
        $this->messenger->addWarning($this->t('Unable to purchase %label plan', [
          '%label' => $display_name,
        ]));
      }
    }
    catch (\Exception $e) {
      $this->messenger->addError($e->getMessage());
      $messages = $this->messenger->all();
      $this->messenger->deleteAll();
      if (!empty($messages['error'][0])) {
        $overlap_error = $messages['error'][0];
        // Make sure this is overlapping error message.
        if (strstr($overlap_error, 'has following overlapping')) {
          $form_state->set('planConflicts', $this->getOverlappingProducts($overlap_error));
          // $form_state->getValue('startDate')
          $form_state->setRebuild(TRUE);
        }
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
        '#theme' =>'conflicting_products',
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

    $package = $this->getEntity()->getRatePlan()->getPackage();

    // Process products in attempted purchased plan.
    $products = [];
    foreach ($package->getApiProducts() as $product) {
      $products[$product->id()] = $product;
    }

    $plan_items = [];
    foreach ($overlaps as $plan_id => $overlapping_products) {
      list($id, $name) = explode('|', $plan_id);
      $plan_item = [
        'data' => $name,
        'children' => [],
      ];
      $additional = array_diff_key($products, $overlapping_products);
      $excluded = array_diff_key($overlapping_products, $products);
      $conflicting = array_intersect_key($products, $overlapping_products);
      $overlapping = [
        'Additional products' => $additional,
        'Excluded products' => $excluded,
        'Conflicting products' => $conflicting,
      ];
      foreach ($overlapping as $situation => $situation_products) {
        if (!empty($situation_products)) {
          $product_items = [
            'data' => $this->t($situation),
            'children' => [],
          ];
          foreach ($situation_products as $situation_product) {
            if (!in_array($situation_product->getDisplayName(), $product_items['children'])) {
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
