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

namespace Drupal\apigee_m10n;

/**
 * Environment variable keys.
 */
abstract class EnvironmentVariable {
  const APIGEE_EDGE_AUTH_TYPE = 'APIGEE_EDGE_AUTH_TYPE';
  const APIGEE_EDGE_ENDPOINT = 'APIGEE_EDGE_ENDPOINT';
  const APIGEE_EDGE_ORGANIZATION = 'APIGEE_EDGE_ORGANIZATION';
  const APIGEE_EDGE_USERNAME = 'APIGEE_EDGE_USERNAME';
  const APIGEE_EDGE_PASSWORD = 'APIGEE_EDGE_PASSWORD';
  const APIGEE_INTEGRATION_ENABLE = 'APIGEE_INTEGRATION_ENABLE';
  const APIGEE_EDGE_AUTHORIZATION_SERVER = 'APIGEE_EDGE_AUTHORIZATION_SERVER';
  const APIGEE_EDGE_CLIENT_ID = 'APIGEE_EDGE_CLIENT_ID';
  const APIGEE_EDGE_CLIENT_SECRET = 'APIGEE_EDGE_CLIENT_SECRET';

}
