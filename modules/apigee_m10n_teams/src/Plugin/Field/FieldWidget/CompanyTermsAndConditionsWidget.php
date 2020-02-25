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

namespace Drupal\apigee_m10n_teams\Plugin\Field\FieldWidget;

use Apigee\Edge\Api\Monetization\Entity\CompanyAcceptedRatePlan;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\apigee_m10n\Plugin\Field\FieldWidget\TermsAndConditionsWidget;
use Drupal\apigee_m10n_teams\MonetizationTeamsInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Override class for the `apigee_tnc_widget` field widget.
 */
class CompanyTermsAndConditionsWidget extends TermsAndConditionsWidget implements ContainerFactoryPluginInterface {

  /**
   * Teams Monetization factory.
   *
   * @var \Drupal\apigee_m10n_teams\MonetizationTeamsInterface
   */
  protected $team_monetization;

  /**
   * CompanyTermsAndConditionsWidget constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   Monetization factory.
   * @param \Drupal\apigee_m10n_teams\MonetizationTeamsInterface $team_monetization
   *   Teams monetization factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, LoggerInterface $logger, MonetizationInterface $monetization, MonetizationTeamsInterface $team_monetization) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $logger, $monetization);
    $this->team_monetization = $team_monetization;
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
      $configuration['third_party_settings'],
      $container->get('logger.factory')->get('apigee_m10n'),
      $container->get('apigee_m10n.monetization'),
      $container->get('apigee_m10n.teams')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($element, FormStateInterface $form_state) {
    if ($this->entity->decorated() instanceof CompanyAcceptedRatePlan) {
      $value = $element['#value'];
      // We only apply checking when terms and conditions checkbox is present in the form.
      if (empty($value)) {
        $form_state->setError($element, $this->t('Terms and conditions acceptance is required.'));
      }
      else {
        // Accept terms and conditions.
        $this->team_monetization->acceptLatestTermsAndConditions($this->entity->decorated()->getCompany()->id());
      }
    }
    else {
      parent::validate($element, $form_state);
    }
  }

}
