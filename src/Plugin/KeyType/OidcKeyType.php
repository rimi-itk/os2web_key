<?php

namespace Drupal\os2web_key\Plugin\KeyType;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyTypeBase;
use Drupal\key\Plugin\KeyTypeMultivalueInterface;

/**
 * Defines a custom key type for OpenID Connect authentication.
 *
 * @KeyType(
 *   id = "os2web_key_oidc",
 *   label = @Translation("OpenID Connect (OIDC)"),
 *   description = @Translation("A set of credentials for a OpenID Connect."),
 *   group = "authentication",
 *   key_value = {
 *     "plugin" = "os2web_key_oidc",
 *     "accepted" = FALSE,
 *   },
 *   multivalue = {
 *     "enabled" = true,
 *     "fields" = {
 *        "discovery_url" = {
 *          "label" = @Translation("Discovery url"),
 *          "required" = true
 *        },
 *       "client_id" = {
 *         "label" = @Translation("Client ID"),
 *         "required" = true
 *       },
 *       "client_secret" = {
 *         "label" = @Translation("Client secret"),
 *         "required" = true
 *       },
 *     }
 *   }
 * )
 */
class OidcKeyType extends KeyTypeBase implements KeyTypeMultivalueInterface {
  public const DISCOVERY_URL = 'discovery_url';
  public const CLIENT_ID = 'client_id';
  public const CLIENT_SECRET = 'client_secret';

  /**
   * {@inheritdoc}
   */
  public static function generateKeyValue(array $configuration) {
    return Json::encode($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function validateKeyValue(array $form, FormStateInterface $form_state, $key_value): void {
    if (empty($key_value)) {
      $form_state->setError($form, $this->t('The key value is empty.'));
      return;
    }

    $definition = $this->getPluginDefinition();
    $fields = $definition['multivalue']['fields'];

    foreach ($fields as $id => $field) {
      if (!is_array($field)) {
        $field = ['label' => $field];
      }

      if (isset($field['required']) && $field['required'] === FALSE) {
        continue;
      }

      if (!isset($key_value[$id])) {
        $form_state->setError($form, $this->t('The key value is missing the field %field.', ['%field' => $id]));
      }
      elseif (empty($key_value[$id])) {
        $form_state->setError($form, $this->t('The key value field %field is empty.', ['%field' => $id]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function serialize(array $array) {
    return Json::encode($array);
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($value) {
    return Json::decode($value);
  }

}
