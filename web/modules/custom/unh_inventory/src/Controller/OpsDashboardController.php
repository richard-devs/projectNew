<?php

namespace Drupal\unh_inventory\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides the ICTS Ops Management dashboard.
 */
class OpsDashboardController extends ControllerBase {

  /**
   * Displays the Ops Management dashboard.
   */
  public function dashboard() {
    $project_count = $this->countNodes('ops_project');
    $task_count = $this->countNodes('ops_task');
    $incident_count = $this->countNodes('ops_incident');

    $project_statuses = [
      'planned' => 'Planned',
      'in_progress' => 'In Progress',
      'on_hold' => 'On Hold',
      'completed' => 'Completed',
      'cancelled' => 'Cancelled',
    ];

    $task_statuses = [
      'not_started' => 'Not started',
      'in_progress' => 'In progress',
      'on_hold' => 'On hold',
      'completed' => 'Completed',
      'cancelled' => 'Cancelled',
    ];

    $priorities = [
      'low' => 'Low',
      'medium' => 'Medium',
      'high' => 'High',
      'critical' => 'Critical',
    ];

    $project_status_counts = [];
    foreach ($project_statuses as $value => $label) {
      $project_status_counts[$value] = $this->countNodesByField(
        'ops_project',
        'field_project_status',
        $value
      );
    }

    $task_status_counts = [];
    foreach ($task_statuses as $value => $label) {
      $task_status_counts[$value] = $this->countNodesByField(
        'ops_task',
        'field_task_status',
        $value
      );
    }

    $project_priority_counts = [];
    foreach ($priorities as $value => $label) {
      $project_priority_counts[$value] = $this->countNodesByField(
        'ops_project',
        'field_project_priority',
        $value
      );
    }

    $task_priority_counts = [];
    foreach ($priorities as $value => $label) {
      $task_priority_counts[$value] = $this->countNodesByField(
        'ops_task',
        'field_task_priority',
        $value
      );
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['icts-ops-dashboard'],
      ],

      'header' => [
        '#markup' => '
          <div class="icts-ops-dashboard-header">
            <h1>ICTS Ops Management</h1>
            <p>Monitor and manage ICT projects, operational tasks and issues.</p>
          </div>
        ',
      ],

      'quick_actions' => [
        '#markup' => '
          <div class="icts-ops-dashboard-actions">
            <a href="/node/add/ops_project" class="ops-dashboard-action primary">
              Create Project
            </a>
            <a href="/node/add/ops_task" class="ops-dashboard-action">
              Create Task
            </a>
            <a href="/ops/projects" class="ops-dashboard-action">
              View Projects
            </a>
            <a href="/ops/tasks" class="ops-dashboard-action">
              View Tasks
            </a>
          </div>
        ',
      ],

      'summary' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['icts-ops-dashboard-summary'],
        ],

        'projects' => [
          '#markup' => '
            <div class="icts-ops-dashboard-card">
              <div class="icts-ops-dashboard-card-title">Projects</div>
              <div class="icts-ops-dashboard-card-count">' . $project_count . '</div>
              <div class="icts-ops-dashboard-card-text">Active ICT projects and initiatives.</div>
              <a href="/ops/projects">View Projects</a>
            </div>
          ',
        ],

        'tasks' => [
          '#markup' => '
            <div class="icts-ops-dashboard-card">
              <div class="icts-ops-dashboard-card-title">Tasks</div>
              <div class="icts-ops-dashboard-card-count">' . $task_count . '</div>
              <div class="icts-ops-dashboard-card-text">Operational tasks and assignments.</div>
              <a href="/ops/tasks">View Tasks</a>
            </div>
          ',
        ],

