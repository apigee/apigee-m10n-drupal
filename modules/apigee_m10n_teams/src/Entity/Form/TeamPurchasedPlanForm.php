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

namespace Drupal\apigee_m10n_teams\Entity\Form;

use Drupal\apigee_m10n\Entity\Form\PurchasedPlanForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\apigee_edge_teams\Entity\Team;
use Drupal\apigee_m10n_teams\Entity\TeamsPurchasedPlanInterface;
use Drupal\apigee_edge\Entity\Form\FieldableEdgeEntityForm;

/**
 * Team purchased plan entity form.
 */
class TeamPurchasedPlanForm extends PurchasedPlanForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // If the team has already purchased this plan, show a message instead.
    /** @var \Drupal\apigee_m10n\Entity\RatePlanInterface $rate_plan */

    if ($this->entity instanceof TeamsPurchasedPlanInterface && $this->entity->isTeamPurchasedPlan()) {
      // Execute only is called from teams.
      $team_id = $this->entity->decorated()->getCompany()->id();
      $monetization = \Drupal::service('apigee_m10n.teams');
      if (($rate_plan = $this->getEntity()->getRatePlan()) && ($monetization->isTeamAlreadySubscribed($team_id, $rate_plan))) {
        return [
          '#markup' => $this->t('You have already purchased %rate_plan.', [
            '%rate_plan' => $rate_plan->getDisplayName(),
          ]),
        ];
      }
      $form = FieldableEdgeEntityForm::buildForm($form, $form_state);
    }
    else {
      // Call buildForm of PurchasedPlanForm.
      $form = parent::buildForm($form, $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      if ($this->entity instanceof TeamsPurchasedPlanInterface && $this->entity->isTeamPurchasedPlan()) {
        // Auto assign legal name.
        $company_id = $this->entity->decorated()->getCompany()->id();
        $company = Team::load($company_id);
        // Autopopulate legal name when company has no legal name attribute set.
        if (empty($company->getAttributeValue(static::LEGAL_NAME_ATTR))) {
          $company->setAttribute(static::LEGAL_NAME_ATTR, $company_id);
          $company->save();
        }

        $display_name = $this->entity->getRatePlan()->getDisplayName();
        Cache::invalidateTags([PurchasedPlanForm::MY_PURCHASES_CACHE_TAG]);

        if ($this->entity->save()) {
          $this->messenger->addStatus($this->t('You have purchased %label plan', [
            '%label' => $display_name,
          ]));
          $form_state->setRedirect('entity.purchased_plan.team_collection', ['team' => $company_id]);
        }
        else {
          $this->messenger->addWarning($this->t('Unable to purchase %label plan', [
            '%label' => $display_name,
          ]));
        }
      }
      else {
        parent::save($form, $form_state);
      }
    }
    catch (\Exception $e) {
      $this->messenger->addError($e->getMessage());
    }
  }

}
