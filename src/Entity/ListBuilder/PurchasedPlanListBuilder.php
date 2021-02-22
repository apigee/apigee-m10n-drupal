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

namespace Drupal\apigee_m10n\Entity\ListBuilder;

use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\apigee_m10n\Entity\Form\PurchasedPlanForm;
use Drupal\apigee_m10n\Entity\PurchasedPlanInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Utility\Html;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines implementation of a purchased plan listing page.
 *
 * @ingroup entity_api
 */
abstract class PurchasedPlanListBuilder extends EntityListBuilder implements ContainerInjectionInterface {

  /**
   * Purchased plan storage.
   *
   * @var \Drupal\apigee_m10n\Entity\Storage\PurchasedPlanStorageInterface
   */
  protected $storage;

  /**
   * Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The monetization service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * PurchasedPlanListBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity type service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Entity storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The monetization service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, MessengerInterface $messenger, MonetizationInterface $monetization) {
    parent::__construct($entity_type, $storage);

    $this->storage = $storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->monetization = $monetization;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type,
      $entity_type_manager->getStorage($entity_type->id()),
      $entity_type_manager,
      $container->get('logger.channel.apigee_m10n'),
      $container->get('messenger'),
      $container->get('apigee_m10n.monetization')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entityType = $container->get('entity_type.manager')->getDefinition('purchased_plan');
    return static::createInstance($container, $entityType);
  }

  /**
   * {@inheritdoc}
   */
  public function getPageTitle(RouteMatchInterface $route_match): string {
    // TODO: make sure this string is configurable.
    return $this->t('Purchased plans');
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('@label', [
      '@label' => (string) $this->entityType->getPluralLabel(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'plan' => [
        'data' => $this->t('Rate plan'),
        'class' => ['purchased-rate-plan'],
        'field' => 'plan',
      ],
      'status' => [
        'data' => $this->t('Status'),
        'field' => 'status',
        'class' => ['purchased-plan-status'],
      ],
      'start_date' => [
        'data' => $this->t('Start Date'),
        'class' => ['purchased-plan-start-date'],
        'field' => 'start_date',
      ],
      'end_date' => [
        'data' => $this->t('End Date'),
        'class' => ['purchased-plan-end-date'],
        'field' => 'end_date',
      ],
      'operations' => [
        'data' => $this->t('Actions'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\apigee_m10n\Entity\PurchasedPlanInterface $entity */
    $rate_plan = $entity->getRatePlan();

    $date_display_settings = [
      'label' => 'hidden',
      'settings' => [
        'date_format' => 'custom',
        'custom_date_format' => 'm/d/Y',
      ],
    ];

    return [
      'data' => [
        'plan' => [
          'data' => Link::fromTextAndUrl($rate_plan->getDisplayName(), $this->ratePlanUrl($entity)),
          'class' => ['purchased-plan-rate-plan'],
        ],
        'status' => [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '<span class="status status--{{ status|clean_class }}">{{ status }}</span>',
            '#context' => ['status' => $this->t('@status', ['@status' => $entity->getStatus()])],
          ],
          'class' => ['purchased-plan-status'],
        ],
        'start_date' => [
          'data' => $entity->get('startDate')->view($date_display_settings),
          'class' => ['purchased-plan-start-date'],
        ],
        'end_date' => [
          'data' => $entity->getEndDate() ? $entity->get('endDate')->view($date_display_settings) : NULL,
          'class' => ['purchased-plan-end-date'],
        ],
        'operations'    => ['data' => $this->buildOperations($entity)],
      ],
      'class' => ['purchased-plan-row', Html::cleanCssIdentifier(strtolower($rate_plan->getDisplayName()))],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];
    $label = $this->entityType->getPluralLabel();

    // Group the purchases by status.
    $purchased_plans = [
      PurchasedPlanInterface::STATUS_ACTIVE => [
        'title' => $this->t('Active and Future @label', ['@label' => $label]),
        'empty' => $this->t('No active or future @label.', ['@label' => strtolower($label)]),
        'entities' => [],
      ],
      PurchasedPlanInterface::STATUS_ENDED => [
        'title' => $this->t('Cancelled and Expired @label', ['@label' => $label]),
        'empty' => $this->t('No cancelled or expired @label.', ['@label' => strtolower($label)]),
        'entities' => [],
      ],
    ];

    /** @var \Drupal\apigee_m10n\Entity\PurchasedPlanInterface $entity */
    foreach ($this->load() as $entity) {
      if (in_array($entity->getStatus(), [PurchasedPlanInterface::STATUS_ACTIVE, PurchasedPlanInterface::STATUS_FUTURE])) {
        $purchased_plans[PurchasedPlanInterface::STATUS_ACTIVE]['entities'][] = $entity;
        continue;
      }

      $purchased_plans[PurchasedPlanInterface::STATUS_ENDED]['entities'][] = $entity;
    }

    foreach ($purchased_plans as $key => $purchased_plan) {
      $build[$key]['heading'] = [
        '#type' => 'html_tag',
        '#value' => $purchased_plan['title'],
        '#tag' => 'h3',
      ];

      $build[$key]['table'] = [
        '#type' => 'table',
        '#header' => $this->buildHeader(),
        '#title' => $purchased_plan['title'],
        '#rows' => [],
        '#empty' => $purchased_plan['empty'],
        '#cache' => [
          'contexts' => $this->entityType->getListCacheContexts(),
          'tags' => $this->entityType->getListCacheTags(),
        ],
      ];

      if (count($purchased_plan['entities'])) {
        foreach ($purchased_plan['entities'] as $entity) {
          if ($row = $this->buildRow($entity)) {
            $build[$key]['table']['#rows'][$entity->id()] = $row;
          }
        }
      }

      array_push($build[$key]['table']['#cache']['contexts'], 'url.query_args');
      array_push($build[$key]['table']['#cache']['tags'], PurchasedPlanForm::MY_PURCHASES_CACHE_TAG);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $rate_plan = $entity->getRatePlan();
    $org_timezone = $rate_plan->getOrganization()->getTimezone();
    $today = new \DateTime('today', $org_timezone);

    if ($entity->access('update')
      && $entity->isActive()
      && (
        (empty($entity->getEndDate()) && empty($rate_plan->getEndDate()))
        || $today < $entity->getEndDate()
        || $today < $rate_plan->getEndDate()
      )
    ) {
      $operations['cancel'] = [
        'title' => $this->t('Cancel'),
        'weight' => 10,
        'url' => $this->cancelUrl($entity),
      ];
    }

    return $operations;
  }

  /**
   * Gets a cancel url for the given entity.
   *
   * We could just use `$entity->toUrl('cancel')` but then we would have to
   * look up the drupal user ID in `toUrl()`.
   *
   * @param \Drupal\apigee_m10n\Entity\PurchasedPlanInterface $purchased_plan
   *   The purchased_plan entity.
   *
   * @return \Drupal\Core\Url
   *   The cancel purchased plan url.
   */
  abstract protected function cancelUrl(PurchasedPlanInterface $purchased_plan);

  /**
   * Gets the rate plan URL for the subscribed rate plan.
   *
   * @param \Drupal\apigee_m10n\Entity\PurchasedPlanInterface $purchased_plan
   *   The purchased_plan entity.
   *
   * @return \Drupal\Core\Url
   *   The rate plan canonical url.
   */
  abstract protected function ratePlanUrl(PurchasedPlanInterface $purchased_plan);

}
