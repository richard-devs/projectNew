<?php

namespace Drupal\unh_inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

class InventoryAssignmentForm extends FormBase {

  public function getFormId() {
    return 'unh_inventory_assignment_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $device_types = [
      'laptop' => 'Laptop',
      'desktop' => 'Desktop',
      'monitor' => 'Monitor',
      'docking_station' => 'Docking station',
      'printer' => 'Printer',
      'server' => 'Server',
      'network_device' => 'Network device',
      'mobile_device' => 'Mobile device',
      'other' => 'Other',
    ];

    $form['device_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Device type'),
      '#options' => [
        '' => $this->t('- Select device type -'),
      ] + $device_types,
      '#required' => TRUE,
    ];

    $device_options = [
      '' => $this->t('- Select available device -'),
    ];

    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'icts_asset')
      ->condition('field_asset_status', 'available')
      ->sort('title', 'ASC')
      ->execute();

    if ($nids) {
      $assets = Node::loadMultiple($nids);

      foreach ($assets as $asset) {
        $tag = $asset->get('field_asset_tag')->value ?? '';
        $device_type = $asset->get('field_asset_device_type')->value ?? '';

        $device_label = $device_types[$device_type] ?? 'Other';
        $label = $device_label;

        if ($tag) {
          $label .= ' | ' . $tag;
        }

        $device_options[$asset->id()] = $label;
      }
    }

    $form['asset_nid'] = [
      '#type' => 'select',
      '#title' => $this->t('Available device'),
      '#options' => $device_options,
      '#required' => TRUE,
    ];

    $form['staff_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Staff / User name'),
      '#description' => $this->t('Enter the full name of the person receiving the device.'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['organization_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Organization / Email'),
      '#description' => $this->t('Enter the person’s organization and/or official email address.'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['staff_index_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Staff / User Index Number'),
      '#description' => $this->t('Enter the staff or user index number.'),
      '#required' => TRUE,
      '#maxlength' => 100,
    ];

    $form['department'] = [
      '#type' => 'select',
      '#title' => $this->t('Department / Division'),
      '#options' => unh_inventory_department_options(),
      '#default_value' => 'Unspecified',
      '#required' => TRUE,
    ];

    $form['section'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Section'),
      '#description' => $this->t('Optional. Enter the section when known.'),
      '#required' => FALSE,
      '#maxlength' => 255,
    ];

    $form['assigned_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Assignment date'),
      '#default_value' => date('Y-m-d'),
      '#required' => TRUE,
    ];

    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notes'),
      '#rows' => 4,
    ];

    $form['handover_document'] = [
      '#type' => 'file',
      '#title' => $this->t('Signed Ownership / Handover Document'),
      '#description' => $this->t('Optional. Upload the signed ownership or handover document. PDF, JPG, JPEG or PNG, maximum 10 MB.'),
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'pdf jpg jpeg png',
        ],
        'FileSizeLimit' => [
          'fileLimit' => 10 * 1024 * 1024,
        ],
      ],
      '#required' => FALSE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Assign Device'),
      '#button_type' => 'primary',
    ];

    $form['back'] = [
      '#markup' => '<div class="unh-inventory-back-row"><a href="/inventory" class="unh-inventory-back-button">← Back to Inventory</a></div>',
      '#weight' => -100,
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $asset_nid = (int) $form_state->getValue('asset_nid');
    $device_type = $form_state->getValue('device_type');

    $asset = Node::load($asset_nid);

    if (!$asset || $asset->bundle() !== 'icts_asset') {
      $form_state->setErrorByName(
        'asset_nid',
        $this->t('The selected device could not be found.')
      );
      return;
    }

    $actual_type = $asset->get('field_asset_device_type')->value;

    if ($actual_type !== $device_type) {
      $form_state->setErrorByName(
        'asset_nid',
        $this->t('The selected device does not match the selected device type.')
      );
    }

    if ($asset->get('field_asset_status')->value !== 'available') {
      $form_state->setErrorByName(
        'asset_nid',
        $this->t('The selected device is no longer available.')
      );
    }

    $existing = \Drupal::database()
      ->select('unh_inventory_assignment', 'a')
      ->fields('a', ['id'])
      ->condition('asset_nid', $asset_nid)
      ->condition('status', 'active')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    if ($existing) {
      $form_state->setErrorByName(
        'asset_nid',
        $this->t('This device already has an active assignment.')
      );
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $asset_nid = (int) $form_state->getValue('asset_nid');
    $asset = Node::load($asset_nid);
    $notes = trim((string) $form_state->getValue('notes'));
    $new_name = trim((string) $form_state->getValue('staff_name'));
    $new_email = trim((string) $form_state->getValue('organization_email'));
    $staff_index_number = trim((string) $form_state->getValue('staff_index_number'));
    $department = trim((string) $form_state->getValue('department'));
    $section = trim((string) $form_state->getValue('section'));

    $handover_fid = 0;
    $uploaded_files = $form_state->getValue('handover_document', []);
    $uploaded_file = is_array($uploaded_files) ? reset($uploaded_files) : $uploaded_files;

    if ($uploaded_file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile
      && $uploaded_file->isValid()) {

      $extension = strtolower($uploaded_file->getClientOriginalExtension());
      $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

      if (!in_array($extension, $allowed, TRUE)) {
        $this->messenger()->addError(
          $this->t('The document must be a PDF, JPG, JPEG or PNG file.')
        );
        return;
      }

      if ($uploaded_file->getSize() > (10 * 1024 * 1024)) {
        $this->messenger()->addError(
          $this->t('The document must not exceed 10 MB.')
        );
        return;
      }

      $file_system = \Drupal::service('file_system');
      $directory = 'public://inventory/handover';

      $file_system->prepareDirectory(
        $directory,
        \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY |
        \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS
      );

      $filename = $file_system->getDestinationFilename(
        $uploaded_file->getClientOriginalName(),
        $directory,
        \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME
      );

      $destination = $directory . '/' . $filename;

      $uri = $file_system->moveUploadedFile(
        $uploaded_file->getPathname(),
        $destination
      );

      if ($uri) {
        $file = File::create([
          'uri' => $uri,
          'status' => 1,
        ]);
        $file->save();
        $handover_fid = (int) $file->id();
      }
    }

    \Drupal::database()
      ->insert('unh_inventory_assignment')
      ->fields([
        'asset_nid' => $asset_nid,
        'uid' => NULL,
        'staff_name' => $new_name,
        'staff_index_number' => $staff_index_number,
        'organization_email' => $new_email,
        'assigned_date' => $form_state->getValue('assigned_date'),
        'returned_date' => NULL,
        'status' => 'active',
        'notes' => $notes,
      ])
      ->execute();

    $old_status = $asset && $asset->hasField('field_asset_status')
      ? (string) $asset->get('field_asset_status')->value
      : '';

    $old_department = '';
    $old_section = '';

    if ($asset && $asset->hasField('field_asset_department') && !$asset->get('field_asset_department')->isEmpty()) {
      $old_department = trim((string) $asset->get('field_asset_department')->value);
    }

    if ($asset && $asset->hasField('field_asset_section') && !$asset->get('field_asset_section')->isEmpty()) {
      $old_section = trim((string) $asset->get('field_asset_section')->value);
    }

    if ($asset && $asset->hasField('field_asset_department')) {
      $asset->set('field_asset_department', $department);
    }

    if ($asset && $asset->hasField('field_asset_section')) {
      $asset->set('field_asset_section', $section);
    }

    $asset->set('field_asset_status', 'assigned');

    if ($handover_fid) {
      $file = File::load($handover_fid);

      if ($file) {
        if ($asset->hasField('field_asset_handover_document')) {
          $asset->set('field_asset_handover_document', [
            'target_id' => $file->id(),
          ]);
        }

        if ($asset->hasField('field_asset_ownership_document')) {
          $asset->set('field_asset_ownership_document', [
            'target_id' => $file->id(),
          ]);
        }
      }
    }

    $asset->save();

    if ($old_department !== $department) {
      unh_inventory_log_inventory_activity(
        'department_changed',
        'inventory_device',
        $asset_nid,
        $asset->label(),
        'field_asset_department',
        'Department / Division',
        $old_department !== '' ? $old_department : '—',
        $department !== '' ? $department : '—',
        'successful',
        $staff_index_number
      );
    }

    if ($old_section !== $section) {
      unh_inventory_log_inventory_activity(
        'section_changed',
        'inventory_device',
        $asset_nid,
        $asset->label(),
        'field_asset_section',
        'Section',
        $old_section !== '' ? $old_section : '—',
        $section !== '' ? $section : '—',
        'successful',
        $staff_index_number
      );
    }

    if ($old_status !== 'assigned') {
      $status_new_value = 'assigned';

      if ($notes !== '') {
        $status_new_value .= ' | Reason: ' . $notes;
      }

      unh_inventory_log_inventory_activity(
        'status_changed',
        'inventory_device',
        $asset_nid,
        $asset->label(),
        'field_asset_status',
        'Status',
        $old_status !== '' ? $old_status : '—',
        $status_new_value,
        'successful',
        $staff_index_number
      );
    }

    unh_inventory_log_inventory_activity(
      'assigned',
      'inventory_device',
      $asset_nid,
      $asset->label(),
      'assignment',
      'Device Assignment',
      'Unassigned',
      $new_name ?: $new_email ?: 'Unknown user',
      'successful',
      $staff_index_number
    );

    if ($notes !== '') {
      unh_inventory_log_inventory_activity(
        'assignment_note',
        'inventory_device',
        $asset_nid,
        $asset->label(),
        'notes',
        'Assignment / Reassignment Notes',
        NULL,
        $notes,
        'successful',
        $staff_index_number
      );
    }

    if ($handover_fid) {
      $file = File::load($handover_fid);

      if ($file) {
        unh_inventory_log_inventory_activity(
          'ownership_document_uploaded',
          'inventory_device',
          $asset_nid,
          $asset->label(),
          'field_asset_ownership_document',
          'Signed Ownership / Handover Document',
          NULL,
          $file->getFilename(),
          'successful',
          $staff_index_number
        );
      }
    }

    $this->messenger()->addStatus(
      $this->t('The device has been assigned successfully.')
    );

    $form_state->setRedirect('unh_inventory.assignments');
  }

}
