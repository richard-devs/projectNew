<?php

namespace Drupal\unh_inventory\Controller;

use Drupal\Core\Controller\ControllerBase;

class InventoryController extends ControllerBase {

  public function dashboard() {
    return [
      '#markup' => '
        <div class="unh-inventory">
          <div class="unh-inventory-header">
            <h1>ICTS Inventory</h1>
            <p>Manage ICT equipment, assignments and asset records.</p>
          </div>

          <div class="unh-inventory-grid">

            <div class="unh-inventory-card">
              <h2>Devices</h2>
              <p>Manage laptops, desktops, monitors, docking stations and other ICT equipment.</p>
              <a href="#">Manage Devices</a>
            </div>

            <div class="unh-inventory-card">
              <h2>Users</h2>
              <p>View users and the equipment currently assigned to them.</p>
              <a href="#">View Users</a>
            </div>

            <div class="unh-inventory-card">
              <h2>Assignments</h2>
              <p>Record equipment issued to users and maintain assignment history.</p>
              <a href="#">Manage Assignments</a>
            </div>

            <div class="unh-inventory-card">
              <h2>Reports</h2>
              <p>View inventory summaries and equipment assignment reports.</p>
              <a href="#">View Reports</a>
            </div>

          </div>
        </div>
      ',
    ];
  }

}
