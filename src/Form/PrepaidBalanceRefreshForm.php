<?php

/**
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

namespace Drupal\apigee_m10n\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to to refresh prepaid balance.
 */
class PrepaidBalanceRefreshForm extends FormBase {

  /**
   * Success message for form.
   */
  const SUCCESS_MESSAGE = 'Prepaid balance successfully refreshed.';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prepaid_balance_refresh_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $tags = []) {
    $form_state->set('tags', $tags);

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Invalidate cache tags.
    if ($tags = $form_state->get('tags')) {
      Cache::invalidateTags($tags);

      $this->messenger()->addStatus($this->t(static::SUCCESS_MESSAGE));
    }
  }

}
