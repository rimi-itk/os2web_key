<?php

namespace Drupal\os2web_key\Plugin\KeyInput;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyInputBase;
use Drupal\os2web_key\Plugin\KeyType\OidcKeyType;

/**
 * Input for OpenID Connect authentication.
 *
 * @KeyInput(
 *   id = "os2web_key_oidc",
 *   label = @Translation("OpenID Connect (OIDC)")
 * )
 */
class OidcKeyInput extends KeyInputBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      OidcKeyType::DISCOVERY_URL => '',
      OidcKeyType::CLIENT_ID => '',
      OidcKeyType::CLIENT_SECRET => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[OidcKeyType::DISCOVERY_URL] = [
      '#type' => 'url',
      '#title' => $this->t('Discovery url'),
      '#default_value' => $this->configuration[OidcKeyType::DISCOVERY_URL],
      '#required' => TRUE,
    ];

    $form[OidcKeyType::CLIENT_ID] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $this->configuration[OidcKeyType::CLIENT_ID],
      '#required' => TRUE,
    ];

    $form[OidcKeyType::CLIENT_SECRET] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $this->configuration[OidcKeyType::CLIENT_SECRET],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function processSubmittedKeyValue(FormStateInterface $form_state) {
    $values = $form_state->getValues();
    return [
      'submitted' => $values,
      'processed_submitted' => $values,
    ];
  }

}
