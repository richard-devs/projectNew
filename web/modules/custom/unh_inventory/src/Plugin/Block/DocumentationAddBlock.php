<?php

namespace Drupal\unh_inventory\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides an Add Documentation button.
 *
 * @Block(
 *   id = "documentation_add",
 *   admin_label = @Translation("Add Documentation"),
 *   category = @Translation("ICTS")
 * )
 */
class DocumentationAddBlock extends BlockBase {

  public function build() {
    if (!\Drupal::currentUser()->hasPermission('create ops_documentation content')) {
      return [];
    }

    return [
      '#type' => 'link',
      '#title' => $this->t('+ Add Documentation'),
      '#url' => Url::fromRoute('node.add', ['node_type' => 'ops_documentation']),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];
  }

}