        'incidents' => [
          '#markup' => '
            <div class="icts-ops-dashboard-card">
              <div class="icts-ops-dashboard-card-title">Issues &amp; Incidents</div>
              <div class="icts-ops-dashboard-card-count">' . $incident_count . '</div>
              <div class="icts-ops-dashboard-card-text">Operational issues requiring attention.</div>
              <a href="/ops/incidents">View Issues</a>
            </div>
          ',
        ],
      ],

      'details' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['icts-ops-dashboard-details'],
        ],

        'project_status' => [
          '#markup' => $this->buildStatusSection(
            'Project Status',
            $project_statuses,
            $project_status_counts
          ),
        ],

        'task_status' => [
          '#markup' => $this->buildStatusSection(
            'Task Status',
            $task_statuses,
            $task_status_counts
          ),
        ],

        'priorities' => [
          '#markup' => $this->buildPrioritySection(
            $priorities,
            $project_priority_counts,
            $task_priority_counts
          ),
        ],
      ],

      'deadlines' => [
        '#markup' => $this->buildUpcomingTasks(),
      ],

      'activity' => [
        '#markup' => '
          <div class="icts-ops-dashboard-section icts-ops-dashboard-activity">
            <div class="icts-ops-dashboard-section-heading">
              <h2>Operations Activity</h2>
              <a href="/ops/activity/">View Activity</a>
            </div>
            <p>Review recent project and task changes, assignments and operational updates.</p>
          </div>
        ',
      ],

      '#cache' => [
        'tags' => [
          'node_list:ops_project',
          'node_list:ops_task',
          'node_list:ops_incident',
        ],
      ],
    ];
  }

  /**
   * Builds upcoming task deadlines.
   */
  protected function buildUpcomingTasks() {
    $storage = $this->entityTypeManager()->getStorage('node');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'ops_task')
      ->condition('status', 1)
      ->exists('field_task_due_date')
      ->sort('field_task_due_date', 'ASC')
      ->range(0, 5);

    $ids = $query->execute();

    $output = '<div class="icts-ops-dashboard-section icts-ops-dashboard-deadlines">';
    $output .= '<div class="icts-ops-dashboard-section-heading">';
    $output .= '<h2>Upcoming Task Deadlines</h2>';
    $output .= '<a href="/ops/tasks">View All Tasks</a>';
    $output .= '</div>';

    if (!$ids) {
      $output .= '<p class="ops-dashboard-empty">No upcoming task deadlines.</p>';
      $output .= '</div>';
      return $output;
    }

    $nodes = $storage->loadMultiple($ids);

    $output .= '<div class="ops-dashboard-deadline-list">';

    foreach ($nodes as $node) {
      $title = htmlspecialchars($node->label(), ENT_QUOTES, 'UTF-8');
      $url = $node->toUrl()->toString();

      $date = '';
      if (!$node->get('field_task_due_date')->isEmpty()) {
        $date = $node->get('field_task_due_date')->date->format('D, d M Y - H:i');
      }

      $status = '';
      if (!$node->get('field_task_status')->isEmpty()) {
        $status = $node->get('field_task_status')->value;
      }

      $output .= '<div class="ops-dashboard-deadline-item">';
      $output .= '<div>';
      $output .= '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="ops-dashboard-deadline-title">' . $title . '</a>';
      $output .= '<div class="ops-dashboard-deadline-date">' . htmlspecialchars($date, ENT_QUOTES, 'UTF-8') . '</div>';
      $output .= '</div>';

      if ($status) {
        $output .= '<span class="ops-dashboard-deadline-status">' .
          htmlspecialchars($status, ENT_QUOTES, 'UTF-8') .
          '</span>';
      }

      $output .= '</div>';
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
  }

  /**
   * Counts published nodes of a specific content type.
   */
  protected function countNodes($type) {
    return (int) $this->entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', $type)
      ->condition('status', 1)
      ->count()
      ->execute();
  }

  /**
   * Counts published nodes matching a field value.
   */
  protected function countNodesByField($type, $field, $value) {
    return (int) $this->entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', $type)
      ->condition('status', 1)
      ->condition($field . '.value', $value)
      ->count()
      ->execute();
  }

  /**
   * Builds a status summary section.
   */
  protected function buildStatusSection($title, array $statuses, array $counts) {
    $output = '<div class="icts-ops-dashboard-section">';
    $output .= '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>';
    $output .= '<div class="icts-ops-dashboard-status-list">';

    foreach ($statuses as $value => $label) {
      $count = $counts[$value] ?? 0;

      $output .= '<div class="icts-ops-dashboard-status-item">';
      $output .= '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
      $output .= '<strong>' . $count . '</strong>';
      $output .= '</div>';
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
  }

  /**
   * Builds the priority summary section.
   */
  protected function buildPrioritySection(
    array $priorities,
    array $project_counts,
    array $task_counts
  ) {
    $output = '<div class="icts-ops-dashboard-section">';
    $output .= '<h2>Priority Overview</h2>';
    $output .= '<div class="icts-ops-dashboard-priority-list">';

    foreach ($priorities as $value => $label) {
      $project_count = $project_counts[$value] ?? 0;
      $task_count = $task_counts[$value] ?? 0;

      $output .= '<div class="icts-ops-dashboard-priority-item">';
      $output .= '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
      $output .= '<div>';
      $output .= '<span>Projects: <strong>' . $project_count . '</strong></span>';
      $output .= '<span>Tasks: <strong>' . $task_count . '</strong></span>';
      $output .= '</div>';
      $output .= '</div>';
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
  }

}
