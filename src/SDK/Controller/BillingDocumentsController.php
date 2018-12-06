<?php

/*
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Drupal\apigee_m10n\SDK\Controller;

use Apigee\Edge\ClientInterface;
use Apigee\Edge\Controller\AbstractController;
use Apigee\Edge\Exception\ClientErrorException;
use Psr\Http\Message\UriInterface;

/**
 * TODO: Extract this to the apigee-client-php package.
 *
 * Class BillingDocumentsController.
 *
 * @package Drupal\apigee_m10n\Controller
 */
class BillingDocumentsController extends AbstractController implements BillingDocumentsControllerInterface {

  /**
   * The organization id.
   *
   * @var string
   */
  protected $organization;

  /**
   * The API Client.
   *
   * @var \Apigee\Edge\ClientInterface
   */
  protected $client;

  /**
   * BillingDocumentsController constructor.
   *
   * @param string $organization
   *   The organization id.
   * @param \Apigee\Edge\ClientInterface $client
   *   The API Client.
   */
  public function __construct(string $organization, ClientInterface $client) {
    parent::__construct($client);

    $this->organization = $organization;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseEndpointUri(): UriInterface {
    return $this->client->getUriFactory()->createUri("/mint/organizations/{$this->organization}/billing-documents-months");
  }

  /**
   * {@inheritdoc}
   */
  public function getEntities(): ?array {
    try {
      $response = $this->client->get($this->getBaseEndpointUri());

      // TODO: Use ResponseToArrayHelper when we refactor this to apigee-client-php.
      return json_decode((string) $response->getBody());

    }
    catch (ClientErrorException $exception) {
      // Log something for now.
      \Drupal::logger('apigee_m10n')->error($exception->getMessage());
    }
  }

}
