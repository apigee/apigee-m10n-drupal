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

use Apigee\Edge\Api\Monetization\Structure\LegalEntityTermsAndConditionsHistoryItem;
use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\apigee_m10n_teams\Access\TeamPermissionAccessInterface;
use Drupal\apigee_m10n_teams\Entity\Routing\MonetizationTeamsEntityRouteProvider;
use Drupal\apigee_m10n_teams\Entity\Storage\TeamPackageStorage;
use Drupal\apigee_m10n_teams\Entity\Storage\TeamSubscriptionStorage;
use Drupal\apigee_m10n_teams\Entity\TeamAwareRatePlan;
use Drupal\apigee_m10n_teams\Entity\TeamRouteAwarePackage;
use Drupal\apigee_m10n_teams\Entity\TeamRouteAwareSubscription;
use Drupal\apigee_m10n_teams\Plugin\Field\FieldFormatter\TeamSubscribeFormFormatter;
use Drupal\apigee_m10n_teams\Plugin\Field\FieldFormatter\TeamSubscribeLinkFormatter;
use Drupal\apigee_m10n_teams\Plugin\Field\FieldWidget\CompanyTermsAndConditionsWidget;
use Drupal\apigee_m10n_teams\Entity\Form\TeamSubscriptionForm;
use Drupal\apigee_m10n\Exception\SdkEntityLoadException;
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
   * The `apigee_m10n.monetization` service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * The Teams SDK controller factory.
   *
   * @var \Drupal\apigee_m10n\
   */
  protected $sdk_controller_factory;

  /**
   * Static cache of `acceptLatestTermsAndConditions` results.
   *
   * @var array
   */
  protected $companyAcceptedTermsStatus;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * MonetizationTeams constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\apigee_m10n_teams\TeamSdkControllerFactoryInterface $sdk_controller_factory
   *   The SDK controller factory.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The monetization service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(RouteMatchInterface $route_match, TeamSdkControllerFactoryInterface $sdk_controller_factory, MonetizationInterface $monetization, LoggerInterface $logger) {
    $this->route_match = $route_match;
    $this->sdk_controller_factory = $sdk_controller_factory;
    $this->monetization = $monetization;
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
      // Override the storage class.
      $entity_types['package']->setStorageClass(TeamPackageStorage::class);
    }

    // Overrides for the `rate_plan` entity.
    if (isset($entity_types['rate_plan'])) {
      // Use our class to override the original entity class.
      $entity_types['rate_plan']->setClass(TeamAwareRatePlan::class);
      $entity_types['rate_plan']->setLinkTemplate('team', '/teams/{team}/monetization/package/{package}/plan/{rate_plan}');
      $entity_types['rate_plan']->setLinkTemplate('team-subscribe', '/teams/{team}/monetization/package/{package}/plan/{rate_plan}/subscribe');
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
      // Override subscribe form.
      $entity_types['subscription']->setFormClass('default', TeamSubscriptionForm::class);
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
  public function fieldWidgetInfoAlter(array &$info) {
    // Override the terms and condition widget.
    $info['apigee_tnc_widget']['class'] = CompanyTermsAndConditionsWidget::class;
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
    if ($team = $this->currentTeam()) {
      if ($operation === 'subscribe') {
        return $this->subscriptionCreateAccess($account, ['team' => $team], 'subscription');
      }
      else {
        return $this->entityAccess($entity, $operation, $account);
      }
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
    // All team routes have the team as the first parameter and we could be
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
  public function isLatestTermsAndConditionAccepted(string $company_id): ?bool {
    if (!($latest_tnc = $this->monetization->getLatestTermsAndConditions())) {
      // If there isn't a latest TnC, and there was no error, there shouldn't be
      // anything to accept.
      // TODO: Add a test for an org with no TnC defined.
      return TRUE;
    }
    // Check the cache table.
    if (!isset($this->companyAcceptedTermsStatus[$company_id])) {
      // Get the latest TnC ID.
      $latest_tnc_id = $latest_tnc->id();

      // Creates a controller for getting accepted TnC.
      $controller = $this->sdk_controller_factory->companyTermsAndConditionsController($company_id);

      try {
        $history = $controller->getTermsAndConditionsHistory();
      }
      catch (\Exception $e) {
        $message = "Unable to load Terms and Conditions history for a team \n\n" . $e;
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

      $this->companyAcceptedTermsStatus[$company_id] = ($latest instanceof LegalEntityTermsAndConditionsHistoryItem) && $latest->getAction() === 'ACCEPTED';
    }

    return $this->companyAcceptedTermsStatus[$company_id];
  }

  /**
   * {@inheritdoc}
   */
  public function acceptLatestTermsAndConditions(string $company_id): ?LegalEntityTermsAndConditionsHistoryItem {
    try {
      // Reset the static cache for this team.
      unset($this->companyAcceptedTermsStatus[$company_id]);
      return $this->sdk_controller_factory->companyTermsAndConditionsController($company_id)
        ->acceptTermsAndConditionsById($this->monetization->getLatestTermsAndConditions()->id());
    }
    catch (\Throwable $t) {
      $this->logger->error('Unable to accept latest TnC: ' . $t->getMessage());
    }
    return NULL;
  }

}
