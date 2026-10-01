<?php

namespace Drupal\unh_inventory\Controller;

use Drupal\Core\Controller\ControllerBase;

class InventoryReportsController extends ControllerBase {

  public function dashboard() {
    $db = \Drupal::database();

    $statuses = [
      'available' => 'Available',
      'assigned' => 'Assigned',
      'repair' => 'Under repair',
      'retired' => 'Retired',
      'lost' => 'Lost',
    ];

    $status_counts = array_fill_keys(array_keys($statuses), 0);

    $assets = $db->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('n.type', 'icts_asset')
      ->condition('n.status', 1)
      ->execute()
      ->fetchAll();

    $get_field_value = function ($table, $column, $nid) use ($db) {
      if (!$db->schema()->tableExists($table)) {
        return '';
      }

      return trim((string) $db->select($table, 'f')
        ->fields('f', [$column])
        ->condition('f.entity_id', $nid)
        ->range(0, 1)
        ->execute()
        ->fetchField());
    };

    $department_counts = [];

    foreach (unh_inventory_department_options() as $department) {
      $department_counts[$department] = array_fill_keys(array_keys($statuses), 0);
    }

    $device_type_counts = [];
    $department_device_counts = [];
    $department_section_device_counts = [];

    foreach ($assets as $asset_row) {
      $nid = (int) $asset_row->nid;

      $status = $get_field_value(
        'node__field_asset_status',
        'field_asset_status_value',
        $nid
      );

      if (!isset($status_counts[$status])) {
        continue;
      }

      $status_counts[$status]++;

      $department = $get_field_value(
        'node__field_asset_department',
        'field_asset_department_value',
        $nid
      );

      if ($department === '' || !isset($department_counts[$department])) {
        $department = 'Unspecified';
      }

      $department_counts[$department][$status]++;

      $device_type = $get_field_value(
        'node__field_asset_device_type',
        'field_asset_device_type_value',
        $nid
      );

      $device_type_label = $device_type;

      if ($device_type !== '') {
        $device_type_definition = \Drupal::service('entity_field.manager')
          ->getFieldDefinitions('node', 'icts_asset')['field_asset_device_type'];

        $allowed_values = $device_type_definition->getSetting('allowed_values');

        if (isset($allowed_values[$device_type])) {
          $device_type_label = $allowed_values[$device_type];
        }
      }

      if ($device_type_label === '') {
        $device_type_label = 'Unspecified';
      }

      if (!isset($device_type_counts[$device_type_label])) {
        $device_type_counts[$device_type_label] = array_fill_keys(
          array_keys($statuses),
          0
        );
      }

      $device_type_counts[$device_type_label][$status]++;

      if (!isset($department_device_counts[$department])) {
        $department_device_counts[$department] = [];
      }

      if (!isset($department_device_counts[$department][$device_type_label])) {
        $department_device_counts[$department][$device_type_label] = array_fill_keys(
          array_keys($statuses),
          0
        );
      }

      $department_device_counts[$department][$device_type_label][$status]++;

      $section = $get_field_value(
        'node__field_asset_section',
        'field_asset_section_value',
        $nid
      );

      if ($section === '') {
        $section = 'Unspecified';
      }

      if (!isset($department_section_device_counts[$department])) {
        $department_section_device_counts[$department] = [];
      }

      if (!isset($department_section_device_counts[$department][$section])) {
        $department_section_device_counts[$department][$section] = [];
      }

      if (!isset($department_section_device_counts[$department][$section][$device_type_label])) {
        $department_section_device_counts[$department][$section][$device_type_label] = array_fill_keys(
          array_keys($statuses),
          0
        );
      }

      $department_section_device_counts[$department][$section][$device_type_label][$status]++;
    }

    $rows = [];

    foreach ($statuses as $key => $label) {
      $rows[] = [
        'status' => $label,
        'count' => $status_counts[$key],
      ];
    }

    $total = count($assets);

    $assignments = (int) $db->select('unh_inventory_assignment', 'a')
      ->condition('a.status', 'active')
      ->countQuery()
      ->execute()
      ->fetchField();

    $department_rows = [];

    foreach ($department_counts as $department => $counts) {
      $department_rows[] = [
        'department' => $department,
        'available' => $counts['available'],
        'assigned' => $counts['assigned'],
        'under_repair' => $counts['repair'],
        'retired' => $counts['retired'],
        'lost' => $counts['lost'],
      ];
    }

    $device_type_rows = [];

    foreach ($device_type_counts as $device_type => $counts) {
      $device_type_rows[] = [
        'device_type' => $device_type,
        'available' => $counts['available'],
        'assigned' => $counts['assigned'],
        'under_repair' => $counts['repair'],
        'retired' => $counts['retired'],
        'lost' => $counts['lost'],
        'total' => array_sum($counts),
      ];
    }

    uasort($device_type_rows, function ($a, $b) {
      return strnatcasecmp($a['device_type'], $b['device_type']);
    });

    $department_device_rows = [];

    foreach ($department_device_counts as $department => $device_types) {
      foreach ($device_types as $device_type => $counts) {
        $department_device_rows[] = [
          'department' => $department,
          'device_type' => $device_type,
          'available' => $counts['available'],
          'assigned' => $counts['assigned'],
          'under_repair' => $counts['repair'],
          'retired' => $counts['retired'],
          'lost' => $counts['lost'],
          'total' => array_sum($counts),
        ];
      }
    }

    usort($department_device_rows, function ($a, $b) {
      $department_compare = strnatcasecmp($a['department'], $b['department']);

      if ($department_compare !== 0) {
        return $department_compare;
      }

      return strnatcasecmp($a['device_type'], $b['device_type']);
    });

    $department_section_device_rows = [];

    foreach ($department_section_device_counts as $department => $sections) {
      foreach ($sections as $section => $device_types) {
        foreach ($device_types as $device_type => $counts) {
          $department_section_device_rows[] = [
            'department' => $department,
            'section' => $section,
            'device_type' => $device_type,
            'available' => $counts['available'],
            'assigned' => $counts['assigned'],
            'total' => $counts['available'] + $counts['assigned'],
          ];
        }
      }
    }

    usort($department_section_device_rows, function ($a, $b) {
      $department_compare = strnatcasecmp($a['department'], $b['department']);

      if ($department_compare !== 0) {
        return $department_compare;
      }

      $section_compare = strnatcasecmp($a['section'], $b['section']);

      if ($section_compare !== 0) {
        return $section_compare;
      }

      return strnatcasecmp($a['device_type'], $b['device_type']);
    });

    return [
      'back' => [
        '#attached' => ['library' => ['unh_inventory/reports_visual']],
        '#markup' => '<div class="unh-inventory-back-row"><a href="/inventory" class="unh-inventory-back-button" style="background:#000000 !important;background-color:#000000 !important;background-image:none !important;">← Back to Inventory</a></div>',
      ],
      '#markup' => '<div class="unh-inventory-page">
    <div class="unh-inventory-print-row"
     style="display:flex;justify-content:flex-end;margin:0 0 12px;padding:0;">
  <a href="#"
   class="unh-inventory-print-button"
   onclick="window.print(); return false;"
   style="display:inline-flex !important;align-items:center !important;justify-content:center !important;gap:7px !important;min-width:160px !important;padding:12px 32px !important;background:#000000 !important;background-color:#000000 !important;background-image:none !important;color:#ffffff !important;border:2px solid #000000 !important;border-radius:8px !important;text-decoration:none !important;font-family:Arial,Helvetica,sans-serif !important;font-size:18px !important;font-weight:800 !important;box-shadow:0 3px 9px rgba(0,0,0,.14) !important;">
  Print
</a>
</div>

    <div class="unh-reports-top">
        <div class="unh-inventory-page-header">
          <div>
            <h1>ICTS Inventory Reports</h1>
            <p>Inventory status and assignment overview.</p>
          </div>
        </div>

        <div class="unh-inventory-summary">
          <div class="unh-inventory-stat">
            <span class="label">Total devices</span>
            <strong>' . $total . '</strong>
          </div>
          <div class="unh-inventory-stat">
            <span class="label">Active assignments</span>
            <strong>' . $assignments . '</strong>
          </div>
        </div>
        </div>',

      'status_heading' => [
        '#markup' => '<div class="unh-inventory-report-section-heading"><h2>Overall Inventory Status</h2><p>Device status across all departments / divisions.</p></div>',
      ],

      'table' => [
        '#type' => 'table',
        '#header' => [
          'status' => 'Devices status',
          'count' => 'Count',
        ],
        '#rows' => $rows,
        '#attributes' => [
          'class' => ['unh-inventory-report-table'],
        ],
      ],

      'department_heading' => [
        '#markup' => '<div class="unh-inventory-report-section-heading"><h2>Department / Division Devices</h2><p>Device status broken down by department / division.</p></div>',
      ],

      'department_table' => [
        '#type' => 'table',
        '#header' => [
          'department' => 'Department / Division',
          'available' => 'Available',
          'assigned' => 'Assigned',
          'under_repair' => 'Under repair',
          'retired' => 'Retired',
          'lost' => 'Lost',
        ],
        '#rows' => $department_rows,
        '#empty' => 'No Department / Division values have been recorded.',
        '#attributes' => [
          'class' => ['unh-inventory-report-table'],
        ],
      ],

      'device_type_heading' => [
        '#markup' => '<div class="unh-inventory-report-section-heading"><h2>Device Type Summary</h2><p>Inventory counts by equipment type and status.</p></div>',
      ],

      'device_type_table' => [
        '#type' => 'table',
        '#header' => [
          'device_type' => 'Device Type',
          'available' => 'Available',
          'assigned' => 'Assigned',
          'under_repair' => 'Under repair',
          'retired' => 'Retired',
          'lost' => 'Lost',
          'total' => 'Total',
        ],
        '#rows' => $device_type_rows,
        '#empty' => 'No device types have been recorded.',
        '#attributes' => [
          'class' => ['unh-inventory-report-table'],
        ],
      ],

      'department_device_heading' => [
        '#markup' => '<div class="unh-inventory-report-section-heading"><h2>Device Type by Department / Division</h2><p>Equipment type and status broken down by department / division.</p></div>',
      ],

      'department_device_table' => [
        '#type' => 'table',
        '#header' => [
          'department' => 'Department / Division',
          'device_type' => 'Device Type',
          'available' => 'Available',
          'assigned' => 'Assigned',
          'under_repair' => 'Under repair',
          'retired' => 'Retired',
          'lost' => 'Lost',
          'total' => 'Total',
        ],
        '#rows' => $department_device_rows,
        '#empty' => 'No department / device type combinations have been recorded.',
        '#attributes' => [
          'class' => ['unh-inventory-report-table'],
        ],
      ],

      'department_section_device_heading' => [
        '#markup' => '<div class="unh-inventory-report-section-heading"><h2>Device Type by Department / Section</h2><p>Equipment type and availability broken down by department / division and section.</p></div>',
      ],

      'department_section_device_table' => [
        '#type' => 'table',
        '#header' => [
          'department' => 'Department / Division',
          'section' => 'Section',
          'device_type' => 'Device Type',
          'available' => 'Available',
          'assigned' => 'Assigned',
          'total' => 'Total',
        ],
        '#rows' => $department_section_device_rows,
        '#empty' => 'No department / section / device type combinations have been recorded.',
        '#attributes' => [
          'class' => ['unh-inventory-report-table'],
        ],
      ],

      'footer' => [
        '#markup' => '</div>',
      ],
    ];
  }

}
