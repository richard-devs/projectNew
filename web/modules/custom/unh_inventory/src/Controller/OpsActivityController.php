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

    $groups = [];

    foreach ($rows as $row) {
      $account = $this->entityTypeManager()
        ->getStorage('user')
        ->load($row->uid);

      $actor = $account ? $account->getDisplayName() : 'Unknown user';

      $group_key = $row->created . '|' . $row->uid;

      if (!isset($groups[$group_key])) {
        $groups[$group_key] = [
          'created' => $row->created,
          'actor' => $actor,
          'changes' => [],
        ];
      }

      if ($row->field_name) {
        $old_value = $row->old_value !== NULL && $row->old_value !== ''
          ? $row->old_value
          : 'Unassigned';

        $new_value = $row->new_value !== NULL && $row->new_value !== ''
          ? $row->new_value
          : 'Empty';

        if ($row->field_name === 'field_task_description' || $row->field_name === 'field_project_description') {
          $old_value = trim(strip_tags($old_value));
          $new_value = trim(strip_tags($new_value));
        }

        $groups[$group_key]['changes'][] = [
          'label' => $this->getFieldLabel($node, $row->field_name),
          'old' => $old_value,
          'new' => $new_value,
          'assignment' => in_array($row->field_name, [
            'field_task_assignee',
            'field_project_owner',
          ], TRUE),
        ];
      }
    }

    $items = [];

    foreach ($groups as $group) {
      $date = \Drupal::service('date.formatter')
        ->format($group['created'], 'custom', 'd M Y, H:i');

      $changes = [];

      foreach ($group['changes'] as $change) {
        $changes[] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['ops-activity-change'],
          ],
          'message' => [
            '#markup' => '<div class="ops-activity-message">' .
              htmlspecialchars($change['label']) .
              ' changed</div>',
          ],
          'values' => [
            '#markup' => '<div class="ops-activity-change-values">' .
              '<div><strong>From:</strong> ' .
              htmlspecialchars($change['old']) .
              '</div>' .
              '<div><strong>To:</strong> ' .
              htmlspecialchars($change['new']) .
              '</div>' .
              ($change['assignment']
                ? '<div class="ops-activity-assigned-by"><strong>Assigned by:</strong> ' .
                  htmlspecialchars($group['actor']) .
                  '</div>'
                : '') .
              '</div>',
          ],
        ];
      }

      $items[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['ops-activity-item'],
        ],
        'header' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['ops-activity-header'],
          ],
          'date' => [
            '#markup' => '<div class="ops-activity-date">' .
              htmlspecialchars($date) .
              '</div>',
          ],
          'actor' => [
            '#markup' => '<div class="ops-activity-actor"><strong>Changed by:</strong> ' .
              htmlspecialchars($group['actor']) .
              '</div>',
          ],
        ],
        'changes' => $changes,
      ];
    }

    $overview_url = Url::fromRoute('entity.node.canonical', [
      'node' => $node->id(),
    ]);

    $back_url = $node->bundle() === 'ops_task'
      ? Url::fromUserInput('/ops/tasks')
      : Url::fromUserInput('/ops/projects');

    return [
      'tabs' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['ops-detail-tabs'],
        ],
        'overview' => [
          '#type' => 'link',
          '#title' => 'Overview',
          '#url' => $overview_url,
          '#attributes' => [
            'class' => ['ops-detail-tab'],
          ],
        ],
        'activity' => [
          '#type' => 'link',
          '#title' => 'Activity / History',
          '#url' => Url::fromRoute('unh_inventory.ops_activity', [
            'node' => $node->id(),
          ]),
          '#attributes' => [
            'class' => ['ops-detail-tab', 'is-active'],
          ],
        ],
      ],
      'print' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['ops-activity-print-top'],
        ],
        'button' => [
          '#type' => 'html_tag',
          '#tag' => 'button',
          '#value' => 'Print',
          '#attributes' => [
            'type' => 'button',
            'class' => ['ops-activity-print'],
            'onclick' => 'window.print();',
          ],
        ],
      ],
      'title' => [
        '#markup' => '<h1 class="ops-activity-title">' .
          htmlspecialchars($node->label()) .
          '</h1>',
      ],
      'activity' => $items ?: [
        '#markup' => '<div class="ops-activity-empty">No activity has been recorded yet.</div>',
      ],
      'back' => [
        '#type' => 'link',
        '#title' => $node->bundle() === 'ops_task'
          ? '← Back to Tasks'
          : '← Back to Projects',
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
