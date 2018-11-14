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

namespace Drupal\Tests\apigee_m10n\Kernel;

/**
 * Tests the format currency twig filter.
 *
 * @package Drupal\Tests\apigee_m10n\Kernel
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 *
 * @coversDefaultClass \Drupal\apigee_m10n\TwigExtension\FormatCurrencyTwigExtension
 */
class FormatCurrencyTwigFilterTest extends MonetizationKernelTestBase {

  /**
   * The monetization service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * Test template.
   *
   * @var array
   */
  protected $template = [
    '#type' => 'inline_template',
    '#template' => '',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->monetization = $this->container->get('apigee_m10n.monetization');
  }

  /**
   * Test rendering USD.
   */
  public function testRenderFloatAsUsd() {
    $this->template['#template'] = "{{ 10.50 | apigee_m10n_format_currency('USD') }}";

    $output = (string) \Drupal::service('renderer')->renderPlain($this->template);

    $this::assertEquals($output, "$10.50");
  }

  /**
   * Test rendering UAD.
   */
  public function testRenderStringAsAud() {
    $this->template['#template'] = "{{ '10.50' | apigee_m10n_format_currency('AUD') }}";

    $output = (string) \Drupal::service('renderer')->renderPlain($this->template);

    $this::assertEquals($output, "A$10.50");
  }

}
