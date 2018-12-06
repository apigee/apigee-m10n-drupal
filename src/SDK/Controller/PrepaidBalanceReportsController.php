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
 * Class PrepaidBalanceReportsController.
 *
 * @package Drupal\apigee_m10n\Controller
 */
class PrepaidBalanceReportsController extends AbstractController implements PrepaidBalanceReportsControllerInterface {

  /**
   * The organization id.
   *
   * @var string
   */
  protected $organization;

  /**
   * The API client.
   *
   * @var \Apigee\Edge\ClientInterface
   */
  protected $client;

  /**
   * UUID or email address of a developer.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developerId;

  /**
   * PrepaidBalanceReportsController constructor.
   *
   * @param string $developerId
   *   The developer uuid or email.
   * @param string $organization
   *   The organization id.
   * @param \Apigee\Edge\ClientInterface $client
   *   The API Client.
   */
  public function __construct(string $developerId, string $organization, ClientInterface $client) {
    parent::__construct($client);

    $this->developerId = $developerId;
    $this->organization = $organization;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseEndpointUri(): UriInterface {
    return $this->client->getUriFactory()
      ->createUri("/mint/organizations/{$this->organization}/prepaid-balance-reports");
  }

  /**
   * {@inheritdoc}
   */
  public function getReport(\DateTimeImmutable $billingMonth, string $currencyCode): ?string {
    try {
      $data = [
        'billingMonth' => strtoupper($billingMonth->format('F')),
        'billingYear' => $billingMonth->format('Y'),
        'showTxDetail' => TRUE,
        'currCriteria' => [
          [
            'id' => $currencyCode,
            'orgId' => $this->organization,
          ],
        ],
        'devCriteria' => [
          [
            'id' => $this->developerId,
            'orgId' => $this->organization,
          ],
        ],
      ];

      $body = (string) json_encode((object) $data);
      $response = $this->client->post($this->getBaseEndpointUri(), $body, [
        'Accept' => 'application/octet-stream; charset=utf-8',
      ]);

      // TODO: Use ResponseToArrayHelper when we refactor this to apigee-client-php.
      return (string) $response->getBody();

    }
    catch (ClientErrorException $exception) {
      // Log something for now.
      \Drupal::logger('apigee_m10n')->error($exception->getMessage());
    }
  }

}
