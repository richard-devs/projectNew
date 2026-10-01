<?php

namespace Drupal\unh_inventory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class InventoryUsersController extends ControllerBase {

  public function listing() {
    $records = \Drupal::database()->select('unh_inventory_assignment', 'a')
      ->fields('a', ['uid', 'staff_name', 'staff_index_number', 'organization_email', 'asset_nid'])
      ->condition('status', 'active')
      ->orderBy('staff_name', 'ASC')
      ->execute()
      ->fetchAll();

    $users = [];

    foreach ($records as $record) {
      $key = $record->staff_name
        ? 'staff:' . strtolower(trim($record->staff_name))
        : 'uid:' . (int) $record->uid;

      if (!isset($users[$key])) {
        $users[$key] = [
          'uid' => $record->uid,
          'staff_name' => $record->staff_name ?? '',
          'staff_index_number' => $record->staff_index_number ?? '',
          'organization_email' => $record->organization_email ?? '',
          'assets' => [],
        ];
      }

      $users[$key]['assets'][] = $record->asset_nid;
    }

    $rows = [];

    foreach ($users as $person) {
      $staff_name = $person['staff_name'];
      $staff_index_number = $person['staff_index_number'];
      $organization_email = $person['organization_email'];

      if (!$staff_name && !empty($person['uid'])) {
        $user = User::load($person['uid']);

        if ($user) {
          $staff_name = $user->getDisplayName();
          $organization_email = $user->getEmail();
        }
      }

      $assets = [];
      $departments = [];
      $sections = [];
      $issuance_dates = [];

      foreach ($person['assets'] as $nid) {
        $asset = Node::load($nid);

        if ($asset) {
          $device_type = (string) $asset->get('field_asset_device_type')->value;

          $device_type_labels = [
            'laptop' => 'Laptop',
            'desktop' => 'Desktop',
            'docking_station' => 'Docking station',
            'monitor' => 'Monitor',
            'printer' => 'Printer',
            'tablet' => 'Tablet',
            'server' => 'Server',
            'network_equipment' => 'Network equipment',
          ];

          $device_type_label = $device_type_labels[$device_type] ?? ucwords(str_replace('_', ' ', $device_type));

          $assets[] = $device_type_label;

          if ($asset->hasField('field_asset_date_of_issuance')) {
            $issuance_date = trim((string) $asset->get('field_asset_date_of_issuance')->value);
            if ($issuance_date !== '' && !in_array($issuance_date, $issuance_dates, TRUE)) {
              $issuance_dates[] = $issuance_date;
            }
          }

          $department = trim((string) $asset->get('field_asset_department')->value);
          if ($department !== '' && !in_array($department, $departments, TRUE)) {
            $departments[] = $department;
          }

          if ($asset->hasField('field_asset_section')) {
            $section = trim((string) $asset->get('field_asset_section')->value);
            if ($section !== '' && !in_array($section, $sections, TRUE)) {
              $sections[] = $section;
            }
          }
        }
      }

      $rows[] = [
        'user' => $staff_name,
        'index_number' => $staff_index_number,
        'email' => $organization_email,
        'department' => implode(', ', $departments),
        'section' => implode(', ', $sections),
        'date_of_issuance' => implode(', ', $issuance_dates),
        'equipment' => implode(', ', $assets),
        'count' => count($assets),
      ];
    }

    return [
      '#attached' => ['library' => ['unh_inventory/users_visual']],
      'back' => [
        '#markup' => '<div class="unh-inventory-back-row"><a href="/inventory" class="unh-inventory-back-button" style="display:inline-flex !important;align-items:center !important;gap:7px !important;padding:9px 16px !important;background:#000000 !important;background-color:#000000 !important;background-image:none !important;color:#ffffff !important;border:2px solid #000000 !important;border-radius:7px !important;text-decoration:none !important;font-size:13px !important;font-weight:800 !important;">← Back to Inventory</a></div>',
      ],
      'header' => [
        '#markup' => '<div class="unh-inventory-page-header"><div><span class="unh-inventory-page-kicker">PEOPLE & EQUIPMENT</span><h1>Inventory Users</h1><p>View staff and users currently assigned ICTS equipment.</p></div><a href="#" class="unh-inventory-print-button" onclick="window.print(); return false;"><span class="unh-print-icon">🖨</span><span>Print</span></a></div>',
      ],
      'intro' => [
        '#markup' => '<div class="unh-inventory-page-intro"><p>Staff and users currently assigned ICTS equipment.</p></div>',
      ],
      '#cache' => [
        'max-age' => 0,
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          'user' => [
            'data' => [
              '#markup' => 'Staff /<br>User<br>Name',
            ],
          ],
          'index_number' => [
            'data' => [
              '#markup' => 'Index<br>Number',
            ],
          ],
          'email' => [
            'data' => [
              '#markup' => 'Organiza<br>tion /<br>Email',
            ],
          ],
          'department' => [
            'data' => [
              '#markup' => 'Depart<br>ment /<br>Division',
            ],
          ],
          'section' => 'Section',
          'date_of_issuance' => [
            'data' => [
              '#markup' => 'Date of<br>Issuance',
            ],
          ],
          'equipment' => [
            'data' => [
              '#markup' => 'Assigned<br>Equip<br>ment',
            ],
          ],
          'count' => [
            'data' => [
              '#markup' => 'Devices<br>Assigned',
            ],
          ],
        ],
        '#rows' => $rows,
        '#empty' => 'No users currently have ICTS equipment assigned.',
        '#attributes' => [
          'class' => ['unh-inventory-table'],
        ],
      ],
    ];
  }

}
