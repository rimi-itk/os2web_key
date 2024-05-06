<?php

namespace Drupal\os2web_key;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\key\KeyInterface;
use Drupal\os2web_key\Exception\RuntimeException;
use Drupal\os2web_key\Plugin\KeyType\CertificateKeyType;
use Psr\Log\LoggerAwareTrait;

/**
 * Certificate helper.
 */
class CertificateHelper {
  use LoggerAwareTrait;

  protected const FORMAT_PEM = 'pem';
  protected const FORMAT_PFX = 'pfx';
  protected const CERT = 'cert';
  protected const PKEY = 'pkey';

  public function __construct(
    LoggerChannelInterface $logger,
  ) {
    $this->setLogger($logger);
  }

  /**
   * Get certificates from a key.
   *
   * @param \Drupal\key\KeyInterface $key
   *   The key.
   *
   * @return array<string, string>
   *   The certificates.
   */
  public function getCertificates(KeyInterface $key): array {
    $contents = $key->getKeyValue();
    $type = $key->getKeyType();
    if (!($type instanceof CertificateKeyType)) {
      throw new RuntimeException(sprintf('Invalid key type: %s', $type::class));
    }

    return $this->parseCertificates(
      $contents,
      $type->getInputFormat(),
      $type->getPassphrase(),
      $key
    );
  }

  /**
   * Read a certificate.
   *
   * @return array<string, string>
   *   The certificates.
   */
  public function parseCertificates(
    string $contents,
    string $format,
    ?string $passphrase,
    ?KeyInterface $key,
  ): array {
    $certificates = [
      self::CERT => NULL,
      self::PKEY => NULL,
    ];
    switch ($format) {
      case self::FORMAT_PFX:
        if (!openssl_pkcs12_read($contents, $certificates, $passphrase)) {
          throw $this->createSslRuntimeException('Error reading certificate', $key);
        }
        break;

      case self::FORMAT_PEM:
        $certificate = @openssl_x509_read($contents);
        if (FALSE === $certificate) {
          throw $this->createSslRuntimeException('Error reading certificate', $key);
        }
        if (!@openssl_x509_export($certificate, $certificates['cert'])) {
          throw $this->createSslRuntimeException('Error exporting x509 certificate', $key);
        }
        $pkey = @openssl_pkey_get_private($contents, $passphrase);
        if (FALSE === $pkey) {
          throw $this->createSslRuntimeException('Error reading private key', $key);
        }
        if (!@openssl_pkey_export($pkey, $certificates['pkey'])) {
          throw $this->createSslRuntimeException('Error exporting private key', $key);
        }
        break;
    }

    if (!isset($certificates[self::CERT], $certificates[self::PKEY])) {
      throw $this->createRuntimeException("Cannot read certificate parts 'cert' and 'pkey'", $key);
    }

    return $certificates;
  }

  /**
   * Create a passwordless certificate.
   */
  public function createPasswordlessCertificate(array $certificates, string $format, ?KeyInterface $key): string {
    $cert = $certificates[self::CERT] ?? NULL;
    if (!isset($cert)) {
      throw $this->createRuntimeException('Certificate part "cert" not found', $key);
    }

    $pkey = $certificates[self::PKEY] ?? NULL;
    if (!isset($pkey)) {
      throw $this->createRuntimeException('Certificate part "pkey" not found', $key);
    }

    $output = '';
    switch ($format) {
      case self::FORMAT_PEM:
        $parts = ['', ''];
        if (!@openssl_x509_export($cert, $parts[0])) {
          throw $this->createSslRuntimeException('Cannot export certificate', $key);
        }
        if (!@openssl_pkey_export($pkey, $parts[1])) {
          throw $this->createSslRuntimeException('Cannot export private key', $key);
        }
        $extracerts = $certificates['extracerts'] ?? NULL;
        if (is_array($extracerts)) {
          foreach ($extracerts as $extracert) {
            $part = '';
            if (!@openssl_x509_export($extracert, $part)) {
              throw $this->createSslRuntimeException('Cannot export certificate', $key);
            }
            // $parts[] = $part;
          }
        }
        $output = implode('', $parts);
        break;

      case self::FORMAT_PFX:
        if (!@openssl_pkcs12_export($cert, $output, $pkey, '')) {
          throw $this->createSslRuntimeException('Cannot export certificate', $key);
        }
        break;

      default:
        throw $this->createSslRuntimeException(sprintf('Invalid format: %s', $format), $key);
    }

    return $output;
  }

  /**
   * Create a runtime exception.
   */
  public function createRuntimeException(string $message, ?KeyInterface $key, ?string $sslError = NULL): RuntimeException {
    if (NULL !== $sslError) {
      $message .= ' (' . $sslError . ')';
    }
    // @fixme Error: Typed property …::$logger must not be accessed before initialization.
    if (isset($this->logger)) {
      $this->logger->error('@key: @message', [
        '@key' => $key?->id(),
        '@message' => $message,
      ]);
    }

    return new RuntimeException($message);
  }

  /**
   * Create an SSL runtime exception.
   */
  public function createSslRuntimeException(string $message, ?KeyInterface $key): RuntimeException {
    return $this->createRuntimeException($message, $key, openssl_error_string() ?: NULL);
  }

}
