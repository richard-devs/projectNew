<?php

namespace Drupal\unh_inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\File\FileSystemInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Bulk import ICTS inventory devices from CSV or Excel.
 */
class InventoryImportForm extends FormBase {

  public function getFormId() {
    return 'unh_inventory_import_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['intro'] = [
      '#markup' => '<p>Upload a CSV or Excel file containing ICTS inventory devices. Required fields are Device Name, Asset ID / Tag No., Category, Serial Number, Year, Staff / User Name, Organization / Email and Status.</p>',
    ];

    $form['inventory_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Inventory File'),
      '#description' => $this->t('Accepted formats: CSV, XLSX. Maximum file size: 10 MB.'),
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'csv xlsx',
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
      '#value' => $this->t('Import Devices'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => \Drupal\Core\Url::fromUserInput('/inventory'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uploaded_files = \Drupal::request()->files->get('files', []);
    $uploaded_file = $uploaded_files['inventory_file'] ?? NULL;

    if (!$uploaded_file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
      $this->messenger()->addError($this->t('Please upload a CSV or Excel file.'));
      return;
    }

    if ($uploaded_file->getError() !== UPLOAD_ERR_OK) {
      $this->messenger()->addError($this->t('The inventory file upload failed with error code @code.', [
        '@code' => $uploaded_file->getError(),
      ]));
      return;
    }

    $spreadsheet = NULL;

    try {
      $spreadsheet = IOFactory::load($uploaded_file->getRealPath());
      $sheet = $spreadsheet->getActiveSheet();
      $rows = $sheet->toArray(NULL, TRUE, TRUE, TRUE);

      if (!$rows) {
        throw new \RuntimeException('The uploaded file is empty.');
      }

      $headers = array_shift($rows);
      $headers = array_map(
        fn($value) => $this->normalizeHeader((string) $value),
        $headers
      );

      $required = [
        'asset_id_tag_no',
        'category',
        'serial_number',
      ];

      if (
        !in_array('device_name', $headers, TRUE) &&
        !in_array('hostname', $headers, TRUE) &&
        !in_array('device_name_hostname', $headers, TRUE)
      ) {
        throw new \RuntimeException('Missing required column: Device Name / Hostname');
      }

      foreach ($required as $field) {
        if (!in_array($field, $headers, TRUE)) {
          throw new \RuntimeException(
            'Missing required column: ' . str_replace('_', ' ', $field)
          );
        }
      }

      $imported = 0;
      $failed = [];

      foreach ($rows as $row_number => $row) {
        $excel_row = $row_number + 2;
        $data = [];

        foreach ($headers as $column => $header) {
          $data[$header] = isset($row[$column]) ? trim((string) $row[$column]) : '';
        }

        if (!array_filter($data, static fn($value) => $value !== '')) {
          continue;
        }

        try {
          $this->importRow($data, $excel_row);
          $imported++;
        }
        catch (\Throwable $e) {
          $failed[] = 'Row ' . $excel_row . ': ' . $e->getMessage();
        }
      }

      if ($imported) {
        $this->messenger()->addStatus(
          $this->t('@count device(s) imported successfully.', [
            '@count' => $imported,
          ])
        );
      }

      if ($failed) {
        $this->messenger()->addWarning(
          $this->t('@count row(s) were not imported:', [
            '@count' => count($failed),
          ]) . '<br>' . implode('<br>', array_map('htmlspecialchars', $failed))
        );
      }
    }
    catch (\Throwable $e) {
      $this->messenger()->addError(
        $this->t('Import failed: @message', [
          '@message' => $e->getMessage(),
        ])
      );
    }
    finally {
      if ($spreadsheet) {
        $spreadsheet->disconnectWorksheets();
      }
    }
  }

  protected function importRow(array $data, int $row_number): void {
    $device_name = $data['device_name']
      ?? ($data['hostname'] ?? ($data['device_name_hostname'] ?? ''));
    $asset_tag = $data['asset_id_tag_no'] ?? '';
    $category = strtolower($data['category'] ?? '');
    $serial = $data['serial_number'] ?? '';
    $year = $data['year'] ?? '';
    $staff_name = $data['staff_name'] ?? ($data['staff_user_name'] ?? '');
    $staff_index_number = $data['staff_index_number'] ?? ($data['staff_user_index_number'] ?? '');
    $email = $data['organization_email'] ?? '';
    $department = trim((string) ($data['department'] ?? ($data['department_division'] ?? '')));
    $section = trim((string) ($data['section'] ?? ''));
    $date_of_issuance = trim((string) ($data['date_of_issuance'] ?? ''));

    $required_values = [
      'Device Name / Hostname' => $device_name,
      'Asset ID / Tag No.' => $asset_tag,
      'Category' => $category,
      'Serial Number' => $serial,
    ];

    foreach ($required_values as $label => $value) {
      if ($value === '') {
        throw new \RuntimeException($label . ' is required.');
      }
    }

    $status = $staff_name !== '' ? 'assigned' : 'available';

    $department_options = unh_inventory_department_options();

    if ($department === '') {
      $department = 'Unspecified';
    }
    elseif (!isset($department_options[$department])) {
      throw new \RuntimeException('Invalid Department / Division: ' . $department . '. Use one of the approved Department / Division values.');
    }

    $category_map = [
      'laptop' => 'laptop',
      'desktop' => 'desktop',
      'monitor' => 'monitor',
      'docking station' => 'docking_station',
      'docking_station' => 'docking_station',
      'printer' => 'printer',
      'server' => 'server',
      'network device' => 'network_device',
      'network_device' => 'network_device',
      'mobile device' => 'mobile_device',
      'mobile_device' => 'mobile_device',
      'other' => 'other',
    ];

    if (!isset($category_map[$category])) {
      throw new \RuntimeException('Invalid Category: ' . $data['category']);
    }

    $status_map = [
      'available' => 'available',
      'assigned' => 'assigned',
      'under repair' => 'repair',
      'repair' => 'repair',
      'retired' => 'retired',
      'lost' => 'lost',
      'temporary assigned' => 'temporary_assigned',
      'temporary_assigned' => 'temporary_assigned',
    ];

    if (!isset($status_map[$status])) {
      throw new \RuntimeException('Invalid Status: ' . $data['status']);
    }

    $purchase_date = '';

    if ($year !== '') {
      if (!preg_match('/^\d{4}$/', $year)) {
        throw new \RuntimeException('Year must be a four-digit year or left blank.');
      }

      $purchase_date = $year . '-01-01';
    }

    $database = \Drupal::database();

    $existing_tag = $database->select('node__field_asset_tag', 't')
      ->fields('t', ['entity_id'])
      ->condition('field_asset_tag_value', $asset_tag)
      ->range(0, 1)
      ->execute()
      ->fetchField();

    if ($existing_tag) {
      throw new \RuntimeException('Asset ID / Tag No. already exists: ' . $asset_tag);
    }

    $existing_serial = $database->select('node__field_asset_serial_number', 's')
      ->fields('s', ['entity_id'])
      ->condition('field_asset_serial_number_value', $serial)
      ->range(0, 1)
      ->execute()
      ->fetchField();

    if ($existing_serial) {
      throw new \RuntimeException('Serial Number already exists: ' . $serial);
    }

    $node = Node::create([
      'type' => 'icts_asset',
      'title' => $device_name . ' | ' . $asset_tag,
      'field_asset_device_type' => $category_map[$category],
      'field_asset_tag' => $asset_tag,
      'field_asset_serial_number' => $serial,
      'field_asset_purchase_date' => $purchase_date,
      'field_asset_date_of_issuance' => $date_of_issuance,
      'field_asset_status' => $status_map[$status],
      'field_asset_department' => $department,
      'field_asset_section' => $section,
      'field_asset_notes' => 'Uploaded in bulk',
      'uid' => \Drupal::currentUser()->id(),
      'status' => 1,
    ]);

    $node->save();

    $assignment_date = date('Y-m-d');

    if ($staff_name !== '') {
      $database->insert('unh_inventory_assignment')
        ->fields([
          'asset_nid' => $node->id(),
          'uid' => \Drupal::currentUser()->id(),
          'staff_name' => $staff_name,
          'staff_index_number' => $staff_index_number,
          'organization_email' => $email,
          'assigned_date' => $assignment_date,
          'returned_date' => NULL,
          'status' => 'active',
          'notes' => 'Uploaded in bulk',
        ])
        ->execute();

      if (function_exists('unh_inventory_log_inventory_activity')) {
        unh_inventory_log_inventory_activity(
          'assigned',
          'inventory_device',
          (int) $node->id(),
          $node->label(),
          'assignment',
          'Device Assignment',
          'Unassigned',
          $staff_name,
          'successful',
          $staff_index_number !== '' ? $staff_index_number : NULL
        );
      }
    }
  }

  protected function normalizeHeader(string $header): string {
    $header = trim($header);
    $header = strtolower($header);
    $header = preg_replace('/[^a-z0-9]+/', '_', $header);
    return trim($header, '_');
  }

}
