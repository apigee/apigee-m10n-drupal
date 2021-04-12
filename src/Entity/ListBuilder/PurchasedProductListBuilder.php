<?php

/*
 * Copyright 2021 Google Inc.
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

use Drupal\apigee_m10n\Entity\Form\PurchasedProductForm;
use Drupal\apigee_m10n\Entity\PurchasedProductInterface;
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
 * Defines implementation of a purchased api product listing page.
 *
 * @ingroup entity_api
 */
abstract class PurchasedProductListBuilder extends EntityListBuilder implements ContainerInjectionInterface {

  /**
   * Purchased plan storage.
   *
   * @var \Drupal\apigee_m10n\Entity\Storage\PurchasedProductStorageInterface
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
   * PurchasedProductListBuilder constructor.
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
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, MessengerInterface $messenger) {
    parent::__construct($entity_type, $storage);

    $this->storage = $storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->messenger = $messenger;
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
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entityType = $container->get('entity_type.manager')->getDefinition('purchased_product');
    return static::createInstance($container, $entityType);
  }

  /**
   * {@inheritdoc}
   */
  public function getPageTitle(RouteMatchInterface $route_match): string {
    // TODO: make sure this string is configurable.
    return $this->t('Purchased Products');
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
      'subscription_id' => [
        'data' => $this->t('Subscription Name'),
        'class' => ['purchased-subscription'],
        'field' => 'name',
      ],
      'plan' => [
        'data' => $this->t('API Product'),
        'class' => ['purchased-rate-plan'],
        'field' => 'plan',
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
        'class' => ['purchased-product-action'],
      ],
      'status' => [
        'data' => $this->t('Status'),
        'field' => 'status',
        'class' => ['purchased-plan-status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\apigee_m10n\Entity\PurchasedProductInterface $entity */
    $datetimeImmutable = new \DateTimeImmutable();
    $utc = new \DateTimeZone('UTC');

    return [
      'data' => [
        'subscription_id' => [
          'data' => $entity->getname(),
          'class' => ['purchased-subscription'],
        ],
        'plan' => [
          'data' => $entity->getApiProduct(),
          'class' => ['purchased-plan-rate-plan'],
        ],
        'start_date' => [
          'data' => $datetimeImmutable->setTimestamp($entity->getStartTime() / 1000)->setTimezone($utc)->format('m/d/Y'),
          'class' => ['purchased-plan-start-date'],
        ],
        'end_date' => [
          'data' => $entity->getEndTime() ? $datetimeImmutable->setTimestamp($entity->getEndTime() / 1000)->setTimezone($utc)->format('m/d/Y') : NULL,
          'class' => ['purchased-plan-end-date'],
        ],
        'operations' => [
          'data' => $this->buildOperations($entity),
          'class' => ['purchased-product-action'],
        ],
        'status' => [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '<span class="status status--{{ status|clean_class }}">{{ status }}</span>',
            '#context' => ['status' => $this->t('@status', ['@status' => $entity->getStatus()])],
          ],
          'class' => ['purchased-plan-status'],
        ],
      ],
      'class' => ['purchased-plan-row', Html::cleanCssIdentifier(strtolower($entity->getName()))],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];
    $label = $this->entityType->getPluralLabel();

    // Group the purchases by status.
    $purchased_products = [
      PurchasedProductInterface::STATUS_ACTIVE => [
        'title' => $this->t('Active @label', ['@label' => $label]),
        'empty' => $this->t('No active @label.', ['@label' => strtolower($label)]),
        'entities' => [],
      ],
      PurchasedProductInterface::STATUS_ENDED => [
        'title' => $this->t('Inactive @label', ['@label' => $label]),
        'empty' => $this->t('No inactive @label.', ['@label' => strtolower($label)]),
        'entities' => [],
      ],
    ];

    /** @var \Drupal\apigee_m10n\Entity\PurchasedProductInterface $entity */
    foreach ($this->load() as $entity) {
      if (in_array($entity->getStatus(), [PurchasedProductInterface::STATUS_ACTIVE, PurchasedProductInterface::STATUS_FUTURE])) {
        $purchased_products[PurchasedProductInterface::STATUS_ACTIVE]['entities'][] = $entity;
        continue;
      }

      $purchased_products[PurchasedProductInterface::STATUS_ENDED]['entities'][] = $entity;
    }

    // Get the rateplan Id of the apiproduct.
    if (!empty($entity)) {
      $this->activeRatePlanId = $entity->getActiveRatePlans();
    }

    foreach ($purchased_products as $key => $purchased_product) {
      $this->checkStatus = PurchasedProductInterface::STATUS_ACTIVE;

      $header = $this->buildHeader();
      if ($key == PurchasedProductInterface::STATUS_ACTIVE) {
        unset($header['end_date']);
      }
      else {
        unset($header['operations']);
      }

      $build[$key]['heading'] = [
        '#type' => 'html_tag',
        '#value' => $purchased_product['title'],
        '#tag' => 'h3',
      ];

      $build[$key]['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#title' => $purchased_product['title'],
        '#rows' => [],
        '#empty' => $purchased_product['empty'],
        '#cache' => [
          'contexts' => $this->entityType->getListCacheContexts(),
          'tags' => $this->entityType->getListCacheTags(),
        ],
      ];

      if (count($purchased_product['entities'])) {
        foreach ($purchased_product['entities'] as $entity) {
          if ($row = $this->buildRow($entity)) {
            if ($key == PurchasedProductInterface::STATUS_ACTIVE) {
              unset($row['data']['end_date']);
            }
            else {
              unset($row['data']['operations']);
            }
            $build[$key]['table']['#rows'][$entity->id()] = $row;
          }
        }
      }

      array_push($build[$key]['table']['#cache']['contexts'], 'url.query_args');
      array_push($build[$key]['table']['#cache']['tags'], PurchasedProductForm::MY_PURCHASES_PRODUCT_CACHE_TAG);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    // Get Current UTC timestamp & Transform timeRange to the required format and time zone.
    $utc = new \DateTimeZone('UTC');
    $currentTimestamp = (new \DateTimeImmutable())->setTimezone($utc);
    $currentTimestamp = (int) ($currentTimestamp->getTimestamp() . $currentTimestamp->format('v'));

    if ($entity->access('update')
      && $entity->isActive()
      && (empty($entity->getEndTime()) || ($currentTimestamp < $entity->getEndTime()))
    ) {
      $operations['cancel'] = [
        'title' => $this->t('Cancel'),
        'weight' => 11,
        'url' => $this->cancelUrl($entity),
      ];
    }

    if (!empty($this->activeRatePlanId[$entity->getApiProduct()])) {
      $link = $this->ratePlanUrl($entity, $this->activeRatePlanId[$entity->getApiProduct()]);
      $operations['view'] = [
        'title' => $this->t('View Rate Plan'),
        'weight' => 1,
        'url' => $link,
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
   * @param \Drupal\apigee_m10n\Entity\PurchasedProductInterface $purchased_product
   *   The purchased_product entity.
   *
   * @return \Drupal\Core\Url
   *   The cancel purchased product url.
   */
  abstract protected function cancelUrl(PurchasedProductInterface $purchased_product);

  /**
   * Gets the rate plan URL for the subscribed rate plan.
   *
   * @param \Drupal\apigee_m10n\Entity\PurchasedProductInterface $purchased_product
   *   The purchased_product entity.
   * @param string $rateplanId
   *   Rate plan ID.
   *
   * @return \Drupal\Core\Url
   *   The rate plan canonical url.
   */
  abstract protected function ratePlanUrl(PurchasedProductInterface $purchased_product, $rateplanId);

}
