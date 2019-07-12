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

namespace Drupal\apigee_m10n_teams\Form;

use Drupal\apigee_m10n\Form\PrepaidBalanceReportsDownloadForm;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a form to to download prepaid balances.
 */
class TeamPrepaidBalanceReportsDownloadForm extends PrepaidBalanceReportsDownloadForm {

  /**
   * Generate a balance report.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The route entity for the report.
   * @param \DateTimeImmutable $billing_date
   *   The billing date.
   * @param string $currency
   *   The currency for the report.
   *
   * @return null|string
   *   A CSV string of prepaid balances.
   */
  public function getReport(EntityInterface $entity, \DateTimeImmutable $billing_date, $currency) {
    return $this->monetization->getPrepaidBalanceReport($entity->id(), $billing_date, $currency);
  }

}
