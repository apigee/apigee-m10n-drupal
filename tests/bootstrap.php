<?php

/**
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


/**
 * @file
 * Searches for the core bootstrap file.
 */

$dir = __DIR__;

// Match against previous dir for Windows.
$previous_dir = '';

while ($dir = dirname($dir)) {
  // We've reached the root.
  if ($dir === $previous_dir) {
    break;
  }

  $previous_dir = $dir;

  if (is_file($dir . '/core/tests/bootstrap.php')) {
    require_once $dir . '/core/tests/bootstrap.php';
    return;
  }
}

throw new RuntimeException('Unable to load core bootstrap.php.');
