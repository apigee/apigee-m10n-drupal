<?php

/*
 * Copyright 2019 Google Inc.
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

use Apigee\Edge\Api\Management\Controller\OrganizationController;
use Apigee\Edge\Api\Monetization\Controller\OrganizationProfileController;
use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\address\FieldHelper;
use Drupal\address\LabelHelper;
use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\requirement\Plugin\RequirementBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check if a commerce store exists.
 *
 * @Requirement(
 *   id = "commerce_store",
 *   group="apigee_m10n_add_credit",
 *   label = "Commerce store",
 *   description = "Setup a commerce store to handle prepaid balance checkouts.",
 *   action_button_label="Setup store",
 *   severity="error"
 * )
 */
class CommerceStore extends RequirementBase implements ContainerFactoryPluginInterface {

  /**
   * SDK connector service.
   *
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  protected $sdkConnector;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * The Apigee Organization.
   *
   * @var \Apigee\Edge\Api\Monetization\Entity\OrganizationProfileInterface
   */
  protected $organization;

  /**
   * CommerceStore constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\apigee_edge\SDKConnectorInterface $sdk_connector
   *   SDK connector service.
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface $subdivision_repository
   *   The subdivision repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SDKConnectorInterface $sdk_connector, SubdivisionRepositoryInterface $subdivision_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sdkConnector = $sdk_connector;
    $this->subdivisionRepository = $subdivision_repository;

    try {
      $organization_id = $this->sdkConnector->getOrganization();
      $client = $this->sdkConnector->getClient();

      // Load the organization.
      $organization_controller = new OrganizationController($client);
      $organization = $organization_controller->load($organization_id);
      /** @var \Apigee\Edge\Api\Management\Entity\OrganizationInterface $organization */
      if ($organization->getPropertyValue('features.isMonetizationEnabled') === 'true') {
        // Set the organization.
        $organization_profile_controller = new OrganizationProfileController($organization_id, $client);
        $this->organization = $organization_profile_controller->load($organization_id);
      }
    }
    catch (\Exception $exception) {
      watchdog_exception('apigee_m10n_add_credit', $exception);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('apigee_edge.sdk_connector'),
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['store'] = [
      '#markup' => $this->t('Create a store for handling prepaid balance top ups.'),
    ];

    $site_config = $this->getConfigFactory()->get('system.site');
    $form['name'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#placeholder' => $this->t('Name of store'),
      '#default_value' => "{$site_config->get('name')} store",
    ];

    $form['mail'] = [
      '#title' => $this->t('Email'),
      '#type' => 'email',
      '#placeholder' => $this->t('admin@example.com'),
      '#default_value' => $site_config->get('mail'),
      '#description' => $this->t('Store email notifications are sent from this address.'),
    ];

    $currencies = $this->getEntityTypeManager()->getStorage('commerce_currency')->loadMultiple();
    $currency_codes = array_keys($currencies);
    $form['default_currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Default currency'),
      '#options' => array_combine($currency_codes, $currency_codes),
    ];

    $form['type'] = [
      '#type' => 'value',
      '#value' => 'online',
    ];

    $form['address'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Address'),
    ];

    $form['address']['country_code'] = [
      '#title' => $this->t('Country'),
      '#type' => 'address_country',
      '#required' => TRUE,
      '#default_value' => $this->getConfigFactory()->get('system.date')->get('country.default'),
    ];

    $address_fields = [
      AddressField::ADDRESS_LINE1 => [
        'size' => 60,
        'placeholder' => 'Acme Street',
      ],
      AddressField::ADDRESS_LINE2 => [
        'size' => 60,
        'placeholder' => '',
      ],
      AddressField::LOCALITY => [
        'size' => 30,
        'placeholder' => 'Santa Clara',
      ],
      AddressField::ADMINISTRATIVE_AREA => [
        'size' => 30,
        'placeholder' => 'CA or California',
      ],
      AddressField::POSTAL_CODE => [
        'size' => 10,
        'placeholder' => '95050',
      ],
    ];
    $labels = LabelHelper::getGenericFieldLabels();
    foreach ($address_fields as $address_field => $settings) {
      $form['address'][FieldHelper::getPropertyName($address_field)] = [
        '#title' => $labels[$address_field],
        '#type' => 'textfield',
        '#size' => $settings['size'],
        '#placeholder' => $settings['placeholder'],
      ];
    }

    // Add default address from organization.
    if ($this->organization && $addresses = $this->organization->getAddresses()) {
      /** @var \Apigee\Edge\Api\Monetization\Structure\Address $address */
      $address = reset($addresses);

      $form['address']['country_code']['#default_value'] = $address->getCountry();
      $form['address'][FieldHelper::getPropertyName(AddressField::ADDRESS_LINE1)]['#default_value'] = $address->getAddress1();
      $form['address'][FieldHelper::getPropertyName(AddressField::ADDRESS_LINE1)]['#default_value'] = $address->getAddress1();
      $form['address'][FieldHelper::getPropertyName(AddressField::LOCALITY)]['#default_value'] = $address->getCity();
      $form['address'][FieldHelper::getPropertyName(AddressField::POSTAL_CODE)]['#default_value'] = $address->getZip();

      // Find the state code from the country subdivisions.
      if (($state = $address->getState())
        && ($subdivisions = $this->subdivisionRepository->getList([$address->getCountry()]))
        && (in_array($state, $subdivisions)) || (isset($subdivisions[$state]))) {
        $form['address'][FieldHelper::getPropertyName(AddressField::ADMINISTRATIVE_AREA)]['#default_value'] = $state;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    try {
      $values = $form_state->getValues();
      $store = $this->getEntityTypeManager()->getStorage('commerce_store')
        ->create($values);
      $store->save();
    }
    catch (\Exception $exception) {
      watchdog_exception('apigee_m10n_add_credit', $exception);
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
    return count($this->getEntityTypeManager()->getStorage('commerce_store')->loadMultiple());
  }

}
