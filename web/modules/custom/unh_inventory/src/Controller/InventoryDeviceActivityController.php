<?php

namespace Drupal\unh_inventory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

class InventoryDeviceActivityController extends ControllerBase {

  public function listing(NodeInterface $node) {
    if ($node->bundle() !== 'icts_asset') {
      return [
        '#markup' => '<div class="messages messages--error">Invalid inventory device.</div>',
      ];
    }

    $db = \Drupal::database();
    $rows = [];

    /*
     * 1. Inventory activity log.
     *
     * Assignment/reassignment transactions can create several activity
     * records at the same timestamp:
     * assigned/reassigned, assignment_note, ownership_document_uploaded,
     * and the resulting status_changed record.
     *
     * These are displayed as one transaction rather than separate rows.
     */
    $activity = $db->select('unh_inventory_activity', 'a')
      ->fields('a')
      ->condition('entity_type', 'inventory_device')
      ->condition('entity_id', $node->id())
      ->orderBy('created', 'DESC')
      ->execute()
      ->fetchAll();

    $transactions = [];

    foreach ($activity as $item) {
      $timestamp = (int) $item->created;

      if (!isset($transactions[$timestamp])) {
        $transactions[$timestamp] = [
          'timestamp' => $timestamp,
          'assignment' => NULL,
          'note' => '',
          'document' => FALSE,
          'status' => NULL,
          'other' => [],
        ];
      }

      switch ($item->action) {
        case 'assignment_note':
          $transactions[$timestamp]['note'] =
            trim((string) ($item->new_value ?? ''));
          break;

        case 'ownership_document_uploaded':
          $transactions[$timestamp]['document'] = TRUE;
          break;

        case 'assigned':
        case 'reassigned':
          $transactions[$timestamp]['assignment'] = $item;
          break;

        case 'status_changed':
          $transactions[$timestamp]['status'] = $item;
          break;

        case 'changed':
          if ($item->field_name === 'field_asset_status') {
            continue 2;
          }
          $transactions[$timestamp]['other'][] = $item;
          break;

        default:
          $transactions[$timestamp]['other'][] = $item;
          break;
      }
    }

    foreach ($transactions as $transaction) {
      $timestamp = $transaction['timestamp'];
      $assignment = $transaction['assignment'];

      /*
       * Assignment/reassignment transaction.
       */
      if ($assignment) {
        $changed_by = 'System';

        if (!empty($assignment->uid)) {
          $user = User::load((int) $assignment->uid);
          if ($user) {
            $changed_by = $user->getDisplayName();
          }
        }

        $old_value = (string) ($assignment->old_value ?? '');
        $new_value = (string) ($assignment->new_value ?? '');
        $action = $assignment->action === 'reassigned'
          ? 'Reassigned'
          : 'Assigned';

        $reason = $transaction['note'];

        if ($reason === '' && $transaction['status']) {
          $status_new = (string) ($transaction['status']->new_value ?? '');

          if (strpos($status_new, ' | Reason: ') !== FALSE) {
            [, $reason] = explode(' | Reason: ', $status_new, 2);
          }
        }

        if ($transaction['document']) {
          $reason = $reason
            ? $reason . ' | Ownership / handover document attached'
            : 'Ownership / handover document attached';
        }

        $rows[] = [
          'timestamp' => $timestamp,
          'action' => $action,
          'from' => $old_value,
          'to' => $new_value,
          'by' => $changed_by,
          'reason' => $reason,
          'type' => 'activity',
          'index_number' => (string) ($assignment->staff_index_number ?? ''),
        ];

        continue;
      }

      /*
       * Standalone status change.
       */
      if ($transaction['status']) {
        $item = $transaction['status'];
        $changed_by = 'System';

        if (!empty($item->uid)) {
          $user = User::load((int) $item->uid);
          if ($user) {
            $changed_by = $user->getDisplayName();
          }
        }

        $old_value = (string) ($item->old_value ?? '');
        $new_value = (string) ($item->new_value ?? '');
        $reason = '';

        if (strpos($new_value, ' | Reason: ') !== FALSE) {
          [$new_value, $reason] = explode(' | Reason: ', $new_value, 2);
        }

        $rows[] = [
          'timestamp' => $timestamp,
          'action' => 'Status Changed',
          'from' => $old_value,
          'to' => $new_value,
          'by' => $changed_by,
          'reason' => $reason,
          'type' => 'activity',
          'index_number' => (string) ($item->staff_index_number ?? ''),
        ];
      }

      /*
       * Other independent activity records.
       */
      foreach ($transaction['other'] as $item) {
        $changed_by = 'System';

        if (!empty($item->uid)) {
          $user = User::load((int) $item->uid);
          if ($user) {
            $changed_by = $user->getDisplayName();
          }
        }

        $old_value = (string) ($item->old_value ?? '');
        $new_value = (string) ($item->new_value ?? '');
        $reason = '';

        if (strpos($new_value, ' | Reason: ') !== FALSE) {
          [$new_value, $reason] = explode(' | Reason: ', $new_value, 2);
          $new_value = trim($new_value);
          $reason = trim($reason);
        }

        $action = 'Changed';

        if ($item->action === 'device_added') {
          $stored_value = (string) ($item->new_value ?? '');

          if (strpos($stored_value, ' | Asset ID: ') !== FALSE) {
            [$device_type, $asset_id] = explode(' | Asset ID: ', $stored_value, 2);
            $action = trim($device_type);
            $new_value = 'Asset ID: ' . trim($asset_id);
          }
          else {
            $action = trim($stored_value);
            $new_value = '—';
          }

          if ($node->hasField('field_asset_notes') && !$node->get('field_asset_notes')->isEmpty()) {
            $reason = trim(strip_tags((string) $node->get('field_asset_notes')->value));
          }
        }
        elseif ($item->action === 'changed') {
          $action = 'Changed';
        }
        else {
          $action = ucwords(str_replace('_', ' ', (string) $item->action));
        }

        $rows[] = [
          'timestamp' => (int) $item->created,
          'action' => $action,
          'from' => $old_value !== '' ? $old_value : '—',
          'to' => $new_value !== '' ? $new_value : '—',
          'by' => $changed_by,
          'reason' => $reason !== '' ? $reason : '—',
          'type' => 'activity',
          'index_number' => (string) ($item->staff_index_number ?? ''),
        ];
      }
    }

    /*
     * 2. Legacy assignment history.
     *
     * Current assignments and reassignments are already recorded in
     * unh_inventory_activity. Only genuinely legacy records that have
     * no matching activity entry are shown here.
     */
    $assignments = $db->select('unh_inventory_assignment', 'a')
      ->fields('a')
      ->condition('asset_nid', $node->id())
      ->orderBy('id', 'DESC')
      ->execute()
      ->fetchAll();

    foreach ($assignments as $assignment) {
      $staff = trim((string) ($assignment->staff_name ?? ''));

      if (!$staff && !empty($assignment->uid)) {
        $user = User::load((int) $assignment->uid);
        if ($user) {
          $staff = $user->getDisplayName();
        }
      }

      $staff = $staff ?: 'Unknown';
      $assignment_date = trim((string) ($assignment->assigned_date ?? ''));
      $assignment_notes = trim((string) ($assignment->notes ?? ''));

      $duplicate = FALSE;

      if ($assignment_date) {
        $activity_query = $db->select('unh_inventory_activity', 'x');
        $activity_query->fields('x', [
          'action',
          'new_value',
          'created',
        ]);
        $activity_query->condition('x.entity_type', 'inventory_device');
        $activity_query->condition('x.entity_id', $node->id());
        $activity_query->condition('x.action', ['assigned', 'reassigned'], 'IN');

        foreach ($activity_query->execute() as $activity_item) {
          $activity_date = date('Y-m-d', (int) $activity_item->created);
          $activity_new = trim((string) ($activity_item->new_value ?? ''));

          if (
            $activity_date === $assignment_date &&
            $activity_new === $staff
          ) {
            $duplicate = TRUE;
            break;
          }
        }
      }

      if ($duplicate) {
        continue;
      }

      $assigned_timestamp = $assignment_date
        ? strtotime($assignment_date . ' 00:00:00')
        : 0;

      $reason = $assignment_notes;

      if ($assignment->status === 'returned' && !empty($assignment->returned_date)) {
        $reason = $reason
          ? $reason . ' | Returned: ' . $assignment->returned_date
          : 'Returned: ' . $assignment->returned_date;
      }

      $rows[] = [
        'timestamp' => $assigned_timestamp,
        'action' => 'Legacy Assignment',
        'from' => '',
        'to' => $staff,
        'index_number' => (string) ($assignment->staff_index_number ?? ''),
        'by' => 'Legacy Record',
        'reason' => $reason,
        'type' => 'assignment',
        'organization' => (string) ($assignment->organization_email ?? ''),
      ];
    }

    /*
     * Sort everything together so activity and assignment history
     * appear in chronological order.
     */
    usort($rows, function ($a, $b) {
      return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
    });

    $table_rows = [];

    foreach ($rows as $row) {
      $timestamp = (int) ($row['timestamp'] ?? 0);

      $date = $timestamp ? date('d M Y', $timestamp) : '';
      $time = $timestamp ? date('H:i', $timestamp) : '';

      $to = htmlspecialchars((string) ($row['to'] ?? ''));
      $reason = htmlspecialchars((string) ($row['reason'] ?? ''));

      if (($row['type'] ?? '') === 'assignment' && !empty($row['organization'])) {
        $to .= '<br><small>' .
          htmlspecialchars((string) $row['organization']) .
          '</small>';
      }

      $table_rows[] = [
        'date' => $date,
        'time' => $time,
        'action' => htmlspecialchars((string) ($row['action'] ?? '')),
        'from' => htmlspecialchars((string) ($row['from'] ?? '')),
        'to' => [
          'data' => [
            '#markup' => $to,
          ],
        ],
        'index_number' => htmlspecialchars((string) ($row['index_number'] ?? '')) ?: '—',
        'by' => htmlspecialchars((string) ($row['by'] ?? '')),
        'reason' => $reason,
      ];
    }

    return [
      'back' => [
        '#markup' =>
          '<div class="unh-inventory-back-row">' .
          '<a href="/inventory/devices" class="unh-inventory-back-button">← Back to Devices</a>' .
          '</div>',
      ],

      'header' => [
        '#markup' =>
          '<div class="unh-inventory-page-header">' .
          '<div>' .
          '<span class="unh-inventory-page-kicker">INVENTORY</span>' .
          '<h1>Device Activity</h1>' .
          '<p>Complete history for ' . htmlspecialchars($node->label()) . '.</p>' .
          '</div>' .
          '<a href="#" class="unh-inventory-print-button" onclick="window.print(); return false;">' .
          '<span class="unh-print-icon">🖨</span><span>Print</span>' .
          '</a>' .
          '</div>',
      ],

      'device' => [
        '#markup' =>
          '<div class="unh-device-activity-summary">' .
          '<strong>Device:</strong> ' . htmlspecialchars($node->label()) .
          '</div>',
      ],

      'table' => [
        '#type' => 'table',
        '#header' => [
          'date' => 'Date',
          'time' => 'Time',
          'action' => 'Action',
          'from' => 'Previous Assignee / Value',
          'to' => 'New Assignee / Value',
          'index_number' => 'Index Number',
          'by' => 'Changed By',
          'reason' => 'Reason / Note',
        ],
        '#rows' => $table_rows,
        '#empty' => 'No activity has been recorded for this device.',
        '#attributes' => [
          'class' => [
            'unh-inventory-table',
            'unh-device-activity-table',
          ],
        ],
      ],
    ];
  }

}
