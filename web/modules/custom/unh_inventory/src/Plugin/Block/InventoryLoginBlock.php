<?php

namespace Drupal\unh_inventory\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides an ICTS Inventory login block.
 *
 * @Block(
 *   id = "unh_inventory_login",
 *   admin_label = @Translation("ICTS Inventory Login")
 * )
 */
class InventoryLoginBlock extends BlockBase {

  public function build() {
    $user = \Drupal::currentUser();

    if ($user->isAuthenticated()) {
      $url = Url::fromRoute('unh_inventory.dashboard');

      return [
        '#markup' => '
          <div class="unh-inventory-login">
            <h2>ICTS Inventory</h2>
            <p>Manage assigned ICT equipment and asset records.</p>
            <a class="button btn btn-primary" href="' . $url->toString() . '">
              Open Inventory
            </a>
          </div>
        ',
      ];
    }

    $login_url = Url::fromRoute('user.login', [], [
      'query' => [
        'destination' => '/inventory',
      ],
    ]);

    return [
      '#markup' => '
        <div class="unh-inventory-login">
          <h2>ICTS Inventory</h2>
          <p>Access the ICT equipment inventory and assignment system.</p>
          <a class="button btn btn-primary" href="' . $login_url->toString() . '">
            Inventory Login
          </a>
        </div>
      ',
    ];
  }

}




