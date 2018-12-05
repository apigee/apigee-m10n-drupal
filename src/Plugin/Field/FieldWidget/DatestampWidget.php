<?php


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
 *   label = @Translation("Apigee Date"),
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
      '#type' => 'datetime',
      '#default_value' => $default_value,
      '#date_date_format' => 'date',
      '#date_time_element' => 'none',
      '#date_year_range' => '1902:2037',
    ];
    $element['value']['#description'] = $this->t('Format: %format. Leave blank to use the time of form submission.', ['%format' => Datetime::formatExample($date_format . ' ' . $time_format)]);

    return $element;
  }

}
