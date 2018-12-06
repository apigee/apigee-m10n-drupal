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

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines implementation of a subscriptions listing page.
 *
 * @ingroup entity_api
 */
class SubscriptionListBuilderForDeveloper extends EntityListBuilder implements ContainerInjectionInterface {

  /**
   * Subscription storage.
   *
   * @var \Drupal\apigee_m10n\Entity\Storage\SubscriptionStorageInterface
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
   * Messanger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The user for the listing page.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Current user for the listing page.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $current_user;

  /**
   * SubscriptionListBuilderForDeveloper constructor.
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
   *   Messanger service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, MessengerInterface $messenger) {
    parent::__construct($entity_type, $storage);

    $this->storage = $storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->current_user = \Drupal::currentUser();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.apigee_m10n'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entityType = $container->get('entity_type.manager')->getDefinition('subscription');
    return static::createInstance($container, $entityType);
  }

  /**
   * {@inheritdoc}
   */
  public function getPageTitle(RouteMatchInterface $route_match): string {
    return $this->t('Purchased Plans');
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('@subscriptions', [
      '@subscriptions' => (string) $this->entityTypeManager->getDefinition('subscription')->getPluralLabel()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'status'        => ['data' => $this->t('Status'), 'field' => 'status'],
      'package'       => [
        'data'  => $this->t('Package'),
        'field' => 'package',
        'sort'  => 'desc'
      ],
      'products'      => ['data' => $this->t('Products'), 'field' => 'products'],
      'plan'          => ['data' => $this->t('Plan Name'), 'field' => 'plan'],
      'start_date'    => ['data' => $this->t('Start Date'), 'field' => 'start_date'],
      'end_date'      => ['data' => $this->t('End Date'), 'field' => 'end_date'],
      'plan_end_date' => ['data' => $this->t('Plan End Date'), 'field' => 'plan_end_date'],
      'renewal_date'  => ['data' => $this->t('Renewal Date'), 'field' => 'renewal_date'],
      'operations'    => $this->t('Actions')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $rate_plan = $entity->getRatePlan();

    $products = array_reduce($rate_plan->getPackage()->getApiProducts(), function ($result, $product) {
      return $result ? "{$result}, {$product->getDisplayName()}" : $product->getDisplayName();
    }, "");

    $url = $this->ensureDestination(Url::fromRoute('entity.rate_plan.canonical', [
      'user' => $this->current_user->id(),
      'package' => $rate_plan->getPackage()->id(),
      'rate_plan' => $rate_plan->id()
    ]));

    return [
      'data' => [
        'status'        => $entity->getSubscriptionStatus(),
        'package'       => $rate_plan->getPackage()->getDisplayName(),
        'products'      => $products,
        'plan'          => Link::fromTextAndUrl($rate_plan->getDisplayName(), $url),
        'start_date'    => $entity->getStartDate()->format('m/d/Y'),
        'end_date'      => $entity->getEndDate() ? $entity->getEndDate()->format('m/d/Y') : NULL,
        'plan_end_date' => $rate_plan->getEndDate() ? $rate_plan->getEndDate()->format('m/d/Y') : NULL,
        'renewal_date'  => $entity->getRenewalDate() ? $entity->getRenewalDate()->format('m/d/Y') : NULL,
        'operations'    => [
          'data' => $this->buildOperations($entity)
        ]
      ],
      'class' => [Html::cleanCssIdentifier(strtolower($rate_plan->getDisplayName()))],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    throw new EntityStorageException('Unable to load subscriptions directly. Use `SubscriptionStorage::loadPackageRatePlans`.');
  }

  /**
   * {@inheritdoc}
   */
  public function render(UserInterface $user = NULL) {
    $this->user = $user;
    $developer_id = $user->getEmail();

    $header = $this->buildHeader();

    $build['table'] = [
      '#type'   => 'table',
      '#header' => $header,
      '#title'  => $this->getTitle(),
      '#rows'   => [],
      '#empty'  => $this->t('There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
      '#cache'  => [
        'contexts' => $this->entityType->getListCacheContexts() + ['url.query_args'],
        'tags'     => $this->entityType->getListCacheTags() + ['apigee_my_subscriptions'],
      ],
    ];

    if (!$developer_id) {
      $this->logger->error($this->t('Developer with email @email not found.', ['@email' => $user->getEmail()]));
      $this->messenger->addError($this->t('Developer with email @email not found.', ['@email' => $user->getEmail()]));
      return $build;
    }

    $rows = [];

    foreach ($this->entityTypeManager->getStorage('subscription')->loadByDeveloperId($developer_id) as $entity) {
      if ($row = $this->buildRow($entity)) {
        $rows[$entity->id()] = $row;
      }
    }

    $build['table']['#rows'] = $rows;

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    // TODO: Is a custom unsubscribe access check is really necessary?
    if ($entity->access('update') && $entity->hasLinkTemplate('unsubscribe-form')) {
      if ($entity->isSubscriptionActive()) {
        $operations['unsubscribe'] = [
          'title' => $this->t('Cancel Plan'),
          'weight' => 10,
          'url' => $this->ensureDestination(Url::fromRoute('entity.subscription.unsubscribe_form', ['user' => $this->user->id(), 'subscription' => $entity->id()])),
        ];
      }
    }

    return $operations;
  }

}
