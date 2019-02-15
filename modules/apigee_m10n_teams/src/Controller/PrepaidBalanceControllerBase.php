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

namespace Drupal\apigee_m10n_teams\Controller;

use Apigee\Edge\Api\Monetization\Entity\PrepaidBalanceInterface;
use Drupal\apigee_m10n\Controller\PrepaidBalanceController;
use Drupal\apigee_m10n\Form\PrepaidBalanceRefreshForm;
use Drupal\apigee_m10n_teams\TeamSdkControllerFactoryAwareTrait;

/**
 * Prepaid balance controller base.
 *
 * This is modeled after an entity list builder with some additions.
 * See: `\Drupal\Core\Entity\EntityListBuilder`
 */
abstract class PrepaidBalanceControllerBase extends PrepaidBalanceController implements PrepaidBalanceControllerInterface {

  use TeamSdkControllerFactoryAwareTrait;

  /**
   * The entity for this report.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['apigee-m10n-prepaid-balance-wrapper'],
      ],
      '#attached' => [
        'library' => [
          'apigee_m10n/prepaid_balance',
        ],
      ],
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There are no balances available for this @label.', ['@label' => strtolower($this->entity->getEntityType()->getLabel())]),
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => $this->getCacheTags($this->entity),
        'max-age' => $max_age = $this->getCacheMaxAge(),
        'keys' => [static::getCacheId($this->entity, 'prepaid_balances')],
      ],
    ];
    foreach ($this->load() as $currency) {
      if ($row = $this->buildRow($currency)) {
        $build['table']['#rows'][$currency->id()] = $row;
      }
    }

    // Add a refresh cache form.
    // TODO: Handle access control for teams.
    if (TRUE) {
      $build['refresh_form'] = $this->formBuilder()->getForm(PrepaidBalanceRefreshForm::class, $this->getCacheTags($this->entity));
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('Current prepaid balance');
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'currency' => $this->t('Account Currency'),
      'previous_balance' => $this->t('Previous Balance'),
      'credit' => $this->t('Credit'),
      'usage' => $this->t('Usage'),
      'tax' => $this->t('Tax'),
      'current_balance' => $this->t('Current Balance'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(PrepaidBalanceInterface $balance) {
    $currency_code = $balance->getCurrency()->getName();
    return [
      'currency' => $currency_code,
      'previous_balance' => $this->formatCurrency($balance->getPreviousBalance(), $currency_code),
      'credit' => $this->formatCurrency($balance->getTopUps(), $currency_code),
      'usage' => $this->formatCurrency($balance->getUsage(), $currency_code),
      'tax' => $this->formatCurrency($balance->getTax(), $currency_code),
      'current_balance' => $this->formatCurrency($balance->getCurrentBalance(), $currency_code),
    ];
  }

  /**
   * Format an amount using the `monetization` service.
   *
   * See: \Drupal\apigee_m10n\MonetizationInterface::formatCurrency().
   *
   * @param string $amount
   *   The money amount.
   * @param string $currency_code
   *   Currency code.
   *
   * @return string
   *   The formatted amount as a string.
   */
  protected function formatCurrency($amount, $currency_code) {
    return $this->monetization->formatCurrency($amount, $currency_code);
  }

  /**
   * Loads the balances for the listing.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\PrepaidBalanceInterface[]|array
   *   A list of apigee monetization prepaid balance entities.
   *
   * @throws \Exception
   */
  abstract public function load();

}
