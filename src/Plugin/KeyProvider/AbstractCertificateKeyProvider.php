<?php

namespace Drupal\os2web_key\Plugin\KeyProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyPluginFormInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\os2web_key\CertificateHelper;
use Drupal\os2web_key\Exception\RuntimeException;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract certificate key provider.
 */
abstract class AbstractCertificateKeyProvider extends KeyProviderBase implements KeyPluginFormInterface {
  use LoggerAwareTrait;

  protected const CONFIG_INPUT_FORMAT = 'input_format';
  protected const CONFIG_OUTPUT_FORMAT = 'output_format';
  protected const FORMAT_PEM = 'pem';
  protected const FORMAT_PFX = 'pfx';

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected CertificateHelper $certificateHelper,
    LoggerChannelInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setLogger($logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\os2web_key\CertificateHelper $certificateHelper */
    $certificateHelper = $container->get(CertificateHelper::class);
    /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
    $logger = $container->get('logger.channel.os2web_key');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $certificateHelper,
      $logger
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key): ?string {
    try {
      $configuration = $this->getConfiguration();

      return $this->createPasswordlessCertificate($configuration, $key);
    }
    catch (\Exception) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $configuration = $this->getConfiguration();

    $formatOptions = [
      self::FORMAT_PEM => $this->t('PEM'),
      self::FORMAT_PFX => $this->t('pfx (p12)'),
    ];
    $form[self::CONFIG_INPUT_FORMAT] = [
      '#title' => $this->t('Input format'),
      '#type' => 'select',
      '#options' => $formatOptions,
      '#required' => TRUE,
      '#default_value' => $configuration[self::CONFIG_INPUT_FORMAT] ?? NULL,
    ];

    $form[self::CONFIG_OUTPUT_FORMAT] = [
      '#title' => $this->t('Output format'),
      '#type' => 'select',
      '#options' => $formatOptions,
      '#required' => TRUE,
      '#default_value' => $configuration[self::CONFIG_OUTPUT_FORMAT] ?? NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * Create a passwordless certificate.
   */
  protected function createPasswordlessCertificate(array $configuration, ?KeyInterface $key): string {
    $certificates = $this->readCertificates($configuration, $key);

    return $this->certificateHelper->createPasswordlessCertificate(
    $certificates,
    $configuration[self::CONFIG_OUTPUT_FORMAT] ?? self::FORMAT_PFX,
    $this,
    $key
    );
  }

  /**
   * Read all certificates.
   */
  abstract protected function readCertificates(array $configuration, ?KeyInterface $key): array;

  /**
   * Create a runtime exception.
   */
  protected function createRuntimeException(string $message, ?KeyInterface $key): RuntimeException {
    return $this->certificateHelper->createRuntimeException($message, $this, $key);
  }

}
