<?php

/**
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

use Drupal\apigee_m10n\Entity\PurchasedPlanInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * @file
 * Hooks for apigee_m10n module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the prepaid balance page.
 *
 * @param array $build
 *   A renderable array representing the page.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The prepaid balance owner entity.
 *
 * @see \Drupal\apigee_m10n\Controller\PrepaidBalanceControllerBase::render()
 */
function hook_apigee_m10n_prepaid_balance_list_alter(array &$build, EntityInterface $entity) {
}

/**
 * Alters the insufficient balance error message.
 *
 * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
 *   A translatable error message.
 * @param \Drupal\apigee_m10n\Entity\PurchasedPlanInterface $purchased_plan
 *   The failed purchased plan.
 *
 * @see \Drupal\apigee_m10n\Entity\Form\PurchasedPlanForm::save()
 */
function hook_apigee_m10n_insufficient_balance_error_message_alter(TranslatableMarkup &$message, PurchasedPlanInterface $purchased_plan) {
}

/**
 * @} End of "addtogroup hooks".
 */
