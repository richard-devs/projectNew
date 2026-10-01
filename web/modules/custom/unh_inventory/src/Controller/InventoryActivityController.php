<?php

namespace Drupal\unh_inventory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InventoryActivityController extends ControllerBase {

  protected DateFormatterInterface $dateFormatter;

  public function __construct(DateFormatterInterface $dateFormatter) {
    $this->dateFormatter = $dateFormatter;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  public function listing() {
    $query = \Drupal::database()
      ->select('unh_inventory_activity', 'a');

    $query->leftJoin('users_field_data', 'u', 'u.uid = a.uid');

    $query->fields('a', [
      'id',
      'action',
      'entity_type',
      'entity_id',
      'entity_label',
      'staff_index_number',
      'field_label',
      'old_value',
      'new_value',
      'created',
    ]);

    $query->addField('u', 'name', 'username');
    $query->orderBy('a.created', 'DESC');
    $query->range(0, 500);

    $records = $query->execute()->fetchAll();

    /*
     * Group records by device and exact timestamp.
     *
     * Assignment transactions can create:
     * assigned/reassigned
     * assignment_note
     * ownership_document_uploaded
     * status_changed
     *
     * These belong to one transaction and should be displayed as one row.
     */
    $groups = [];

    foreach ($records as $record) {
      $key = ($record->entity_type ?? '') . ':' .
        ($record->entity_id ?? '') . ':' .
        (int) $record->created;

      if (!isset($groups[$key])) {
        $groups[$key] = [
          'records' => [],
          'assignment' => NULL,
          'note' => '',
          'document' => FALSE,
          'status' => NULL,
        ];
      }

      $groups[$key]['records'][] = $record;

      if (in_array($record->action, ['assigned', 'reassigned'], TRUE)) {
        $groups[$key]['assignment'] = $record;
      }
      elseif ($record->action === 'assignment_note') {
        $groups[$key]['note'] = trim((string) ($record->new_value ?? ''));
      }
      elseif ($record->action === 'ownership_document_uploaded') {
        $groups[$key]['document'] = TRUE;
      }
      elseif ($record->action === 'status_changed') {
        $groups[$key]['status'] = $record;
      }
    }

    $rows = [];

    foreach ($groups as $group) {
      $assignment = $group['assignment'];

      /*
       * Merge a complete assignment/reassignment transaction.
       */
      if ($assignment) {
        $reason_parts = [];

        if ($group['note'] !== '') {
          $reason_parts[] = $group['note'];
        }

        if ($group['status']) {
          $status = $group['status'];
          $status_from = trim((string) ($status->old_value ?? ''));
          $status_to = trim((string) ($status->new_value ?? ''));

          if ($status_to !== '') {
            if (strpos($status_to, ' | Reason: ') !== FALSE) {
              [$status_to, $status_reason] = explode(' | Reason: ', $status_to, 2);
              $status_reason = trim($status_reason);

              if ($status_reason !== '' && $group['note'] === '') {
                $reason_parts[] = $status_reason;
              }
            }
          }
        }

        if ($group['document']) {
          $reason_parts[] = 'Ownership / handover document attached';
        }

        $timestamp = (int) $assignment->created;

        $rows[] = [
          'action' => $this->formatAction($assignment->action),
          'item' => $assignment->entity_label ?: '—',
          'index_number' => $assignment->staff_index_number ?: '—',
          'field' => $assignment->field_label ?: 'Device Assignment',
          'from' => $assignment->old_value !== NULL && $assignment->old_value !== ''
            ? $assignment->old_value
            : '—',
          'to' => $assignment->new_value !== NULL && $assignment->new_value !== ''
            ? $assignment->new_value
            : '—',
          'by' => $assignment->username ?: 'Unknown',
          'day' => $this->dateFormatter->format($timestamp, 'custom', 'l'),
          'date' => $this->dateFormatter->format($timestamp, 'custom', 'd M Y'),
          'time' => $this->dateFormatter->format($timestamp, 'custom', 'H:i:s'),
          'reason' => $reason_parts ? implode(' | ', $reason_parts) : '—',
          '_timestamp' => $timestamp,
        ];

        continue;
      }

      /*
       * Display standalone records normally.
       */
      foreach ($group['records'] as $record) {
        if ($record->action === 'assignment_note' ||
            $record->action === 'ownership_document_uploaded') {
          continue;
        }

        $timestamp = (int) $record->created;

        $old_value = $record->old_value !== NULL && $record->old_value !== ''
          ? $record->old_value
          : '—';

        $new_value = $record->new_value !== NULL && $record->new_value !== ''
          ? $record->new_value
          : '—';

        $reason = '—';
        $activity = $record->field_label ?: '—';

        if (strpos($new_value, ' | Reason: ') !== FALSE) {
          [$new_value, $reason] = explode(' | Reason: ', $new_value, 2);
          $new_value = trim($new_value);
          $reason = trim($reason);
        }

        /*
         * Device-added records use the existing device record for the
         * device type and notes, so older activity records also display
         * correctly without changing the activity database.
         */
        if ($record->action === 'device_added') {
          $stored_value = (string) $record->new_value;

          if (strpos($stored_value, ' | Asset ID: ') !== FALSE) {
            [$activity, $asset_id] = explode(' | Asset ID: ', $stored_value, 2);
            $activity = trim($activity);
            $new_value = 'Asset ID: ' . trim($asset_id);
          }
          else {
            $activity = trim($stored_value);
            $new_value = '—';
          }

          $device = \Drupal\node\Entity\Node::load((int) $record->entity_id);

          if ($device && $device->hasField('field_asset_notes') && !$device->get('field_asset_notes')->isEmpty()) {
            $reason = trim(strip_tags((string) $device->get('field_asset_notes')->value));
          }
        }

        $rows[] = [
          'action' => $this->formatAction($record->action),
          'item' => $record->entity_label ?: '—',
          'index_number' => $record->staff_index_number ?: '—',
          'field' => $activity,
          'from' => $old_value,
          'to' => $new_value,
          'by' => $record->username ?: 'Unknown',
          'day' => $this->dateFormatter->format($timestamp, 'custom', 'l'),
          'date' => $this->dateFormatter->format($timestamp, 'custom', 'd M Y'),
          'time' => $this->dateFormatter->format($timestamp, 'custom', 'H:i:s'),
          'reason' => $reason,
          '_timestamp' => $timestamp,
        ];
      }
    }

    usort($rows, function ($a, $b) {
      return ($b['_timestamp'] ?? 0) <=> ($a['_timestamp'] ?? 0);
    });

    foreach ($rows as &$row) {
      unset($row['_timestamp']);
    }
    unset($row);

    return [
      '#attached' => [
        'library' => ['unh_inventory/activity_visual'],
      ],
      'back' => [
        '#markup' => '<div class="unh-inventory-back-row"><a href="/inventory" class="unh-inventory-back-button">← Back to Inventory</a></div>',
      ],
      'header' => [
        '#markup' => '
          <div class="unh-inventory-page-header">
            <div>
              <span class="unh-inventory-page-kicker">INVENTORY</span>
              <h1>Inventory Activity Logs</h1>
              <p>Review inventory additions, changes, assignments, transfers, returns and report activity.</p>
            </div>
            <a href="#" class="unh-inventory-print-button" onclick="window.print(); return false;" style="appearance:none !important;-webkit-appearance:none !important;background:#009edb !important;background-color:#009edb !important;background-image:none !important;color:#ffffff !important;border:0 !important;border-color:#009edb !important;box-shadow:inset 0 0 0 1000px #009edb !important;text-decoration:none !important;">
              <span class="unh-print-icon" style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;background:rgba(255,255,255,.20);color:#ffffff;border-radius:6px;font-size:18px;line-height:1;">🖨</span><span>Print</span>
            </a>
          </div>
        ',
      ],
      '#cache' => [
        'max-age' => 0,
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          'action' => 'Action',
          'item' => 'Device / Item',
          'index_number' => 'Index Number',
          'field' => 'Activity',
          'from' => 'From',
          'to' => 'To',
          'by' => 'By',
          'day' => 'Day',
          'date' => 'Date',
          'time' => 'Time',
          'reason' => 'Reason / Note',
        ],
        '#rows' => $rows,
        '#empty' => 'No Inventory activity has been recorded yet.',
        '#attributes' => [
          'class' => ['unh-inventory-table', 'unh-inventory-activity-table'],
        ],
      ],
    ];
  }

  protected function formatAction(string $action): string {
    return match ($action) {
      'device_added' => 'Device Added',
      'device_deleted' => 'Device Deleted',
      'changed' => 'Changed',
      'assigned' => 'Assigned',
      'reassigned' => 'Reassigned',
      'returned' => 'Returned',
      'report_print' => 'Report Print',
      default => ucwords(str_replace('_', ' ', $action)),
    };
  }

}
