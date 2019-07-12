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

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Link;
use Drupal\apigee_m10n\Monetization;

/**
 * Plugin implementation of the 'apigee_purchase_plan_link' formatter.
 *
 * @FieldFormatter(
 *   id = "apigee_purchase_plan_link",
 *   label = @Translation("Link to form"),
 *   field_types = {
 *     "apigee_purchase"
 *   }
 * )
 */
class PurchasePlanLinkFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The Cache backend.
   *
   * @var \Drupal\apigee_m10n\Monetization
   */
  private $monetization;

  /**
   * Creates an instance of the plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Entity form builder service.
   * @param \Drupal\apigee_m10n\Monetization $monetization
   *   Monetization service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, Monetization $monetization) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->monetization = $monetization;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('apigee_m10n.monetization')
    );
  }

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
      '#title' => $this->t('Purchase label'),
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
      if ($this->monetization->isDeveloperAlreadySubscribed($value['user']->getEmail(), $rate_plan)) {
        return ['#markup' => $this->t('Already purchased %rate_plan', ['%rate_plan' => $rate_plan->getDisplayName()])];
      }

      return Link::createFromRoute(
        $this->getSetting('label'), 'entity.rate_plan.purchase', [
          'user'           => $value['user']->id(),
          'product_bundle' => $rate_plan->getProductBundleId(),
          'rate_plan'      => $rate_plan->id(),
        ]
      )->toRenderable();
    }
  }

}
