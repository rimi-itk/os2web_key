# OS2Web key

Key types and providers for OS2Web built on the [Key module](https://www.drupal.org/project/key).

## Installation

``` shell
composer require os2web/os2web_key
drush pm:install os2web_key
```

## Key types

### Certificate

This key type handles [PKCS 12](https://en.wikipedia.org/wiki/PKCS_12) or [Privacy-Enhanced Mail
(PEM)](https://en.wikipedia.org/wiki/Privacy-Enhanced_Mail) certificate with an optional password (passphrase).

Use in a form:

``` php
$form['key'] => [
 '#type' => 'key_select',
 '#key_filters' => [
   'type' => 'os2web_key_certificate',
 ],
];
```

The [`CertificateHelper`](https://github.com/OS2web/os2web_key/blob/main/src/CertificateHelper.php) can be used to get
the actual certificates (parts):

``` php
<?php

use Drupal\os2web_key\CertificateHelper;
use Drupal\key\KeyRepositoryInterface;

// Use dependency injection for this.
/** @var KeyRepositoryInterface $repository */
$repository = \Drupal::service('key.repository');
/** @var CertificateHelper $helper */
$helper = \Drupal::service(CertificateHelper::class);

// Use `drush key:list` to list your keys.
$key = $repository->getKey('my_key');

// Get the actual passwordless certificates.
$certificates = $helper->getCertificates($key);
```

**Note**: The parsed certificates have no password.

### OpenID Connect (OIDC)

Example use in a form:

``` php
$form['key'] => [
 '#type' => 'key_select',
 '#key_filters' => [
   'type' => 'os2web_key_oidc,
 ],
];
```

Get the OIDC config:

``` php
<?php

use Drupal\key\KeyRepositoryInterface;
use Drupal\os2web_key\Plugin\KeyType\OidcKeyType;

// Use dependency injection for this.
/** @var KeyRepositoryInterface $repository */
$repository = \Drupal::service('key.repository');

$key = $repository->getKey('openid_connect_ad');
$values = json_decode($key->getKeyValue(), TRUE, 512, JSON_THROW_ON_ERROR);
$discoveryUrl = $values[OidcKeyType::DISCOVERY_URL];
$clientId = $values[OidcKeyType::CLIENT_ID];
$clientSecret = $values[OidcKeyType::CLIENT_SECRET];
```

See [the Key Developer Guide](https://www.drupal.org/docs/contributed-modules/key/developer-guide) for details and more
examples.

## Providers

### `@todo` Azure Key Vault

<https://azure.microsoft.com/en-us/products/key-vault>

### `@todo` Infisical

<https://infisical.com/>

## Coding standards

Our coding are checked by GitHub Actions (cf. [.github/workflows/pr.yml](.github/workflows/pr.yml)). Use the commands
below to run the checks locally.

### PHP

```shell
docker run --rm --volume ${PWD}:/app --workdir /app itkdev/php8.1-fpm composer install
# Fix (some) coding standards issues
docker run --rm --volume ${PWD}:/app --workdir /app itkdev/php8.1-fpm composer coding-standards-apply
# Check that code adheres to the coding standards
docker run --rm --volume ${PWD}:/app --workdir /app itkdev/php8.1-fpm composer coding-standards-check
```

### Markdown

```shell
docker run --rm --volume $PWD:/md peterdavehello/markdownlint markdownlint --ignore vendor --ignore LICENSE.md '**/*.md' --fix
docker run --rm --volume $PWD:/md peterdavehello/markdownlint markdownlint --ignore vendor --ignore LICENSE.md '**/*.md'
```

## Code analysis

We use [PHPStan](https://phpstan.org/) for static code analysis.

Running statis code analysis on a standalone Drupal module is a bit tricky, so we use a helper script to run the
analysis:

```shell
docker run --rm --volume ${PWD}:/app --workdir /app itkdev/php8.1-fpm ./scripts/code-analysis
```
