<?php

namespace Drupal\unh_inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\file\Entity\File;

/**
 * Form for uploading an ICTS device handover document.
 */
class InventoryHandoverDocumentForm extends FormBase {

  public function getFormId() {
    return 'unh_inventory_handover_document_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    if (!$node || $node->bundle() !== 'icts_asset') {
      return ['message' => ['#markup' => 'Invalid inventory device.']];
    }

    $existing = NULL;

    if ($node->hasField('field_asset_handover_document') &&
      !$node->get('field_asset_handover_document')->isEmpty()) {
      $file = $node->get('field_asset_handover_document')->entity;
      if ($file) {
        $existing = [
          '#type' => 'link',
          '#title' => $this->t('View current handover document'),
          '#url' => \Drupal\Core\Url::fromUri($file->createFileUrl()),
          '#attributes' => [
            'target' => '_blank',
            'class' => ['unh-handover-existing-link'],
          ],
        ];
      }
    }

    $form['device'] = [
      '#markup' => '<div class="unh-device-status-device"><strong>Device:</strong> ' . htmlspecialchars($node->label()) . '</div>',
    ];

    if ($existing) {
      $form['existing'] = $existing;
    }

    $form['document'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Signed Handover / Receipt'),
      '#description' => $this->t('Upload the signed document received when the device was issued. Accepted formats: PDF, JPG and PNG.'),
      '#upload_location' => 'public://inventory/handover',
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'pdf jpg jpeg png',
        ],
        'FileSizeLimit' => [
          'fileLimit' => 10485760,
        ],
      ],
      '#required' => TRUE,
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
      '#value' => $this->t('Upload Document'),
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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nid = (int) $form_state->getValue('node_id');
    $node = \Drupal\node\Entity\Node::load($nid);
    $fid = (int) $form_state->getValue('document');

    if (!$node || $node->bundle() !== 'icts_asset' || !$fid) {
      $this->messenger()->addError($this->t('The document could not be uploaded.'));
      return;
    }

    $file = File::load($fid);

    if (!$file) {
      $this->messenger()->addError($this->t('The uploaded document could not be found.'));
      return;
    }

    $old_file = NULL;

    if ($node->hasField('field_asset_handover_document') &&
      !$node->get('field_asset_handover_document')->isEmpty()) {
      $old_file = $node->get('field_asset_handover_document')->entity;
    }

    $file->setPermanent();
    $file->save();

    $node->set('field_asset_handover_document', [
      'target_id' => $file->id(),
    ]);
    $node->save();

    $action = $old_file ? 'handover_document_replaced' : 'handover_document_uploaded';

    \Drupal\unh_inventory\unh_inventory_log_inventory_activity(
      $action,
      'inventory_device',
      $node->id(),
      $node->label(),
      'field_asset_handover_document',
      'Handover Document',
      $old_file ? $old_file->getFilename() : NULL,
      $file->getFilename()
    );

    if ($old_file && $old_file->id() !== $file->id()) {
      $old_file->delete();
    }

    $this->messenger()->addStatus(
      $this->t('The signed handover document has been uploaded successfully.')
    );

    $form_state->setRedirect('unh_inventory.devices');
  }

}
