<?php

namespace Drupal\os2web_key\Plugin\KeyProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyInterface;

/**
 * Defines a provider for a certificate stored in the local file system.
 *
 * @KeyProvider(
 *   id = "os2web_file_certificate",
 *   label = @Translation("Certificate (file)"),
 *   description = @Translation("A provider for a certificate (with optional passphrase) stored in the local file system."),
 *   storage_method = "remote",
 *   key_value = {
 *     "plugin" = "text_field"
 *   }
 * )
 */
final class FileCertificateKeyProvider extends AbstractCertificateKeyProvider {

  protected const CONFIG_KEY_FILE = 'file';
  protected const CONFIG_KEY_PASSPHRASE = 'passphrase';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      self::CONFIG_KEY_FILE => '',
      self::CONFIG_KEY_PASSPHRASE => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $configuration = $this->getConfiguration();

    $form[self::CONFIG_KEY_FILE] = [
      '#title' => $this->t('File location'),
      '#description' => $this->t('Location of the certificate file. The location may be absolute (e.g., %abs), relative to the Drupal directory (e.g., %rel), or defined using a stream wrapper (e.g., %str).', [
        '%abs' => '/app/certificates/certificate.p12',
        '%rel' => '../certificates/certificate.p12',
        '%str' => 'private://certificates/certificate.p12',
      ]),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $configuration[self::CONFIG_KEY_FILE] ?? NULL,
    ];

    $form[self::CONFIG_KEY_PASSPHRASE] = [
      '#title' => $this->t('Passphrase'),
      '#description' => $this->t('Passphrase for certificate'),
      '#type' => 'textfield',
      '#required' => FALSE,
      '#default_value' => $configuration[self::CONFIG_KEY_PASSPHRASE] ?? NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $configuration = $form_state->getValues();

    $file = $configuration[self::CONFIG_KEY_FILE];
    if (!is_file($file) || !is_readable($file)) {
      $form_state->setErrorByName(self::CONFIG_KEY_FILE, $this->t('The file cannot be read.'));
      return;
    }

    try {
      $this->readCertificates($configuration, NULL);
      $this->createPasswordlessCertificate($configuration, NULL);
    }
    catch (\Exception $exception) {
      $form_state->setErrorByName(self::CONFIG_KEY_PASSPHRASE, $this->t('Error reading certificate: %message', ['%message' => $exception->getMessage()]));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function readCertificates(array $configuration, ?KeyInterface $key): array {
    $file = $configuration[self::CONFIG_KEY_FILE] ?? NULL;
    $passphrase = $configuration[self::CONFIG_KEY_PASSPHRASE] ?? NULL;

    if (!is_file($file) || !is_readable($file)) {
      throw $this->createRuntimeException(sprintf('File %s is not readable', $file), $key);
    }
    $contents = file_get_contents($file);

    return $this->certificateHelper->parseCertificates(
      $contents,
      $configuration[self::CONFIG_INPUT_FORMAT] ?? self::FORMAT_PEM,
      $passphrase,
      $this,
      $key
    );
  }

}
