<?php

namespace Drupal\unh_inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class InventoryReturnForm extends FormBase {

  public function getFormId() {
    return 'unh_inventory_return_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $assignment_id = NULL) {
    $assignment_id = (int) $assignment_id;

    $assignment = \Drupal::database()->select('unh_inventory_assignment', 'a')
      ->fields('a')
      ->condition('id', $assignment_id)
      ->condition('status', 'active')
      ->execute()
      ->fetchAssoc();

    if (!$assignment) {
      return [
        'message' => [
          '#markup' => '<p>This assignment is no longer active.</p>',
        ],
      ];
    }

    $asset = Node::load($assignment['asset_nid']);
    $user = \Drupal\user\Entity\User::load($assignment['uid']);

    $asset_name = $asset ? $asset->label() : 'Unknown device';
    $user_name = $user ? $user->getDisplayName() : 'Unknown user';

    $form['assignment_id'] = [
      '#type' => 'hidden',
      '#value' => $assignment_id,
    ];

    $form['summary'] = [
      '#markup' => '<div class="unh-inventory-return-summary"><strong>Device:</strong> ' .
        htmlspecialchars($asset_name) .
        '<br><strong>Assigned to:</strong> ' .
        htmlspecialchars($user_name) .
        '</div>',
    ];

    $form['returned_date'] = [
      '#type' => 'date',
      '#title' => 'Return date',
      '#default_value' => date('Y-m-d'),
      '#required' => TRUE,
    ];

    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => 'Return notes',
      '#rows' => 4,
      '#description' => 'Optional notes about the returned equipment.',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Return Device',
      '#button_type' => 'primary',
    ];

    $form['back'] = [
      '#markup' => '<div class="unh-inventory-back-row"><a href="/inventory" class="unh-inventory-back-button">← Back to Inventory</a></div>',
      '#weight' => -100,
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $assignment_id = (int) $form_state->getValue('assignment_id');
    $returned_date = $form_state->getValue('returned_date');
    $notes = trim((string) $form_state->getValue('notes'));

    $database = \Drupal::database();

    $assignment = $database->select('unh_inventory_assignment', 'a')
      ->fields('a', ['asset_nid', 'staff_name', 'staff_index_number', 'organization_email'])
      ->condition('id', $assignment_id)
      ->condition('status', 'active')
      ->execute()
      ->fetchAssoc();

    if (!$assignment) {
      $this->messenger()->addError('The assignment could not be found.');
      $form_state->setRedirect('unh_inventory.assignments');
      return;
    }

    $database->update('unh_inventory_assignment')
      ->fields([
        'returned_date' => $returned_date,
        'status' => 'returned',
        'notes' => $notes,
      ])
      ->condition('id', $assignment_id)
      ->execute();

    $asset = Node::load($assignment['asset_nid']);

    if ($asset) {
      $asset->set('field_asset_status', 'available');
      $asset->save();
    }

    unh_inventory_log_inventory_activity(
      'returned',
      'inventory_assignment',
      (int) $assignment['asset_nid'],
      $asset ? $asset->label() : 'Unknown device',
      'assignment',
      'Return',
      ($assignment['staff_name'] ?? '') ?: (($assignment['organization_email'] ?? '') ?: 'Assigned user'),
      'Returned / Available',
      'successful',
      ($assignment['staff_index_number'] ?? '') ?: NULL
    );

    $this->messenger()->addStatus('The device has been returned and is now available.');
    $form_state->setRedirect('unh_inventory.assignments');
  }

}
