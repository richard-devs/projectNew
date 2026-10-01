<?php

namespace Drupal\unh_inventory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class InventoryDevicesController extends ControllerBase {

  public function listing() {
    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'icts_asset')
      ->sort('title', 'ASC')
      ->execute();

    $nodes = Node::loadMultiple($nids);
    $rows = [];

    foreach ($nodes as $node) {
      $staff_name = '';
      $staff_index_number = '';
      $organization_email = '';
      $assignment_id = NULL;

      $assignment = \Drupal::database()
        ->select('unh_inventory_assignment', 'a')
        ->fields('a', [
          'id',
          'uid',
          'staff_name',
          'staff_index_number',
          'organization_email',
        ])
        ->condition('asset_nid', $node->id())
        ->condition('status', 'active')
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      if ($assignment) {
        $assignment_id = (int) $assignment->id;
        $staff_name = $assignment->staff_name ?? '';
        $staff_index_number = $assignment->staff_index_number ?? '';
        $organization_email = $assignment->organization_email ?? '';

        if (!$staff_name && !empty($assignment->uid)) {
          $user = User::load($assignment->uid);

          if ($user) {
            $staff_name = $user->getDisplayName();
            $organization_email = $user->getEmail();
          }
        }
      }

      $status_value = $node->get('field_asset_status')->value ?? '';

      $status_label = match ($status_value) {
        'available' => 'Available',
        'assigned' => 'Assigned',
        'temporary_assigned' => 'Temporary Assigned',
        'repair' => 'Under repair',
        'retired' => 'Retired',
        'lost' => 'Lost',
        default => $status_value,
      };

      $action_links = [
        '<a class="unh-device-edit-button" href="' .
          $node->toUrl('edit-form')->toString() .
          '">Edit Device</a>',
        '<a class="unh-device-status-button" href="' .
          Url::fromRoute('unh_inventory.device_status', [
            'node' => $node->id(),
          ])->toString() .
          '">Manage Status</a>',
        '<a class="unh-device-delete-button" href="' .
          Url::fromRoute('unh_inventory.device_delete', [
            'node' => $node->id(),
          ])->toString() .
          '">Delete Device</a>',
      ];

      if ($status_value !== 'retired' && $status_value !== 'lost') {
        if ($assignment_id) {
          $reassign_url = Url::fromRoute('unh_inventory.assignment_transfer', [
            'assignment_id' => $assignment_id,
          ])->toString();
        }
        else {
          $reassign_url = Url::fromRoute('unh_inventory.device_assignment', [
            'node' => $node->id(),
          ])->toString();
        }

        $action_links[] =
          '<a class="unh-device-reassign-button" href="' .
          $reassign_url .
          '">Reassign Device</a>';
      }

      $action_links[] =
        '<a class="unh-device-activity-button" href="' .
        Url::fromRoute('unh_inventory.device_activity', [
          'node' => $node->id(),
        ])->toString() .
        '">View Activity</a>';

      $rows[] = [
        'device' => [
          'data' => [
            '#markup' => '<a href="' . $node->toUrl()->toString() . '">' .
              htmlspecialchars(trim(explode('|', $node->label())[0])) .
              '</a>',
          ],
        ],
        'asset_id' => $node->get('field_asset_tag')->value ?? '',
        'category' => match ($node->get('field_asset_device_type')->value ?? '') {
          'laptop' => 'Laptop',
          'desktop' => 'Desktop',
          'docking_station' => 'Docking station',
          'monitor' => 'Monitor',
          'printer' => 'Printer',
          'tablet' => 'Tablet',
          'server' => 'Server',
          'network_equipment' => 'Network equipment',
          default => ucwords(str_replace('_', ' ', $node->get('field_asset_device_type')->value ?? '')),
        },
        'model' => $node->get('field_asset_model')->value ?? '',
        'serial' => strtoupper($node->get('field_asset_serial_number')->value ?? ''),
        'status' => [
          'data' => [
            '#markup' => htmlspecialchars($status_label),
          ],
        ],
        'staff_name' => $staff_name,
        'staff_index_number' => $staff_index_number,
        'organization_email' => $organization_email,
        'department' => $node->get('field_asset_department')->value ?? '',
        'section' => $node->get('field_asset_section')->value ?? '',
        'date_of_issuance' => $node->get('field_asset_date_of_issuance')->value ?? '',
        'location' => $node->get('field_asset_location')->value ?? '',
        'notes' => strip_tags($node->get('field_asset_notes')->value ?? ''),
        'actions' => [
          'data' => [
            '#markup' =>
              '<div class="unh-device-actions">' .
              implode('', $action_links) .
              '</div>',
          ],
        ],
      ];
    }

