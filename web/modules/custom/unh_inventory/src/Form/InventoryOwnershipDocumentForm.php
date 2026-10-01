<?php

namespace Drupal\unh_inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

class InventoryOwnershipDocumentForm extends FormBase {

  public function getFormId() {
    return 'unh_inventory_ownership_document_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $node = $node instanceof Node ? $node : Node::load((int) $node);

    if (!$node || $node->bundle() !== 'icts_asset') {
      return [
        '#markup' => '<p>Invalid inventory device.</p>',
      ];
    }

    $status = $node->get('field_asset_status')->value ?? '';

    if ($status !== 'available') {
      return [
        '#markup' => '<div class="messages messages--warning">A signed ownership document can only be uploaded for an Available device.</div>',
      ];
    }

    $form['node_id'] = [
      '#type' => 'hidden',
      '#value' => $node->id(),
    ];

    $form['device'] = [
      '#markup' => '<div class="unh-inventory-document-device"><strong>Device:</strong> ' . htmlspecialchars($node->label()) . '</div>',
    ];

    $form['ownership_document'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Signed Ownership Document'),
      '#description' => $this->t('Upload the signed ownership document for this device. PDF, JPG, JPEG or PNG, maximum 10 MB.'),
      '#upload_location' => 'public://inventory/ownership',
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'pdf jpg jpeg png',
        ],
        'FileSizeLimit' => [
          'fileLimit' => 10 * 1024 * 1024,
        ],
      ],
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload Signed Ownership Document'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => \Drupal\Core\Url::fromUserInput('/inventory/devices'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node = Node::load((int) $form_state->getValue('node_id'));
    $fid = (int) $form_state->getValue('ownership_document');

    if (!$node || !$fid) {
      $this->messenger()->addError($this->t('The device or document could not be found.'));
      return;
    }

    $file = File::load($fid);

    if (!$file) {
      $this->messenger()->addError($this->t('The uploaded document could not be found.'));
      return;
    }

    $file->setPermanent();
    $file->save();

    if ($node->hasField('field_asset_ownership_document')) {
      $node->set('field_asset_ownership_document', [
        'target_id' => $file->id(),
      ]);
      $node->save();
    }

    unh_inventory_log_inventory_activity(
      'ownership_document_uploaded',
      'inventory_device',
      $node->id(),
      $node->label(),
      'field_asset_ownership_document',
      'Signed Ownership Document',
      NULL,
      $file->getFilename()
    );

    $this->messenger()->addStatus($this->t('Signed ownership document uploaded successfully.'));
    $form_state->setRedirect('unh_inventory.devices');
  }

}
