<?php

/*
 * Copyright 2025 Google Inc.
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

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\apigee_edge\Entity\Form\FieldableEdgeEntityForm;
use Drupal\apigee_m10n\Entity\Form\PurchasedProductForm;
use Drupal\apigee_m10n_teams\Entity\TeamsPurchasedProductInterface;

/**
 * Team purchased plan entity form.
 */
class TeamPurchasedProductForm extends PurchasedProductForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // If the team has already purchased this plan, show a message instead.
    /** @var \Drupal\apigee_m10n\Entity\XRatePlanInterface $rate_plan */
    if ($this->entity instanceof TeamsPurchasedProductInterface && $this->entity->isTeamPurchasedProduct()) {
      // Execute only is called from teams.
      $team_id = $this->entity->decorated()->getAppGroup()->id();

      $monetization = \Drupal::service('apigee_m10n.teams');
      if (($rate_plan = $this->getEntity()->getRatePlan()) && ($monetization->isxTeamAlreadySubscribed($team_id, $rate_plan))) {
        return [
          '#markup' => $this->t('You have already purchased %rate_plan.', [
            '%rate_plan' => $rate_plan->getDisplayName(),
          ]),
        ];
      }
      $form = FieldableEdgeEntityForm::buildForm($form, $form_state);
      $this->insufficientFundsWorkflow($form, $form_state);
    }
    else {
      // Call buildForm of PurchasedProductForm.
      $form = parent::buildForm($form, $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      if ($this->entity instanceof TeamsPurchasedProductInterface && $this->entity->isTeamPurchasedProduct()) {
        $appgroup_id = $this->entity->decorated()->getAppGroup()->id();

        if (!($rate_plan = $this->entity->getRatePlan())) {
          $this->messenger->addError($this->t('Unable to purchase product: invalid rate plan.'));
          return;
        }

        $display_name = $rate_plan->getDisplayName();
        Cache::invalidateTags([PurchasedProductForm::MY_PURCHASES_PRODUCT_CACHE_TAG]);

        if ($this->entity->save()) {
          $this->messenger->addStatus($this->t('You have purchased %label product', [
            '%label' => $display_name,
          ]));
          $form_state->setRedirect('entity.purchased_product.team_collection', ['team' => $appgroup_id]);
        }
        else {
          $this->messenger->addWarning($this->t('Unable to purchase %label product', [
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
