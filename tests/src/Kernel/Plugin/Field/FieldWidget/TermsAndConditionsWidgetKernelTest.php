<?php

/*
 * Copyright 2019 Google Inc.
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

namespace Drupal\Tests\apigee_m10n\Kernel\Plugin\Field\FieldWidget;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test the `apigee_tnc_widget` field widget.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class TermsAndConditionsWidgetKernelTest extends BaseWidgetKernelTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $this->warmTnsCache();
  }

  /**
   * Test widget display.
   */
  public function testView() {
    $field_name = 'field_test';
    $field_type = 'apigee_tnc';
    $settings = [
      'default_description' => 'lorem ipsum',
    ];
    $this->createField('node', 'page', $field_name, $field_type, $field_name);
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'page', 'default')
      ->setComponent($field_name, [
        'type' => 'apigee_tnc_widget',
        'settings' => $settings,
      ])
      ->save();

    // Test the node add page for the field widget.
    $response = $this->container
      ->get('http_kernel')
      ->handle(Request::create('node/add/page', 'GET'));
    $this->setRawContent($response->getContent());

    $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    $element = $this->cssSelect('[name="' . $field_name . '[0][value]"]');
    $this->assertNotEmpty($element);
    $element = $element[0];
    $attributes = (array) $element;
    $this->assertEquals('checkbox', $attributes['@attributes']['type']);
  }

}
