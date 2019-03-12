<?php

/*
 * @file
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

namespace Drupal\apigee_m10n_add_credit\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;

/**
 * Plugin implementation of the 'add_credit_target_entity' widget.
 *
 * @FieldWidget(
 *   id = "add_credit_target_entity",
 *   label = @Translation("Select list"),
 *   field_types = {
 *     "add_credit_target_entity",
 *   },
 *   multiple_values = TRUE
 * )
 */
class AddCreditTargetEntitySelectWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  protected function getSelectedOptions(FieldItemListInterface $items) {
    // We need to check against a flat list of options.
    $flat_options = OptGroup::flattenOptions($this->getOptions($items->getEntity()));

    $selected_options = [];
    foreach ($items as $item) {
      $value = "{$item->target_type}:{$item->target_id}";
      // Keep the value if it actually is in the list of options (needs to be
      // checked against the flat list).
      if (isset($flat_options[$value])) {
        $selected_options[] = $value;
      }
    }

    return $selected_options;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    foreach ($values as $index => $value) {
      foreach ([explode(':', $value['target_id'])] as [$target_type, $target_id]) {
        $new_values[$index] = [
          'target_type' => $target_type,
          'target_id' => $target_id,
        ];
      }
    }
    return $new_values;
  }

}
