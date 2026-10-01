<?php

namespace Drupal\unh_inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Form for changing an ICTS device status.
 */
class InventoryDeviceStatusForm extends FormBase {

  public function getFormId() {
    return 'unh_inventory_device_status_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    if (!$node || $node->bundle() !== 'icts_asset') {
      return [
        'message' => [
          '#markup' => 'Invalid inventory device.',
        ],
      ];
    }

    $allowed = [
      'available' => 'Available',
      'repair' => 'Under repair',
      'lost' => 'Lost',
      'retired' => 'Retired',
    ];

    $current = $node->get('field_asset_status')->value ?? 'available';

    $form['device'] = [
      '#markup' =>
        '<div class="unh-device-status-device">' .
        '<strong>Device:</strong> ' .
        htmlspecialchars($node->label()) .
        '</div>',
    ];

    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Device Status'),
      '#options' => $allowed,
      '#default_value' => $current,
      '#required' => TRUE,
    ];

    $form['reason'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reason / Note'),
      '#description' => $this->t('A reason or note is required for every status change.'),
      '#rows' => 4,
      '#required' => TRUE,
      '#maxlength' => 2000,
    ];

    $form['node_id'] = [
      '#type' => 'hidden',
      '#value' => $node->id(),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Status'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => \Drupal\Core\Url::fromRoute('unh_inventory.devices'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $status = (string) $form_state->getValue('status');
    $reason = trim((string) $form_state->getValue('reason'));

    if (in_array($status, ['assigned', 'temporary_assigned'], TRUE)) {
      $form_state->setErrorByName(
        'status',
        $this->t('Assigned and Temporary Assigned must be handled through the Assign / Reassign workflow.')
      );
    }

    if ($reason === '') {
      $form_state->setErrorByName(
        'reason',
        $this->t('A reason or note is required before changing the status.')
      );
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nid = (int) $form_state->getValue('node_id');
    $new_status = (string) $form_state->getValue('status');
    $reason = trim((string) $form_state->getValue('reason'));

    $node = Node::load($nid);

    if (!$node || $node->bundle() !== 'icts_asset') {
      $this->messenger()->addError(
        $this->t('The device could not be found.')
      );
      $form_state->setRedirect('unh_inventory.devices');
      return;
    }

    $old_status = $node->get('field_asset_status')->value ?? '';

    if ($old_status === $new_status) {
      $this->messenger()->addWarning(
        $this->t('No status change was made.')
      );
      $form_state->setRedirect('unh_inventory.devices');
      return;
    }

    $labels = [
      'available' => 'Available',
      'repair' => 'Under repair',
      'lost' => 'Lost',
      'retired' => 'Retired',
    ];

    /*
     * A status change is independent of assignment.
     *
     * If the device is changed to Available, close the active
     * assignment while preserving the assignment record as history.
     */
    if ($new_status === 'available') {
      $database = \Drupal::database();

      $active_assignment = $database
        ->select('unh_inventory_assignment', 'a')
        ->fields('a', ['id'])
        ->condition('asset_nid', $nid)
        ->condition('status', 'active')
        ->range(0, 1)
        ->execute()
        ->fetchField();

      if ($active_assignment) {
        $database
          ->update('unh_inventory_assignment')
          ->fields([
            'returned_date' => date('Y-m-d'),
            'status' => 'returned',
          ])
          ->condition('id', $active_assignment)
          ->condition('status', 'active')
          ->execute();
      }
    }

    $node->set('field_asset_status', $new_status);
    $node->save();

    unh_inventory_log_inventory_activity(
      'status_changed',
      'inventory_device',
      $node->id(),
      $node->label(),
      'field_asset_status',
      'Status',
      $labels[$old_status] ?? $old_status,
      ($labels[$new_status] ?? $new_status) . ' | Reason: ' . $reason
    );

    $this->messenger()->addStatus(
      $this->t('The device status has been updated successfully.')
    );

    $form_state->setRedirect('unh_inventory.devices');
  }

}
