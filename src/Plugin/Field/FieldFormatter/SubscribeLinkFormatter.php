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

namespace Drupal\apigee_m10n\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\apigee_m10n\Entity\Subscription;
use Drupal\Core\Link;
use Drupal\apigee_m10n\Form\SubscriptionConfigForm;

/**
 * Plugin implementation of the 'apigee_subscription_form' formatter.
 *
 * @FieldFormatter(
 *   id = "apigee_subscribe_link",
 *   label = @Translation("Link to form"),
 *   field_types = {
 *     "apigee_subscribe"
 *   }
 * )
 */
class SubscribeLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'label' => 'Purchase plan',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subscribe label'),
      '#default_value' => $this->getSetting('label'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }
    return $elements;
  }

  /**
   * Renderable link element.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   Field item variable.
   *
   * @return array
   *   Renderable link element.
   */
  protected function viewValue(FieldItemInterface $item) {
    /** @var \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan */
    $rate_plan = $item->getEntity();
    if ($value = $item->getValue()) {
      if ($subscriptions = Subscription::loadByDeveloperId($value['user']->getEmail())) {
        foreach ($subscriptions as $subscription) {
          if ($subscription->getRatePlan()->id() == $rate_plan->id() && $subscription->isSubscriptionActive()) {
            $label = \Drupal::config(SubscriptionConfigForm::CONFIG_NAME)->get('already_purchased_label');
            return [
              '#markup' => $this->t($label ?? 'Already purchased %rate_plan', [
                '%rate_plan' => $rate_plan->getDisplayName()
              ])
            ];
          }
        }
      }
      return Link::createFromRoute(
        $this->getSetting('label'), 'entity.rate_plan.subscribe', [
          'user'      => $value['user']->id(),
          'package'   => $rate_plan->getPackage()->id(),
          'rate_plan' => $rate_plan->id(),
        ]
      )->toRenderable();
    }
  }

}
