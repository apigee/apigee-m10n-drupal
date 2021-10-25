/*
 * Copyright 2021 Google Inc.
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

/**
 * @file
 * Allow only 2 decimal points for price field.
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.apigee_m10n_add_credit_price = {
    attach: function attach(context) {
      var self = this;
      $(".two_decimal_price div input").keyup(function(){
      var number = ($(this).val().split('.'));
        if (number[1].length > 2)
        {
          var price = parseFloat($(this).val());
          $(this).val(price.toFixed(2));
        }
      });
    },
  };
})(jQuery, Drupal);
