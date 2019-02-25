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

namespace Drupal\apigee_m10n_add_credit\Form;

use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\apigee_m10n_add_credit\AddCreditConfig;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ApigeeAddCreditProductsConfigForm.
 */
class ApigeeAddCreditProductsConfigForm extends ConfigFormBase {

  /**
   * The monetization service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * The commerce_product entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * ApigeeAddCreditProductsConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The monetization service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The commerce_product entity storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MonetizationInterface $monetization, EntityStorageInterface $storage) {
    parent::__construct($config_factory);
    $this->monetization = $monetization;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('apigee_m10n.monetization'),
      $container->get('entity_type.manager')->getStorage('commerce_product')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [AddCreditConfig::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'apigee_add_credit_products_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->getProductsConfig();
    $destination = $this->getDestinationArray();

    $form['info'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('For each currency, create an Apigee add credit product and set it here. This product will be used to top up prepaid balances.'),
    ];

    $form['products'] = [
      '#type' => 'table',
      '#title' => $this->t('Products'),
      '#header' => [
        $this->t('Name'),
        $this->t('Currency'),
        $this->t('Product'),
        $this->t('Operations'),
      ],
    ];

    // Get a list of supported currencies.
    /** @var \Apigee\Edge\Api\Monetization\Entity\SupportedCurrencyInterface[] $supported_currencies */
    $supported_currencies = $this->monetization->getSupportedCurrencies();

    // Display an error message if no currencies.
    if (!count($supported_currencies)) {
      $form['products']['#empty'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            $this->t('No supported currencies found for your Apigee Edge organization.'),
          ],
        ],
      ];
      return parent::buildForm($form, $form_state);
    }

    // Build row for each currency.
    foreach ($supported_currencies as $currency) {
      $currency_code = strtoupper($currency->getId());
      /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
      $product = $config[$currency->id()] ?? NULL;

      $form['products'][$currency->id()] = [
        'name' => ['#markup' => $currency->getDisplayName()],
        'currency' => ['#markup' => $currency_code],
        'product_id' => [
          '#type' => 'entity_autocomplete',
          '#target_type' => 'commerce_product',
          '#selection_handler' => 'apigee_m10n_add_credit:products',
          '#maxlength' => NULL,
          '#default_value' => $product,
          '#placeholder' => $this->t('Select a product for the @code currency', [
            '@code' => $currency_code,
          ]),
        ],
      ];

      // Add operations to edit the product if set.
      $form['products'][$currency->id()]['operations'] = $product ? [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit product'),
            'url' => $product->toUrl('edit-form', ['query' => $destination]),
          ],
        ],
      ] : [
        '#markup' => '',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config(AddCreditConfig::CONFIG_NAME)
      ->set('products', $form_state->getValue('products'))
      ->save();
  }

  /**
   * Helper to get the products from config.
   *
   * @return array
   *   An array of product key
   */
  protected function getProductsConfig() {
    if (!($config = $this->config(AddCreditConfig::CONFIG_NAME)->get('products'))) {
      return [];
    }

    // Collect product ids and load them in one call.
    $ids = array_column($config, 'product_id');
    $products = $this->storage->loadMultiple(array_filter($ids));

    foreach ($config as $currency_code => $currency_config) {
      $config[$currency_code] = $products[$currency_config['product_id']] ?? NULL;
    }

    return $config;
  }

}
