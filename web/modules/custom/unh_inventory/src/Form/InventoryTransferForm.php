<?php

namespace Drupal\unh_inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

class InventoryTransferForm extends FormBase {

  public function getFormId() {
    return 'unh_inventory_transfer_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $assignment_id = NULL, $node = NULL) {
    $assignment_id = (int) $assignment_id;

    $database = \Drupal::database();
    $assignment = NULL;
    $asset = NULL;

    /*
     * Existing assignment.
     */
    if ($assignment_id > 0) {
      $assignment = $database
        ->select('unh_inventory_assignment', 'a')
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

      $asset = Node::load((int) $assignment['asset_nid']);
    }

    /*
     * No active assignment.
     * The device is supplied through the route.
     */
    if (!$assignment && $node) {
      $asset = $node instanceof Node
        ? $node
        : Node::load((int) $node);
    }

    /*
     * Safety fallback.
     */
    if (!$asset || $asset->bundle() !== 'icts_asset') {
      return [
        'message' => [
          '#markup' => '<p>Invalid inventory device.</p>',
        ],
      ];
    }

    $asset_nid = (int) $asset->id();
    $asset_name = $asset->label();

    $current_name = '';
    $current_email = '';
    $current_index_number = '';
    $current_department = '';
    $current_section = '';

    if ($asset->hasField('field_asset_department') && !$asset->get('field_asset_department')->isEmpty()) {
      $current_department = trim((string) $asset->get('field_asset_department')->value);
    }

    if ($asset->hasField('field_asset_section') && !$asset->get('field_asset_section')->isEmpty()) {
      $current_section = trim((string) $asset->get('field_asset_section')->value);
    }

    if ($assignment) {
      $current_name = $assignment['staff_name'] ?? '';
      $current_email = $assignment['organization_email'] ?? '';
      $current_index_number = $assignment['staff_index_number'] ?? '';

      if (!$current_name && !empty($assignment['uid'])) {
        $user = \Drupal\user\Entity\User::load((int) $assignment['uid']);

        if ($user) {
          $current_name = $user->getDisplayName();
          $current_email = $user->getEmail();
        }
      }
    }

    /*
     * If no active assignment exists, make sure we are not
     * accidentally showing a historical assignee as current.
     */
    $form['assignment_id'] = [
      '#type' => 'hidden',
      '#value' => $assignment_id,
    ];

    $form['asset_nid'] = [
      '#type' => 'hidden',
      '#value' => $asset_nid,
    ];

    $summary =
      '<div class="unh-inventory-return-summary">' .
      '<strong>Device:</strong> ' .
      htmlspecialchars($asset_name);

    if ($assignment) {
      $summary .=
        '<br><strong>Current owner:</strong> ' .
        htmlspecialchars($current_name ?: 'Unknown user');

      if ($current_email) {
        $summary .=
          '<br><strong>Organization / Email:</strong> ' .
          htmlspecialchars($current_email);
      }

      if ($current_index_number) {
        $summary .=
          '<br><strong>Index Number:</strong> ' .
          htmlspecialchars($current_index_number);
      }
    }
    else {
      $summary .=
        '<br><strong>Current owner:</strong> Unassigned';
    }

    $summary .= '</div>';

    $form['summary'] = [
      '#markup' => $summary,
    ];

    $form['staff_name'] = [
      '#type' => 'textfield',
      '#title' => 'Staff / User Name',
      '#description' => 'Enter the full name of the person receiving the device.',
      '#default_value' => '',
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['organization_email'] = [
      '#type' => 'email',
      '#title' => 'Organization / Email',
      '#description' => 'Enter the receiving user\'s organization or email address.',
      '#default_value' => $current_email,
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['staff_index_number'] = [
      '#type' => 'textfield',
      '#title' => 'Staff / User Index Number',
      '#description' => 'Enter the index number of the person receiving the device.',
      '#default_value' => '',
      '#required' => TRUE,
      '#maxlength' => 100,
    ];

    $form['department'] = [
      '#type' => 'select',
      '#title' => 'Department / Division',
      '#options' => unh_inventory_department_options(),
      '#default_value' => $current_department ?: 'Unspecified',
      '#required' => TRUE,
    ];

    $form['section'] = [
      '#type' => 'textfield',
      '#title' => 'Section',
      '#description' => 'Optional. Enter the section when known.',
      '#default_value' => $current_section,
      '#required' => FALSE,
      '#maxlength' => 255,
    ];

    $form['transfer_date'] = [
      '#type' => 'date',
      '#title' => 'Assignment / Ownership Date',
      '#default_value' => date('Y-m-d'),
      '#required' => TRUE,
    ];

    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => 'Notes / Reason',
      '#rows' => 4,
      '#description' => 'Optional notes or reason for the assignment or reassignment.',
      '#maxlength' => 2000,
    ];

    $form['handover_document'] = [
      '#type' => 'file',
      '#title' => 'Signed Ownership / Handover Document',
      '#description' => 'Upload the signed ownership or handover document. PDF, JPG, JPEG or PNG, maximum 10 MB.',
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
      '#value' => $assignment
        ? 'Reassign Device'
        : 'Assign Device',
      '#button_type' => 'primary',
    ];

    $form['back'] = [
      '#markup' =>
        '<div class="unh-inventory-back-row">' .
        '<a href="/inventory/devices" class="unh-inventory-back-button">← Back to Devices</a>' .
        '</div>',
      '#weight' => -100,
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $assignment_id = (int) $form_state->getValue('assignment_id');
    $asset_nid = (int) $form_state->getValue('asset_nid');

    if (!$asset_nid) {
      $form_state->setErrorByName(
        'asset_nid',
        $this->t('The inventory device could not be identified.')
      );
      return;
    }

    if ($assignment_id > 0) {
      $exists = \Drupal::database()
        ->select('unh_inventory_assignment', 'a')
        ->condition('id', $assignment_id)
        ->condition('status', 'active')
        ->countQuery()
        ->execute()
        ->fetchField();

      if (!$exists) {
        $form_state->setErrorByName(
          'assignment_id',
          $this->t('This assignment is no longer active.')
        );
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $assignment_id = (int) $form_state->getValue('assignment_id');
    $asset_nid = (int) $form_state->getValue('asset_nid');
    $transfer_date = $form_state->getValue('transfer_date');
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
          'filename' => $file_system->basename($uri),
          'status' => 1,
        ]);

        $file->setPermanent();
        $file->save();

        $handover_fid = $file->id();
      }
    }


    $database = \Drupal::database();
    $asset = Node::load($asset_nid);

    if (!$asset || $asset->bundle() !== 'icts_asset') {
      $this->messenger()->addError(
        $this->t('The inventory device could not be found.')
      );
      $form_state->setRedirect('unh_inventory.devices');
      return;
    }

    /*
     * Find the active assignment supplied by the form.
     */
    $assignment = NULL;

    if ($assignment_id > 0) {
      $assignment = $database
        ->select('unh_inventory_assignment', 'a')
        ->fields('a')
        ->condition('id', $assignment_id)
        ->condition('status', 'active')
        ->execute()
        ->fetchAssoc();
    }

    /*
     * If no assignment ID was supplied, check whether an active
     * assignment already exists for this device.
     */
    if (!$assignment) {
      $assignment = $database
        ->select('unh_inventory_assignment', 'a')
        ->fields('a')
        ->condition('asset_nid', $asset_nid)
        ->condition('status', 'active')
        ->range(0, 1)
        ->execute()
        ->fetchAssoc();

      if ($assignment) {
        $assignment_id = (int) $assignment['id'];
      }
    }

    /*
     * Preserve the previous owner's details before closing
     * the old assignment.
     */
    $previous_name = '';
    $previous_index_number = '';

    if ($assignment) {
      $previous_name = trim((string) ($assignment['staff_name'] ?? ''));
      $previous_index_number = trim((string) ($assignment['staff_index_number'] ?? ''));

      if (!$previous_name && !empty($assignment['uid'])) {
        $user = \Drupal\user\Entity\User::load((int) $assignment['uid']);

        if ($user) {
          $previous_name = $user->getDisplayName();
        }
      }

      $previous_name = $previous_name ?: 'Unassigned';

      /*
       * Close the old assignment WITHOUT erasing its owner,
       * email, UID or original assignment date.
       * This preserves complete assignment history.
       */
      $database
        ->update('unh_inventory_assignment')
        ->fields([
          'returned_date' => $transfer_date,
          'status' => 'returned',
          'notes' => $notes ?: ($assignment['notes'] ?? ''),
        ])
        ->condition('id', $assignment_id)
        ->condition('status', 'active')
        ->execute();

      unh_inventory_log_inventory_activity(
        'assignment_closed',
        'inventory_device',
        $asset_nid,
        $asset->label(),
        'assignment',
        'Previous Assignment',
        $previous_name,
        'Returned / Reassigned',
        'successful',
        $previous_index_number ?: NULL
      );
    }
    else {
      $previous_name = 'Unassigned';
    }

    /*
     * Create the new active assignment.
     */
    $database
      ->insert('unh_inventory_assignment')
      ->fields([
        'asset_nid' => $asset_nid,
        'uid' => NULL,
        'staff_name' => $new_name,
        'staff_index_number' => $staff_index_number,
        'organization_email' => $new_email,
        'assigned_date' => $transfer_date,
        'returned_date' => NULL,
        'status' => 'active',
        'notes' => $notes,
      ])
      ->execute();

    /*
     * Update the current Department / Division and Section on the asset.
     * Existing assignment and activity history is preserved.
     */
    $old_department = '';

    if ($asset->hasField('field_asset_department') && !$asset->get('field_asset_department')->isEmpty()) {
      $old_department = trim((string) $asset->get('field_asset_department')->value);
    }

    if ($asset->hasField('field_asset_department')) {
      $asset->set('field_asset_department', $department);
    }

    if ($old_department !== $department) {
      unh_inventory_log_inventory_activity(
        'department_changed',
        'inventory_device',
        $asset_nid,
        $asset->label(),
        'field_asset_department',
        'Department / Division',
        $old_department !== '' ? $old_department : '—',
        $department,
        'successful',
        $staff_index_number
      );
    }

    /*
     * Update the optional Section on the asset.
     * Existing assignment and activity history is preserved.
     */
    $old_section = '';

    if ($asset->hasField('field_asset_section') && !$asset->get('field_asset_section')->isEmpty()) {
      $old_section = trim((string) $asset->get('field_asset_section')->value);
    }

    if ($asset->hasField('field_asset_section')) {
      $asset->set('field_asset_section', $section);
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

    /*
     * Update the device status to Assigned and record the
     * status transition with the assignment reason.
     */
    $old_status = '';
    if ($asset->hasField('field_asset_status') && !$asset->get('field_asset_status')->isEmpty()) {
      $old_status = (string) $asset->get('field_asset_status')->value;
    }

    $asset->set('field_asset_status', 'assigned');

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

    /*
     * Attach the signed ownership / handover document.
     */
    $file = $handover_fid ? File::load($handover_fid) : NULL;

    if ($file) {

      if ($asset->hasField('field_asset_handover_document')) {
        $asset->set('field_asset_handover_document', [
          'target_id' => $file->id(),
        ]);
      }

      /*
       * Also store the document in the ownership field when
       * available, so the signed document represents ownership
       * as well as handover.
       */
      if ($asset->hasField('field_asset_ownership_document')) {
        $asset->set('field_asset_ownership_document', [
          'target_id' => $file->id(),
        ]);
      }

      $asset->save();

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
    else {
      $asset->save();
    }

    /*
     * Log the complete ownership change.
     */
    unh_inventory_log_inventory_activity(
      $assignment ? 'reassigned' : 'assigned',
      'inventory_device',
      $asset_nid,
      $asset->label(),
      'assignment',
      $assignment ? 'Device Reassignment' : 'Device Assignment',
      $previous_name,
      $new_name,
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

    $this->messenger()->addStatus(
      $this->t(
        $assignment
          ? 'The device has been reassigned successfully.'
          : 'The device has been assigned successfully.'
      )
    );

    $form_state->setRedirect('unh_inventory.devices');
  }

}
