<?php

namespace Drupal\apigee_m10n;

interface MonetizationInterface {

  /**
   * Tests whether the current organization has monetization enabled (a requirement for using this module).
   *
   * @return bool
   */
  function isMonetizationEnabled();
}
