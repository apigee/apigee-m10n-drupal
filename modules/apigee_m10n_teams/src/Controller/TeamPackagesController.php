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

namespace Drupal\apigee_m10n_teams\Controller;

use Drupal\apigee_edge_teams\Entity\TeamInterface;
use Drupal\apigee_m10n\Controller\PackagesController;
use Drupal\apigee_m10n\Form\PackageConfigForm;
use Drupal\apigee_m10n_teams\Entity\TeamRouteAwarePackage;

/**
 * Generates the packages page.
 *
 * @package Drupal\apigee_m10n\Controller
 */
class TeamPackagesController extends PackagesController {

  /**
   * Gets a list of available packages for this user.
   *
   * @param \Drupal\apigee_edge_teams\Entity\TeamInterface $team
   *   The drupal user/developer.
   *
   * @return array
   *   The pager render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function teamCatalogPage(TeamInterface $team) {
    // Load purchased packages for comparison.
    $packages = TeamRouteAwarePackage::getAvailableApiPackagesByTeam($team->id());
    // Get the view mode from package config.
    $view_mode = $this->config(PackageConfigForm::CONFIG_NAME)->get('catalog_view_mode');
    $build = ['package_list' => $this->entityTypeManager()->getViewBuilder('package')->viewMultiple($packages, $view_mode ?? 'default')];
    $build['package_list']['#pre_render'][] = [$this, 'preRender'];

    return $build;
  }

}
