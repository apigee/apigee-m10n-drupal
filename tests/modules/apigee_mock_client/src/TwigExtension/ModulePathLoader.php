<?php
/**
 * Copyright 2018 Google Inc.
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

namespace Drupal\apigee_mock_client\TwigExtension;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Loads templates from the response-templates folder of a module.
 */
class ModulePathLoader extends \Twig_Loader_Filesystem {

  /**
   * Constructs a new FilesystemLoader object .
   *
   * @param string $module_name
   *   The name of the module to load templates for.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct($module_name, ModuleHandlerInterface $module_handler) {
    parent::__construct($module_handler->getModule($module_name)->getPath() . '/response-templates');
  }
}
