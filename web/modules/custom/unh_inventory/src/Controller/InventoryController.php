<?php

namespace Drupal\unh_inventory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class InventoryController extends ControllerBase {

  public function dashboard() {
    $db = \Drupal::database();

    $total = (int) $db->select('node_field_data', 'n')
      ->condition('n.type', 'icts_asset')
      ->countQuery()
      ->execute()
      ->fetchField();

    $status_query = $db->select('node__field_asset_status', 's');
    $status_query->addExpression(
      "SUM(CASE WHEN s.field_asset_status_value = 'available' THEN 1 ELSE 0 END)",
      'available'
    );
    $status_query->addExpression(
      "SUM(CASE WHEN s.field_asset_status_value IN ('assigned', 'temporary_assigned') THEN 1 ELSE 0 END)",
      'assigned'
    );

    $status_counts = $status_query->execute()->fetchAssoc();

    $available = (int) ($status_counts['available'] ?? 0);
    $assigned = (int) ($status_counts['assigned'] ?? 0);

    $users = (int) $db->select('unh_inventory_assignment', 'a')
      ->condition('a.status', 'active')
      ->countQuery()
      ->execute()
      ->fetchField();

    return [
      '#markup' => '
        <div class="unh-inventory">

          <div class="unh-inventory-header">
            <div class="unh-inventory-back-row">
              <a href="/" class="unh-inventory-back-button">← Back to Home</a>
            </div>
            <h1>ICTS Inventory</h1>
            <p>Manage ICT equipment, assignments and asset records.</p>
          </div>

          <div class="unh-inventory-summary">

            <div class="unh-inventory-stat">
              <span class="label">Total devices</span>
              <strong>' . $total . '</strong>
            </div>

            <div class="unh-inventory-stat">
              <span class="label">Available</span>
              <strong>' . $available . '</strong>
            </div>

            <div class="unh-inventory-stat">
              <span class="label">Assigned</span>
              <strong>' . $assigned . '</strong>
            </div>

            <div class="unh-inventory-stat">
              <span class="label">Users with equipment</span>
              <strong>' . $users . '</strong>
            </div>

          </div>

          <div class="unh-inventory-activity-banner">
            <div>
              <span class="unh-inventory-activity-kicker">INVENTORY AUDIT</span>
              <h2>Activity Logs</h2>
              <p>Track inventory additions, changes, assignments, transfers, returns and report activity.</p>
            </div>
            <a href="/inventory/activity" class="unh-inventory-activity-button">View Activity Logs <span>→</span></a>
          </div>

          <div class="unh-inventory-section-heading">
            <h2>Inventory Management</h2>
            <p>Access devices, users, assignments and reporting.</p>
          </div>

          <div class="unh-inventory-grid">

            <div class="unh-inventory-card">
              <div class="unh-inventory-card-top">
                <span class="unh-inventory-card-icon">▣</span>
                <h2>Devices</h2>
              </div>
              <p>Manage laptops, desktops, monitors, docking stations and other ICT equipment.</p>
              <a href="/inventory/devices">Manage Devices <span>→</span></a><a href="/inventory/import" class="unh-inventory-import-link">Import Devices <span>→</span></a>
            </div>

            <div class="unh-inventory-card">
              <div class="unh-inventory-card-top">
                <span class="unh-inventory-card-icon">●</span>
                <h2>Users</h2>
              </div>
              <p>View users and the equipment currently assigned to them.</p>
              <a href="/inventory/users">View Users <span>→</span></a>
            </div>

            <div class="unh-inventory-card">
              <div class="unh-inventory-card-top">
                <span class="unh-inventory-card-icon">↔</span>
                <h2>Assignments</h2>
              </div>
              <p>Issue equipment to users and maintain assignment history.</p>
              <a href="/inventory/assignments/add">Manage Assignments <span>→</span></a>
            </div>

            <div class="unh-inventory-card">
              <div class="unh-inventory-card-top">
                <span class="unh-inventory-card-icon">▤</span>
                <h2>Reports</h2>
              </div>
              <p>View inventory totals, device status and assignment summaries.</p>
                <a href="/inventory/reports">View Reports <span>→</span></a>
              </div>

            </div>

              
            </div>

          </div>

        </div>
      ',
    ];
  }

}
