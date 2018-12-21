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

namespace Drupal\apigee_m10n\Entity\Property;

/**
 * Trait FreemiumPropertyAwareDecoratorTrait.
 */
trait FreemiumPropertyAwareDecoratorTrait {

  /**
   * {@inheritdoc}
   */
  public function getFreemiumDuration(): ?int {
    return $this->decorated->getFreemiumDuration();
  }

  /**
   * {@inheritdoc}
   */
  public function setFreemiumDuration(int $freemiumDuration): void {
    $this->decorated->setFreemiumDuration($freemiumDuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getFreemiumDurationType(): ?string {
    return $this->decorated->getFreemiumDurationType();
  }

  /**
   * {@inheritdoc}
   */
  public function setFreemiumDurationType(string $freemiumDurationType): void {
    $this->decorated->setFreemiumDurationType($freemiumDurationType);
  }

  /**
   * {@inheritdoc}
   */
  public function getFreemiumUnit(): ?int {
    return $this->decorated->getFreemiumUnit();
  }

  /**
   * {@inheritdoc}
   */
  public function setFreemiumUnit(int $freemiumUnit): void {
    $this->decorated->setFreemiumUnit($freemiumUnit);
  }

}
