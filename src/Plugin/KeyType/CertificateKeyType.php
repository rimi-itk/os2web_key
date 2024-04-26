<?php

namespace Drupal\os2web_key\Plugin\KeyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyTypeBase;

/**
 * Defines a key that combines a certificate and an optional password.
 *
 * @KeyType(
 *   id = "os2web_certificate",
 *   label = @Translation("Certificate"),
 *   description = @Translation("A key type to store a certificate."),
 *   group = "authentication"
 * )
 */
class CertificateKeyType extends KeyTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function generateKeyValue(array $configuration): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function validateKeyValue(array $form, FormStateInterface $form_state, $key_value): void {
  }

}
