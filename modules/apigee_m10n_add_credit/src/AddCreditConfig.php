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

namespace Drupal\apigee_m10n_add_credit;

/**
 * Defines config for add credit.
 */
class AddCreditConfig {

  /**
   * The default name for the `apigee_m10n_add_credit` module.
   *
   * @var string
   */
  public const CONFIG_NAME = 'apigee_m10n_add_credit.config';

  /**
   * The "Always" value for `apigee_m10n_add_credit.config.notify_on`.
   *
   * @var string
   */
  public const NOTIFY_ALWAYS = 'always';

  /**
   * The "Only on error" value for `apigee_m10n_add_credit.config.notify_on`.
   *
   * @var string
   */
  public const NOTIFY_ON_ERROR = 'error_only';

  /**
   * The name of the field for add credit enabled.
   */
  public const ADD_CREDIT_ENABLED_FIELD_NAME = 'apigee_add_credit_enabled';

  /**
   * The name of the field that holds the add credit target value.
   */
  public const TARGET_FIELD_NAME = 'add_credit_target';

}
