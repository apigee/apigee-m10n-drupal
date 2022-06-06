<?php

/**
 * Copyright 2020 Google Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

namespace Drupal\Tests\apigee_m10n\Kernel\Form;

use Apigee\Edge\Api\Monetization\Entity\SupportedCurrency;
use Drupal\apigee_m10n\Form\ReportsDownloadForm;
use Drupal\Core\Form\FormState;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the reports download form.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class ReportsDownloadFormTest extends MonetizationKernelTestBase {

  /**
   * Drupal user with access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $developer;

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);

    $this->warmOrganizationCache();
    $this->createAccount([]);
    $this->developer = $this->createAccount([
      'download own reports',
    ]);
  }

  /**
   * Test the form.
   */
  public function testForm() {
    $this->queueSupportedCurrenciesResponse();

    /** @var \Drupal\Core\Form\FormBuilderInterface $builder */
    $builder = \Drupal::service('form_builder');
    /** @var \Drupal\apigee_m10n\Form\ReportsDownloadForm $form */
    $form = $builder->getForm(ReportsDownloadForm::class, $this->developer);

    // Assert entity.
    static::assertEquals($this->developer->getEmail(), $form['entity_id']['#value']);

    // Assert currencies.
    $currency_options = $form['currency']['#options'];
    static::assertCount(3, $currency_options);
    static::assertTrue(!empty($currency_options['usd']));
    static::assertTrue(!empty($currency_options['aud']));

    // Test form validation.
    $this->queueSupportedCurrenciesResponse();
    $form_state = new FormState();
    $form_state->setValue('currency', 'usd');
    $form_state->setValue('from_date', [
      'month' => '12',
      'day' => '27',
      'year' => '2017',
    ]);
    $form_state->setValue('to_date', [
      'month' => '11',
      'day' => '27',
      'year' => '2017',
    ]);
    $builder->submitForm(ReportsDownloadForm::class, $form_state, $this->developer);
    $form_errors = $form_state->getErrors();
    static::assertEquals('The to date cannot be before the from date.', (string) $form_errors['to_date']);

    // Test form submission.
    $this->queueSupportedCurrenciesResponse();
    $this->stack->queueMockResponse([
      'post-revenue-reports.csv.twig',
    ]);
    $form_state = new FormState();
    $form_state->setValue('currency', 'usd');
    $form_state->setValue('from_date', [
      'month' => "12",
      "day" => "3",
      "year" => "2017",
    ]);
    $form_state->setValue('to_date', [
      'month' => "12",
      "day" => "27",
      "year" => "2017",
    ]);
    $builder->submitForm(ReportsDownloadForm::class, $form_state, $this->developer);
    /** @var \Symfony\Component\HttpFoundation\Response $response */
    $response = $form_state->getResponse();
    static::assertEquals(Response::HTTP_OK, $response->getStatusCode());
    static::assertEquals('attachment; filename=revenue-report-2017-12-03-2017-12-27.csv', $response->headers->get('content-disposition'));
    static::assertNotEmpty($response->getContent());
  }

  /**
   * Helper to queue supported currencies response.
   */
  protected function queueSupportedCurrenciesResponse() {
    $this->stack->queueMockResponse([
      'get_supported_currencies' => [
        'currencies' => [
          new SupportedCurrency([
            "description" => "United States Dollars",
            "displayName" => "United States Dollars",
            "id" => "usd",
            "minimumTopupAmount" => 11.0000,
            "name" => "USD",
            "status" => "ACTIVE",
            "virtualCurrency" => FALSE,
          ]),
          new SupportedCurrency([
            "description" => "Australia Dollars",
            "displayName" => "Australia Dollars",
            "id" => "aud",
            "minimumTopupAmount" => 10.0000,
            "name" => "AUD",
            "status" => "ACTIVE",
            "virtualCurrency" => FALSE,
          ]),
        ],
      ],
    ]);
  }

}
