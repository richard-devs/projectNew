<?php

namespace Drupal\unh_inventory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OpsActivityController extends ControllerBase {

  /**
   * Displays activity history for an ICTS Ops project or task.
   */
  public function history(NodeInterface $node) {
    if (!in_array($node->bundle(), ['ops_project', 'ops_task'], TRUE)) {
      throw new NotFoundHttpException();
    }

    $rows = \Drupal::database()
      ->select('unh_ops_activity', 'a')
      ->fields('a')
      ->condition('a.entity_type', 'node')
      ->condition('a.entity_id', $node->id())
      ->orderBy('a.created', 'DESC')
      ->orderBy('a.id', 'DESC')
      ->execute()
      ->fetchAll();

    $items = [];

    foreach ($rows as $row) {
      $account = $this->entityTypeManager()
        ->getStorage('user')
        ->load($row->uid);

      $actor = $account ? $account->getDisplayName() : 'Unknown user';

      $field_label = $row->field_name
        ? $this->getFieldLabel($node, $row->field_name)
        : 'Activity';

      $old_value = $row->old_value !== NULL && $row->old_value !== ''
        ? $row->old_value
        : 'Empty';

      $new_value = $row->new_value !== NULL && $row->new_value !== ''
        ? $row->new_value
        : 'Empty';

      if (in_array($row->field_name, [
        'field_task_description',
        'field_project_description',
      ], TRUE)) {
        $old_value = trim(strip_tags($old_value));
        $new_value = trim(strip_tags($new_value));
      }

      $day = \Drupal::service('date.formatter')
        ->format($row->created, 'custom', 'l');

      $date = \Drupal::service('date.formatter')
        ->format($row->created, 'custom', 'd M Y');

      $time = \Drupal::service('date.formatter')
        ->format($row->created, 'custom', 'H:i');

      $items[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['ops-activity-item'],
        ],
        'field' => [
          '#markup' => '<div class="ops-activity-message">' .
            htmlspecialchars($field_label) .
            ' changed</div>',
        ],
        'values' => [
          '#markup' => '<div class="ops-activity-change-values">' .
            '<div><strong>From:</strong> ' .
            htmlspecialchars($old_value) .
            '</div>' .
            '<div><strong>To:</strong> ' .
            htmlspecialchars($new_value) .
            '</div>' .
            '<div><strong>Changed by:</strong> ' .
            htmlspecialchars($actor) .
            '</div>' .
            '</div>',
        ],
        'date' => [
          '#markup' => '<div class="ops-activity-date-details">' .
            '<span><strong>Day:</strong> ' .
            htmlspecialchars($day) .
            '</span>' .
            '<span><strong>Date:</strong> ' .
            htmlspecialchars($date) .
            '</span>' .
            '<span><strong>Time:</strong> ' .
            htmlspecialchars($time) .
            '</span>' .
            '</div>',
        ],
      ];
    }

    $back_url = $node->bundle() === 'ops_task'
      ? Url::fromUserInput('/ops/tasks')
      : Url::fromUserInput('/ops/projects');

    $back_title = $node->bundle() === 'ops_task'
      ? '← Back to Tasks'
      : '← Back to Projects';

    return [
      'page_header' => [
        '#markup' => '<div class="unh-inventory-page-header ops-activity-page-header">
          <div>
            <span class="unh-inventory-page-kicker">UN-HABITAT</span>
            <h1>ICTS Activity History</h1>
            <p>Review the operational activity and change history for ' .
              htmlspecialchars($node->label()) .
              '.</p>
          </div>
          <a href="#" class="unh-inventory-print-button ops-activity-print" onclick="window.print(); return false;">
            <span class="unh-print-icon">🖨</span><span>Print</span>
          </a>
        </div>',
      ],

      'title' => [
        '#markup' => '<h2 class="ops-activity-title">' .
          htmlspecialchars($node->label()) .
          '</h2>',
      ],

      'history' => $items ?: [
        '#markup' => '<div class="ops-activity-empty">No activity has been recorded yet.</div>',
      ],

      'back' => [
        '#type' => 'link',
        '#title' => $back_title,
        '#url' => $back_url,
        '#attributes' => [
          'class' => ['ops-activity-back'],
        ],
      ],
    ];
  }

  /**
   * Gets the human readable field label.
   */
  protected function getFieldLabel(NodeInterface $node, string $field_name): string {
    if ($node->hasField($field_name)) {
      return (string) $node->getFieldDefinition($field_name)->getLabel();
    }

    return $field_name;
  }

}
