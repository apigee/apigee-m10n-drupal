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

namespace Drupal\apigee_m10n\Plugin\Field\FieldType;

use Apigee\Edge\Api\Monetization\Entity\OrganizationProfile;
use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'apigee_organization' field type.
 *
 * @FieldType(
 *   id = "apigee_organization",
 *   label = @Translation("Apigee organization"),
 *   description = @Translation("Apigee organization"),
 *   default_formatter = "apigee_organization"
 * )
 */
class OrganizationFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('value'))
      ->setDescription(new TranslatableMarkup('The organization object.'))
      ->setComputed(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = new OrganizationProfile([
      "approveTrusted"                => false,
      "approveUntrusted"              => false,
      "billingCycle"                  => "CALENDAR_MONTH",
      "country"                       => "US",
      "currency"                      => "USD",
      "description"                   => $random->sentences(5),
      "hasBillingAdjustment"          => false,
      "hasBroker"                     => false,
      "hasSelfBilling"                => false,
      "hasSeparateInvoiceForProduct"  => false,
      "id"                            => strtolower($random->name()),
      "issueNettingStmt"              => false,
      "logoUrl"                       => "https://upload.wikimedia.org/wikipedia/commons/thumb/a/aa/Apigee_logo.svg/320px-Apigee_logo.svg.png",
      "name"                          => $random->string(),
      "nettingStmtPerCurrency"        => false,
      "regNo"                         => strtolower($random->name()),
      "selfBillingAsExchOrg"          => false,
      "selfBillingForAllDev"          => false,
      "separateInvoiceForFees"        => false,
      "status"                        => "ACTIVE",
      "supportedBillingType"          => "BOTH",
      "taxEngineExternalId"           => strtoupper($random->name()),
      "taxModel"                      => "HYBRID",
      "taxNexus"                      => "US",
      "timezone"                      => "America/Los_Angeles",
    ]);
    return $values;
  }
}
