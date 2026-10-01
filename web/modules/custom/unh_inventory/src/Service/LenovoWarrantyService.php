<?php

namespace Drupal\unh_inventory\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Provides Lenovo warranty lookup integration.
 */
class LenovoWarrantyService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs the Lenovo warranty service.
   */
  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
  }

  /**
   * Looks up Lenovo warranty information.
   */
  public function lookup(string $serial, string $machine_type = ''): array {
    $serial = trim($serial);
    $machine_type = trim($machine_type);

    if ($serial === '') {
      return [
        'success' => FALSE,
        'message' => 'A serial number is required.',
      ];
    }

    return [
      'success' => FALSE,
      'status' => 'not_configured',
      'serial' => $serial,
      'machine_type' => $machine_type,
      'message' => 'Lenovo warranty credentials are not yet configured.',
    ];
  }

}
