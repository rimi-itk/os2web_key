<?php

namespace Drupal\os2web_key;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\key\KeyInterface;
use Drupal\os2web_key\Exception\RuntimeException;
use Drupal\os2web_key\Plugin\KeyProvider\AbstractCertificateKeyProvider;
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
   * Read a certificate.
   */
  public function parseCertificates(
    string $contents,
    string $format,
    ?string $passphrase,
    AbstractCertificateKeyProvider $provider,
    ?KeyInterface $key,
  ): array {
    $certificates = [
      'cert' => NULL,
      'pkey' => NULL,
    ];
    switch ($format) {
      case self::FORMAT_PFX:
        if (!openssl_pkcs12_read($contents, $certificates, $passphrase)) {
          throw $this->createSslRuntimeException('Error reading certificate', $provider, $key);
        }
        break;

      case self::FORMAT_PEM:
        $certificate = @openssl_x509_read($contents);
        if (FALSE === $certificate) {
          throw $this->createSslRuntimeException('Error reading certificate', $provider, $key);
        }
        if (!@openssl_x509_export($certificate, $certificates['cert'])) {
          throw $this->createSslRuntimeException('Error exporting x509 certificate', $provider, $key);
        }
        $pkey = @openssl_pkey_get_private($contents, $passphrase);
        if (FALSE === $pkey) {
          throw $this->createSslRuntimeException('Error reading private key', $provider, $key);
        }
        if (!@openssl_pkey_export($pkey, $certificates['pkey'])) {
          throw $this->createSslRuntimeException('Error exporting private key', $provider, $key);
        }
        break;
    }

    if (!isset($certificates['cert'], $certificates['pkey'])) {
      throw $this->createRuntimeException("Cannot read certificate parts 'cert' and 'pkey'", $provider, $key);
    }

    return $certificates;
  }

  /**
   * Create a passwordless certificate.
   */
  public function createPasswordlessCertificate(array $certificates, string $format, AbstractCertificateKeyProvider $provider, ?KeyInterface $key): string {
    $cert = $certificates['cert'] ?? NULL;
    if (!isset($cert)) {
      throw $this->createRuntimeException('Certificate part "cert" not found', $provider, $key);
    }

    $pkey = $certificates['pkey'] ?? NULL;
    if (!isset($pkey)) {
      throw $this->createRuntimeException('Certificate part "pkey" not found', $provider, $key);
    }

    $output = '';
    switch ($format) {
      case self::FORMAT_PEM:
        $parts = ['', ''];
        if (!@openssl_x509_export($cert, $parts[0])) {
          throw $this->createSslRuntimeException('Cannot export certificate', $provider, $key);
        }
        if (!@openssl_pkey_export($pkey, $parts[1])) {
          throw $this->createSslRuntimeException('Cannot export private key', $provider, $key);
        }
        $output = implode('', $parts);
        break;

      case self::FORMAT_PFX:
        if (!@openssl_pkcs12_export($cert, $output, $pkey, '')) {
          throw $this->createSslRuntimeException('Cannot export certificate', $provider, $key);
        }
        break;

      default:
        throw $this->createSslRuntimeException(sprintf('Invalid format: %s', $format), $provider, $key);
    }

    return $output;
  }

  /**
   * Create a runtime exception.
   */
  public function createRuntimeException(string $message, AbstractCertificateKeyProvider $provider, ?KeyInterface $key, ?string $sslError = NULL): RuntimeException {
    if (NULL !== $sslError) {
      $message .= ' (' . $sslError . ')';
    }
    // @fixme Error: Typed property …::$logger must not be accessed before initialization.
    if (isset($this->logger)) {
      $this->logger->error('@id.@key: @message', [
        '@id' => $provider->getPluginId(),
        '@key' => $key?->id(),
        '@message' => $message,
      ]);
    }

    return new RuntimeException($message);
  }

  /**
   * Create an SSL runtime exception.
   */
  public function createSslRuntimeException(string $message, AbstractCertificateKeyProvider $provider, ?KeyInterface $key): RuntimeException {
    return $this->createRuntimeException($message, $provider, $key, openssl_error_string() ?: NULL);
  }

}
