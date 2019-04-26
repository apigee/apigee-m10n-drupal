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
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AddCreditConfigForm.
 */
class AddCreditConfigForm extends ConfigFormBase {

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
    return 'apigee_add_credit_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(AddCreditConfig::CONFIG_NAME);
    $products_config = $this->getProductsConfig();
    $destination = $this->getDestinationArray();

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General'),
    ];

    $form['general']['use_modal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use modal'),
      '#description' => $this->t('Display the add credit form in a modal.'),
      '#default_value' => $config->get('use_modal'),
    ];

    $form['products_container'] = [
      '#type' => 'fieldset',
      '#tree' => FALSE,
      '#title' => $this->t('Products'),
    ];

    $form['products_container']['help'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'container-inline'
        ]
      ],
      'text' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Configure products for adding credit to prepaid balances. Set a product for each currency below. When a user add credits to a prepaid balance, the configured product will be used for checkout.'),
      ],
      'action' => [
        '#type' => 'link',
        '#title' => $this->t('Add product'),
        '#url' => Url::fromRoute('entity.commerce_product.add_page'),
        '#attributes' => [
          'class' => [
            'button',
            'button-action',
            'button--small',
          ],
        ],
      ],
    ];

    $form['products_container']['products'] = [
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
      $form['products_container']['products']['#empty'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            $this->t('No supported currencies found for your Apigee Edge organization.'),
          ],
        ],
      ];
    }

    // Build row for each currency.
    foreach ($supported_currencies as $currency) {
      $currency_code = strtoupper($currency->getId());
      /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
      $product = $products_config[$currency->id()] ?? NULL;

      $form['products_container']['products'][$currency->id()] = [
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
      $form['products_container']['products'][$currency->id()]['operations'] = $product ? [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit product'),
            'url' => $product->toUrl('edit-form', ['query' => $destination]),
          ],
        ],
        '#attributes' => [
          'class' => [
            'edit',
            'edit--' . $currency->id(),
          ],
        ],
      ] : [
        '#markup' => '',
      ];
    }

    // Use the site default if an email hasn't been saved.
    $default_email = $config->get('notification_recipient');
    $default_email = $default_email ?: $this->configFactory()
      ->get('system.site')
      ->get('mail');

    $form['notifications'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notifications'),
    ];
    // Whether or not to sent an email if there is an error adding credit.
    $description = 'Notification sent when an %add_credit product is processed 
                    to add credit to a developer or team account. If an error 
                    occurs while applying the credit, a notification is sent to
                    the specified email address. Select %add_credit to send a
                    notification even if the credit is applied successfully.';

    $form['notifications']['notify_on'] = [
      '#type' => 'radios',
      '#title' => $this->t('Notify administrator'),
      '#options' => [
        AddCreditConfig::NOTIFY_ALWAYS => $this->t('Always'),
        AddCreditConfig::NOTIFY_ON_ERROR => $this->t('Only on error'),
      ],
      '#description' => $this->t($description, [
        '%add_credit' => 'Add credit',
        '%always_option' => 'Always',
      ]),
      '#default_value' => $config->get('notify_on'),
    ];
    // Allow an email address to be set for the error report.
    $form['notifications']['notification_recipient'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t('The email recipient of %add_credit notifications.', ['%add_credit' => 'Add credit']),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $default_email,
      '#required' => TRUE,
    ];
    // Add a note about configuring notifications in drupal commerce.
    $form['notifications']['note'] = [
      '#markup' => $this->t('<div class="apigee-add-credit-notification-note"><div class="label">@note</div><div>@description<br />@see @commerce_notification_link.</div></div>', [
        '@note' => 'Note:',
        '@description' => 'You can configure Drupal Commerce to send an email to the consumer to confirm completion of the order.',
        '@see' => 'See',
        '@commerce_notification_link' => Link::fromTextAndUrl('Drupal commerce documentation', Url::fromUri('https://docs.drupalcommerce.org/commerce2/user-guide/orders/customer-emails', ['external' => TRUE]))
          ->toString(),
      ]),
    ];

    $form['#attached']['library'][] = 'apigee_m10n_add_credit/settings';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config(AddCreditConfig::CONFIG_NAME)
      ->set('products', $form_state->getValue('products'))
      ->set('use_modal', $form_state->getValue('use_modal'))
      ->set('notify_on', $form_state->getValue('notify_on'))
      ->set('notification_recipient', $form_state->getValue('notification_recipient'))
      ->save();
  }

  /**
   * Helper to get the products from config.
   *
   * @return array
   *   An array of product key
   */
  protected function getProductsConfig() {
    if (!($config = $this->config(AddCreditConfig::CONFIG_NAME)
      ->get('products'))) {
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
