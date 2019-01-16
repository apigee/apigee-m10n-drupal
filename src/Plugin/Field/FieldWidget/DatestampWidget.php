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

namespace Drupal\apigee_m10n\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Datetime\Plugin\Field\FieldWidget\TimestampDatetimeWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the `apigee_datestamp` widget.
 *
 * @FieldWidget(
 *   id = "apigee_datestamp",
 *   label = @Translation("Apigee date"),
 *   field_types = {
 *     "apigee_datestamp",
 *   }
 * )
 */
class DatestampWidget extends TimestampDatetimeWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();
    $default_value = isset($items[$delta]->value) ? DrupalDateTime::createFromTimestamp($items[$delta]->value->getTimestamp()) : '';
    $element['value'] = $element + [
      '#type'              => 'datetime',
      '#default_value'     => $default_value,
      '#date_date_format'  => 'date',
      '#date_time_element' => 'none',
      '#date_year_range'   => '1902:2037',
    ];
    $element['value']['#description'] = $this->t('Format: %format. Leave blank to use the time of form submission.', ['%format' => Datetime::formatExample($date_format . ' ' . $time_format)]);

    return $element;
  }

}
