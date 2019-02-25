<?php

/*
 * @file
 * Copyright 2018 Google Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

namespace Drupal\apigee_m10n_teams;

use Apigee\Edge\Api\Monetization\Entity\TermsAndConditionsInterface;
use Apigee\Edge\Api\Monetization\Structure\LegalEntityTermsAndConditionsHistoryItem;
use Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface;
use Drupal\apigee_m10n_teams\Entity\Routing\MonetizationTeamsEntityRouteProvider;
use Drupal\apigee_m10n_teams\Entity\Storage\TeamSubscriptionStorage;
use Drupal\apigee_m10n_teams\Entity\TeamAwareRatePlan;
use Drupal\apigee_m10n_teams\Entity\TeamRouteAwarePackage;
use Drupal\apigee_m10n_teams\Entity\TeamRouteAwareSubscription;
use Drupal\apigee_m10n_teams\Plugin\Field\FieldFormatter\TeamSubscribeFormFormatter;
use Drupal\apigee_m10n_teams\Plugin\Field\FieldFormatter\TeamSubscribeLinkFormatter;
use Drupal\apigee_m10n\Exception\SdkEntityLoadException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;

/**
 * The `apigee_m10n.teams` service.
 */
class MonetizationTeams implements MonetizationTeamsInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $route_match;

  /**
   * The Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * The Teams SDK controller factory.
   *
   * @var \Drupal\apigee_m10n\
   */
  private $sdk_controller_factory;

  /**
   * Static cache of `acceptLatestTermsAndConditions` results.
   *
   * @var array
   */
  private $companyAcceptedTermsStatus;

  /**
   * Static cache of the latest TnC.
   *
   * @var \Apigee\Edge\Api\Monetization\Entity\TermsAndConditionsInterface|false
   */
  protected $latestTermsAndConditions;

  /**
   * Static cache of the TnC list.
   *
   * @var \Apigee\Edge\Api\Monetization\Entity\TermsAndConditionsInterface[]
   */
  protected $termsAndConditionsList;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * MonetizationTeams constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\apigee_m10n\ApigeeSdkControllerFactoryInterface $sdk_controller_factory
   *   The SDK controller factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The Cache backend.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(RouteMatchInterface $route_match, ApigeeSdkControllerFactoryInterface $sdk_controller_factory, CacheBackendInterface $cache, LoggerInterface $logger) {
    $this->route_match = $route_match;
    $this->sdk_controller_factory = $sdk_controller_factory;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeAlter(array &$entity_types) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    if (isset($entity_types['package'])) {
      // Use our class to override the original entity class.
      $entity_types['package']->setClass(TeamRouteAwarePackage::class);
      // Create a link template for team packages.
      $entity_types['package']->setLinkTemplate('team', '/teams/{team}/monetization/package/{package}');
      // Get the entity route providers.
      $route_providers = $entity_types['package']->getRouteProviderClasses();
      // Override the `html` route provider.
      $route_providers['html'] = MonetizationTeamsEntityRouteProvider::class;
      $entity_types['package']->setHandlerClass('route_provider', $route_providers);
    }

    // Overrides for the `rate_plan` entity.
    if (isset($entity_types['rate_plan'])) {
      // Use our class to override the original entity class.
      $entity_types['rate_plan']->setClass(TeamAwareRatePlan::class);
      $entity_types['rate_plan']->setLinkTemplate('team', '/teams/{team}/monetization/package/{package}/plan/{rate_plan}');
      // Get the entity route providers.
      $route_providers = $entity_types['rate_plan']->getRouteProviderClasses();
      // Override the `html` route provider.
      $route_providers['html'] = MonetizationTeamsEntityRouteProvider::class;
      $entity_types['rate_plan']->setHandlerClass('route_provider', $route_providers);
    }

    // Overrides for the subscription entity.
    if (isset($entity_types['subscription'])) {
      // Use our class to override the original entity class.
      $entity_types['subscription']->setClass(TeamRouteAwareSubscription::class);
      // Override the storage class.
      $entity_types['subscription']->setStorageClass(TeamSubscriptionStorage::class);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fieldFormatterInfoAlter(array &$info) {
    // Override the subscribe link and form formatters.
    $info['apigee_subscribe_form']['class'] = TeamSubscribeFormFormatter::class;
    $info['apigee_subscribe_link']['class'] = TeamSubscribeLinkFormatter::class;
  }

  /**
   * {@inheritdoc}
   */
  public function subscriptionAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($entity->isTeamSubscription() && ($team = $entity->get('team')->entity)) {
      // Gat the access result.
      $access = $this->teamAccessCheck()->allowedIfHasTeamPermissions($team, $account, ["{$operation} subscription"]);
      // Team permission results completely override user permissions.
      return $access->isAllowed() ? $access : AccessResult::forbidden($access->getReason());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function subscriptionCreateAccess(AccountInterface $account, array $context, $entity_bundle) {
    if (isset($context['team']) && $context['team'] instanceof TeamInterface) {
      // Gat the access result.
      $access = $this->teamAccessCheck()->allowedIfHasTeamPermissions($context['team'], $account, ["subscribe rate_plan"]);
      // Team permission results completely override user permissions.
      return $access->isAllowed() ? $access : AccessResult::forbidden($access->getReason());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function ratePlanAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'subscribe' && ($team = $this->currentTeam())) {
      return $this->subscriptionCreateAccess($account, ['team' => $team], 'subscription');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($team = $this->currentTeam()) {
      // Get the access result.
      $access = $this->teamAccessCheck()->allowedIfHasTeamPermissions($team, $account, ["{$operation} {$entity->getEntityTypeId()}"]);
      // Team permission results completely override user permissions.
      return $access->isAllowed() ? $access : AccessResult::forbidden($access->getReason());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function currentTeam(): ?TeamInterface {
    // TODO: This call could be much smarter.
    // All team routes have the team ast the first parameter and we could be
    // checking a route list to make sure the team is part of a team route
    // similar to the `_apigee_monetization_route` route option.
    return $this->route_match->getParameter('team');
  }

  /**
   * Helper that gets the `TeamPermissionAccessCheck` service.
   *
   * This would be injected but injection causes a circular reference error when
   * rebuilding the container due to it's dependency on the
   * `apigee_edge_teams.team_permissions` service.
   *
   * See: <https://github.com/apigee/apigee-edge-drupal/pull/138#discussion_r259570088>.
   *
   * @return \Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface
   *   The team permission access checker.
   */
  protected function teamAccessCheck(): TeamPermissionAccessInterface {
    return \Drupal::service('apigee_m10n_teams.access_check.team_permission');
  }

  /**
   * {@inheritdoc}
   */
  public function isLatestTermsAndConditionAccepted(string $developer_id): ?bool {
    if (!($latest_tnc = $this->getLatestTermsAndConditions())) {
      // If there isn't a latest TnC, and there was no error, there shouldn't be
      // anything to accept.
      // TODO: Add a test for an org with no TnC defined.
      return TRUE;
    }
    // Check the cache table.
    if (!isset($this->developerAcceptedTermsStatus[$developer_id])) {
      // Get the latest TnC ID.
      $latest_tnc_id = $latest_tnc->id();

      // Creates a controller for getting accepted TnC.
      $controller = $this->sdk_controller_factory->developerTermsAndConditionsController($developer_id);

      try {
        $history = $controller->getTermsAndConditionsHistory();
      }
      catch (\Exception $e) {
        $message = "Unable to load Terms and Conditions history for developer \n\n" . $e;
        $this->logger->error($message);
        throw new SdkEntityLoadException($message);
      }

      // All we care about is the latest entry for the latest TnC.
      $latest = array_reduce($history, function ($carry, $item) use ($latest_tnc_id) {
        /** @var \Apigee\Edge\Api\Monetization\Structure\LegalEntityTermsAndConditionsHistoryItem $item */
        // No need to look at items other than for the current TnC.
        if ($item->getTnc()->id() !== $latest_tnc_id) {
          return $carry;
        }
        // Gets the time of the carry over item.
        $carry_time = $carry instanceof LegalEntityTermsAndConditionsHistoryItem ? $carry->getAuditDate()->getTimestamp() : NULL;

        return $item->getAuditDate()->getTimestamp() > $carry_time ? $item : $carry;
      });

      $this->developerAcceptedTermsStatus[$developer_id] = ($latest instanceof LegalEntityTermsAndConditionsHistoryItem) && $latest->getAction() === 'ACCEPTED';
    }

    return $this->developerAcceptedTermsStatus[$developer_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestTermsAndConditions(): ?TermsAndConditionsInterface {
    // Check the static cache.
    if (isset($this->latestTermsAndConditions)) {
      return $this->latestTermsAndConditions;
    }
    // Get the full list.
    $list = $this->getTermsAndConditionsList();

    // Get the latest TnC that have already started.
    $latest = empty($list) ? NULL : array_reduce($list, function ($carry, $item) {
      /** @var \Apigee\Edge\Api\Monetization\Entity\TermsAndConditionsInterface $item */
      // Gets the time of the carry over item.
      $carry_time = $carry instanceof TermsAndConditionsInterface ? $carry->getStartDate()->getTimestamp() : NULL;
      // Gets the timestamp of the current item.
      $item_time = $item->getStartDate()->getTimestamp();
      $now = time();
      // Return the current item only if it the latest without starting in the
      // future.
      return ($item_time > $carry_time && $item_time < $now) ? $item : $carry;
    });

    // Cache the result for this request.
    $this->latestTermsAndConditions = $latest;

    return $this->latestTermsAndConditions;
  }

  /**
   * Gets the full list of terms and conditions.
   *
   * @return \Apigee\Edge\Api\Monetization\Entity\TermsAndConditionsInterface[]
   *   Returns the full list of terms and conditions or false on error.
   */
  protected function getTermsAndConditionsList(): array {
    // The cache ID.
    $cid = 'apigee_m10n:terms_and_conditions_list';

    // Check the static cache.
    if (isset($this->termsAndConditionsList)) {
      return $this->termsAndConditionsList;
    }
    // Check the cache.
    elseif (($cache = $this->cache->get($cid)) && ($list = $cache->data)) {
      // `$list` is set so there is nothing to do here.
    }
    else {
      try {
        $list = $this->sdk_controller_factory->termsAndConditionsController()->getEntities();
      }
      catch (\Exception $ex) {
        $this->logger->error("Unable to load Terms and Conditions: \n {$ex}");
        $this->cache->delete($cid);
        throw new SdkEntityLoadException("Error loading Terms and conditions. \n\n" . $ex);
      }

      // Cache the list for 5 minutes.
      $this->cache->set($cid, $list, time() + 299);

    }
    $this->termsAndConditionsList = $list;

    return $this->termsAndConditionsList;
  }

  /**
   * {@inheritdoc}
   */
  public function acceptLatestTermsAndConditions(string $company_id): ?LegalEntityTermsAndConditionsHistoryItem {
    try {
      // Reset the static cache for this developer.
      unset($this->companyAcceptedTermsStatus[$company_id]);
      return $this->sdk_controller_factory->developerTermsAndConditionsController($company_id)
        ->acceptTermsAndConditionsById($this->getLatestTermsAndConditions()->id());
    }
    catch (\Throwable $t) {
      $this->logger->error('Unable to accept latest TnC: ' . $t->getMessage());
    }
    return NULL;
  }

}
