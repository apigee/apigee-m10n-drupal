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

namespace Drupal\apigee_m10n\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'apigee_date_and_text_formatter' field formatter.
 *
 * @FieldFormatter(
 *   id = "apigee_date_and_text_formatter",
 *   label = @Translation("Date and text"),
 *   field_types = {
 *     "timestamp"
 *   }
 * )
 */
class DateAndTextFormatter extends TimestampFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'text' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['text'] = [
      '#title' => $this->t('Text'),
      '#type' => 'textarea',
      '#rows' => 3,
      '#default_value' => $this->getSetting('text'),
      '#description' => $this->t('Use <em>@date</em> for placing the formatted date in the text.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $text = $this->getSetting('text');

    foreach ($items as $delta => $item) {
      // Grab the formatted date from the TimestampFormatter and add it to the
      // text.
      $elements[$delta] = [
        '#markup' => t($text, [
          '@date' => $elements[$delta]['#markup'],
        ]),
      ];
    }

    return $elements;
  }

}
