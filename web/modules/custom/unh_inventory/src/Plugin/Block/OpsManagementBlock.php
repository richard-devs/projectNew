<?php

namespace Drupal\unh_inventory\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an ICTS Ops Management block.
 *
 * @Block(
 *   id = "unh_ops_management",
 *   admin_label = @Translation("ICTS Ops Management")
 * )
 */
class OpsManagementBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => '
        <div class="unh-ops-management">
          <div class="unh-ops-management-header">
            <div>
              <h2>Ops Management</h2>
              <p>Manage ICT projects, tasks, issues, changes and operational documentation.</p>
            </div>
          </div>

          <div class="unh-ops-management-grid">

            <a class="unh-ops-card" href="/node/add/ops_project">
              <span class="unh-ops-card-title">Projects</span>
              <span class="unh-ops-card-text">Track active ICT projects and initiatives.</span>
            </a>

            <a class="unh-ops-card" href="/node/add/ops_task">
              <span class="unh-ops-card-title">Tasks</span>
              <span class="unh-ops-card-text">Manage operational tasks and assignments.</span>
            </a>

            <a class="unh-ops-card" href="/ops/incidents">
              <span class="unh-ops-card-title">Issues &amp; Incidents</span>
              <span class="unh-ops-card-text">Track operational issues and incidents.</span>
            </a>

            <a class="unh-ops-card" href="/ops/changes">
              <span class="unh-ops-card-title">Changes &amp; Releases</span>
              <span class="unh-ops-card-text">Manage planned changes and ICT releases.</span>
            </a>

            <a class="unh-ops-card" href="/ops/documentation">
              <span class="unh-ops-card-title">Documentation</span>
              <span class="unh-ops-card-text">Access operational procedures and references.</span>
            </a>

            <a class="unh-ops-card" href="/ops/dashboard">
              <span class="unh-ops-card-title">Dashboard</span>
              <span class="unh-ops-card-text">View an overall operational status.</span>
            </a>

          </div>
        </div>
      ',
    ];
  }

}
