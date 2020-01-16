<?php

/*
 * Copyright 2020 Google Inc.
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

namespace Drupal\apigee_m10n_add_credit\Plugin\Requirement\Requirement;

use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\requirement\Plugin\RequirementBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check that the "Add credit" product has been configured.
 *
 * @Requirement(
 *   id="order_item_add_to_cart_form_display",
 *   group="apigee_m10n_add_credit",
 *   label="Add to Cart form display",
 *   description="Enables the required fields on the add to cart form display.",
 *   severity="error",
 *   action_button_label="Update form display",
 *   dependencies={
 *      "apigee_edge_connection",
 *      "commerce_store",
 *      "add_credit_product_type",
 *   }
 * )
 */
class OrderItemAddToCartFormDisplay extends RequirementBase implements ContainerFactoryPluginInterface {

  /**
   * The entity form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $entityFormDisplay;

  /**
   * OrderItemAddToCartFormDisplay constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display
   *   The entity form display.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFormDisplayInterface $entity_form_display) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFormDisplay = $entity_form_display;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_display.repository')->getFormDisplay('commerce_order_item', 'add_credit', 'add_to_cart')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('The required fields for the Add to cart display will be enabled for the Add credit order item type.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Enable the required fields.
    foreach ($this->getRequiredFields() as $required_field => $type) {
      $this->entityFormDisplay->setComponent($required_field, [
        'region' => 'content',
        'type' => $type,
      ])->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(): bool {
    return $this->getModuleHandler()->moduleExists('apigee_m10n_add_credit');
  }

  /**
   * {@inheritdoc}
   */
  public function isCompleted(): bool {
    return !array_diff_key($this->getRequiredFields(), $this->entityFormDisplay->getComponents());
  }

  /**
   * Returns an array of required fields.
   *
   * @return array
   *   An array of required fields with field name as key and widget type as value.
   */
  protected function getRequiredFields(): array {
    return [
      AddCreditConfig::TARGET_FIELD_NAME => 'add_credit_target_entity',
      'unit_price' => 'commerce_unit_price',
    ];
  }

}
