<?php

namespace Drupal\unh_inventory\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

class InventoryDeviceDeleteForm extends ConfirmFormBase {

  protected NodeInterface $node;

  public function getFormId() {
    return 'unh_inventory_device_delete_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    $this->node = $node;

    if (!$this->node || $this->node->bundle() !== 'icts_asset') {
      return [
        'error' => [
          '#markup' => '<p>Invalid inventory device.</p>',
        ],
      ];
    }

    $asset_id = $this->node->get('field_asset_tag')->value ?? '';

    $form['warning'] = [
      '#markup' =>
        '<div class="unh-inventory-delete-confirm">' .
        '<h2>Delete Device</h2>' .
        '<p>You are about to delete <strong>' .
        htmlspecialchars($this->node->label()) .
        '</strong>' .
        ($asset_id ? ' (' . htmlspecialchars($asset_id) . ')' : '') .
        '.</p>' .
        '<p>The device record will be removed, but existing inventory activity logs and assignment history will be preserved.</p>' .
        '<p><strong>This action cannot be undone.</strong></p>' .
        '</div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  public function getQuestion() {
    return $this->t('Are you sure you want to delete this device?');
  }

  public function getCancelUrl() {
    return Url::fromRoute('unh_inventory.devices');
  }

  public function getConfirmText() {
    return $this->t('Delete Device');
  }

  public function getCancelText() {
    return $this->t('Cancel');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->node || $this->node->bundle() !== 'icts_asset') {
      $this->messenger()->addError($this->t('Invalid inventory device.'));
      return;
    }

    $database = \Drupal::database();
    $nid = (int) $this->node->id();
    $label = $this->node->label();
    $asset_id = $this->node->get('field_asset_tag')->value ?? '';

    /*
     * Close any active assignment before deleting the device.
     * The assignment row is deliberately retained so its history remains available.
     */
    $assignment_query = $database->select('unh_inventory_assignment', 'a');
    $assignment_query->fields('a', [
      'id',
      'staff_name',
      'staff_index_number',
      'organization_email',
    ]);
    $assignment_query->condition('a.asset_nid', $nid);
    $assignment_query->condition('a.status', 'active');

    $active_assignments = $assignment_query->execute()->fetchAll();
    $current_index_number = !empty($active_assignments)
      ? (string) ($active_assignments[0]->staff_index_number ?? '')
      : '';

    foreach ($active_assignments as $assignment) {
      $database->update('unh_inventory_assignment')
        ->fields([
          'status' => 'returned',
          'returned_date' => date('Y-m-d'),
        ])
        ->condition('id', $assignment->id)
        ->execute();
    }

    /*
     * Record deletion BEFORE removing the Drupal node.
     * unh_inventory_activity is intentionally not deleted.
     */
    if (function_exists('unh_inventory_log_inventory_activity')) {
      unh_inventory_log_inventory_activity(
        'device_deleted',
        'inventory_device',
        $nid,
        $label,
        'device',
        'Inventory Device',
        $asset_id ?: NULL,
        'Device deleted',
        'successful',
        $current_index_number ?: NULL
      );
    }

    $this->node->delete();

    $this->messenger()->addStatus(
      $this->t('The device @device was deleted. Its inventory activity and assignment history were preserved.', [
        '@device' => $label,
      ])
    );

    $form_state->setRedirect('unh_inventory.devices');
  }

}
