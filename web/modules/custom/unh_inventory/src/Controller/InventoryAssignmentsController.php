<?php

namespace Drupal\unh_inventory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class InventoryAssignmentsController extends ControllerBase {

  public function listing() {
    $records = \Drupal::database()->select('unh_inventory_assignment', 'a')
      ->fields('a')
      ->orderBy('id', 'DESC')
      ->execute()
      ->fetchAllAssoc('id');

    $rows = [];

    foreach ($records as $record) {
      $asset = Node::load($record->asset_nid);

      $asset_name = $asset ? $asset->label() : 'Unknown device';

      $user_name = $record->staff_name ?: '';
      $user_index_number = $record->staff_index_number ?: '';
      $user_email = $record->organization_email ?: '';

      if (!$user_name && !empty($record->uid)) {
        $user = User::load($record->uid);

        if ($user) {
          $user_name = $user->getDisplayName();
          $user_email = $user->getEmail();
        }
      }

      $user_name = $user_name ?: 'Unknown user';
      $user_email = $user_email ?: '';

      $status_labels = [
        'active' => 'Assigned',
        'returned' => 'Returned',
      ];

      $action = '';

      if ($record->status === 'active') {
        $action = [
          'data' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['unh-inventory-assignment-actions'],
            ],
            'transfer' => [
              '#type' => 'link',
              '#title' => 'Transfer Device',
              '#url' => Url::fromRoute('unh_inventory.assignment_transfer', [
                'assignment_id' => $record->id,
              ]),
            ],
            'return' => [
              '#type' => 'link',
              '#title' => 'Return Device',
              '#url' => Url::fromRoute('unh_inventory.assignment_return', [
                'assignment_id' => $record->id,
              ]),
            ],
          ],
        ];
      }

      $rows[] = [
        'asset' => $asset_name,
        'user' => $user_email
          ? $user_name . ' | ' . $user_email
          : $user_name,
        'index_number' => $user_index_number ?: '—',
        'assigned' => $record->assigned_date ?: '',
        'returned' => $record->returned_date ?: '—',
        'status' => $status_labels[$record->status] ?? $record->status,
        'notes' => $record->notes ?: '',
        'action' => $action,
      ];
    }

    return [
      'back' => [
        '#markup' => '<div class="unh-inventory-back-row"><a href="/inventory" class="unh-inventory-back-button">← Back to Inventory</a></div>',
      ],
      'header' => [
        '#markup' => '<div class="unh-inventory-page-header"><div><span class="unh-inventory-page-kicker">EQUIPMENT CONTROL</span><h1>Device Assignments</h1><p>Issue, transfer and return ICTS equipment while maintaining assignment history.</p></div><a href="#" class="unh-inventory-print-button" onclick="window.print(); return false;"><span class="unh-print-icon">🖨</span><span>Print</span></a></div>',
      ],
      'actions' => [
        '#markup' => '<div class="unh-inventory-actions"><a class="button button--primary" href="/inventory/assignments/add">Assign ICTS Device</a></div>',
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          'asset' => 'Device',
          'user' => 'Staff / User',
          'index_number' => 'Index Number',
          'assigned' => 'Assigned',
          'returned' => 'Returned',
          'status' => 'Status',
          'notes' => 'Notes',
          'action' => 'Action',
        ],
        '#rows' => $rows,
        '#empty' => 'No device assignments have been recorded yet.',
        '#attributes' => [
          'class' => ['unh-inventory-table'],
        ],
      ],
    ];
  }

}
