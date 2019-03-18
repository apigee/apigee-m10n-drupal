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

use Drupal\apigee_m10n\Plugin\Field\FieldWidget\TermsAndConditionsWidget;
use Drupal\apigee_m10n_teams\Entity\TeamRouteAwareSubscriptionInterface;
use Drupal\apigee_m10n_teams\MonetizationTeamsInterface;
use Drupal\apigee_m10n\MonetizationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Override class for the `apigee_tnc_widget` field widget.
 */
class CompanyTermsAndConditionsWidget extends TermsAndConditionsWidget {

  /**
   * Company ID.
   *
   * @var string
   */
  protected $company_id;

  /**
   * Teams Monetization factory.
   *
   * @var \Drupal\apigee_m10n_teams\MonetizationTeamsInterface
   */
  protected $team_monetization;

  /**
   * Monetization factory.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * SubscribeLinkFormatter constructor.
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
   * @param \Drupal\apigee_m10n_teams\MonetizationTeamsInterface $team_monetization
   *   Teams monetization factory.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   Monetization factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, MonetizationTeamsInterface $team_monetization = NULL, MonetizationInterface $monetization = NULL) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->team_monetization = $team_monetization;
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
      $configuration['third_party_settings'],
      $container->get('apigee_m10n.teams'),
      $container->get('apigee_m10n.monetization')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    if ($entity instanceof TeamRouteAwareSubscriptionInterface && $entity->isTeamSubscription())  {
      // We won't ask a user to accept terms and conditions again if it has been already accepted.
      $this->company_id = $entity->decorated()->getCompany()->id();
      if (!$items[$delta]->value) {
        $tnc = $this->monetization->getLatestTermsAndConditions();
        $element += [
          '#type'             => 'checkbox',
          '#default_value'    => !empty($items[0]->value),
          '#element_validate' => [[$this, 'validate']],
        ];
        // Accept TnC description.
        $element['#description'] = $this->t('%description @link', [
          '%description' => ($description = $tnc->getDescription()) ? $this->t($description) : $this->getSetting('default_description'),
          '@link' => ($link = $tnc->getUrl()) ? Link::fromTextAndUrl($this->t('Terms and Conditions'), Url::fromUri($link))->toString() : '',
        ]);
        $element['#attached']['library'][] = 'apigee_m10n/tnc-widget';
        return ['value' => $element];
      }
    }
    else {
      return parent::formElement($items, $delta, $element, $form, $form_state);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function validate($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    // We only apply checking when terms and conditions checkbox is present in the form.
    if (empty($value)) {
      $form_state->setError($element, $this->t('Terms and conditions acceptance is required.'));
    }
    else {
      // Accept terms and conditions.
      $this->team_monetization->acceptLatestTermsAndConditions($this->company_id);
    }
  }

}
