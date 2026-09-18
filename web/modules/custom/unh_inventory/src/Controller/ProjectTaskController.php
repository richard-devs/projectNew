<?php

namespace Drupal\unh_inventory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ProjectTaskController extends ControllerBase {

  public function addTask(NodeInterface $node) {
    if ($node->bundle() !== 'ops_project') {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $url = Url::fromRoute('node.add', [
      'node_type' => 'ops_task',
    ], [
      'query' => [
        'field_task_project' => $node->id(),
      ],
    ]);

    return new RedirectResponse($url->toString());
  }

}