    return [
      '#attached' => ['library' => ['unh_inventory/devices_visual']],
      'back' => [
        '#markup' => '<div class="unh-inventory-back-row"><a href="/inventory" class="unh-inventory-back-button">← Back to Inventory</a></div>',
      ],
      'header' => [
        '#markup' => '<div class="unh-inventory-page-header"><div><span class="unh-inventory-page-kicker">INVENTORY</span><h1>ICTS Devices</h1><p>View and manage ICT equipment, asset identification and current assignments.</p></div><a href="#" class="unh-inventory-print-button" onclick="window.print(); return false;" style="display:inline-flex !important;align-items:center !important;justify-content:center !important;gap:9px !important;min-width:140px !important;min-height:56px !important;padding:10px 18px !important;background:#e85d4a !important;background-color:#e85d4a !important;background-image:none !important;color:#ffffff !important;border:2px solid #ffffff !important;border-radius:9px !important;font-size:16px !important;font-weight:900 !important;opacity:1 !important;box-shadow:0 5px 16px rgba(0,0,0,.24) !important;text-decoration:none !important;"><span class="unh-print-icon" style="background:transparent !important;background-color:transparent !important;color:#ffffff !important;width:auto !important;height:auto !important;border:0 !important;border-radius:0 !important;font-size:21px !important;padding:0 !important;margin:0 !important;">🖨</span><span style="color:#ffffff !important;">Print</span></a></div>',
      ],
      'actions' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['unh-inventory-actions'],
        ],
        'add_asset' => [
          '#type' => 'link',
          '#title' => $this->t('Add ICTS Asset'),
          '#url' => Url::fromRoute('node.add', [
            'node_type' => 'icts_asset',
          ]),
          '#attributes' => [
            'class' => ['button', 'button--primary', 'unh-inventory-add-button'],
          ],
        ],
        'assign_device' => [
          '#type' => 'link',
          '#title' => $this->t('Assign ICTS Device'),
          '#url' => Url::fromRoute('unh_inventory.assignment_add'),
          '#attributes' => [
            'class' => ['button', 'unh-inventory-assign-button'],
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          'device' => [
            'data' => [
              '#markup' => 'Device Name /<br>Host<br>name',
            ],
          ],
          'asset_id' => [
            'data' => [
              '#markup' => 'Asset ID /<br>Org Barcode<br>No.',
            ],
          ],
          'category' => [
            'data' => [
              '#markup' => 'Device<br>Type',
            ],
          ],
          'model' => 'Model',
          'serial' => [
            'data' => [
              '#markup' => 'Serial<br>No.',
            ],
          ],
          'status' => 'Status',
          'staff_name' => [
            'data' => [
              '#markup' => 'Staff /<br>User',
            ],
          ],
          'index_number' => [
            'data' => [
              '#markup' => 'Index<br>Number',
            ],
          ],
          'organization_email' => [
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
          'date_issuance' => [
            'data' => [
              '#markup' => 'Date of<br>Issuance',
            ],
          ],
          'location' => [
            'data' => [
              '#markup' => 'Org<br>Location',
            ],
          ],
          'notes' => 'Notes',
          'actions' => 'Actions',
        ],
        '#rows' => $rows,
        '#empty' => 'No inventory devices found.',
        '#attributes' => [
          'class' => ['unh-inventory-table'],
        ],
      ],
    ];
  }

}
