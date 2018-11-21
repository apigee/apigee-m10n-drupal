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

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;

/**
 * Unsubscribe entity form for subscriptions.
 */
class UnsubscribeConfirmForm extends EntityConfirmFormBase {

  protected $user;
  protected $subscription;

  /**
   * Class constructor.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->user = User::load($route_match->getParameter('user'));
    $this->subscription = $route_match->getParameter('subscription');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you would like to unsubscribe from this plan?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('End This Plan');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Unsubscribe from <em>%label</em> plan', [
      '%label' => $this->subscription->getRatePlan()->label()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {}

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['end_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plan End Date'),
      '#options' => [
        'immediate' => $this->t('Immediately'),
        'end_date'  => $this->t('Future Date')
      ],
      '#default_value' => 'immediate'
    ];
    $form['endDate'] = [
      '#type'  => 'date',
      '#title' => $this->t('Select an end date'),
      '#states' => [
        'visible' => [
          ':input[name="end_type"]' => array('value' => 'end_date'),
        ]
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $end_type = $values['end_type'];
    if ($end_type == 'end_date') {
      $this->entity->setEndDate(new \DateTimeImmutable($values['endDate']));
    }
    else {
      $this->entity->setEndDate(new \DateTimeImmutable('-1 day'));
    }
    $this->entity->setDeveloperEmail(\Drupal::currentUser()->getEmail());
    $this->entity->save();
  }

}
