<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;

trait TraitHtlBtn {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layoutgenentitystyles_view' => 'more_fields/field-hotlock-btn',
      'size' => '',
      'variant' => 'htl-btn--fade',
      'options_size' => [
        'htl-btn--normal' => 'Normal',
        'htl-btn--big' => 'Big',
        'htl-btn--sm' => 'Small'
      ],
      'options_variant' => [
        'none' => 'aucun',
        'htl-btn--fade' => 'hover Fade by primary',
        'htl-btn--inv' => 'hover Fade by background',
        'htl-btn--bg' => 'hover slide by primary',
        'htl-btn--bg-inv' => 'hover slide by background'
      ],
      'custom_class' => '',
      'haslinktag' => true,
      'disable_button' => false
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Utilile pour mettre à jour le style.
      'layoutgenentitystyles_view' => [
        '#type' => 'hidden',
        '#value' => 'more_fields/field-hotlock-btn'
      ],
      'size' => [
        '#type' => 'select',
        '#title' => 'Taille du bouton',
        '#options' => $this->getSetting('options_size'),
        '#default_value' => $this->getSetting('size')
      ],
      'variant' => [
        '#type' => 'select',
        '#title' => 'Effet au hover',
        '#options' => $this->getSetting('options_variant'),
        '#default_value' => $this->getSetting('variant')
      ],
      'custom_class' => [
        '#type' => 'textfield',
        '#title' => 'Custom class',
        '#default_value' => $this->getSetting('custom_class')
      ],
      'disable_button' => [
        '#type' => 'checkbox',
        '#title' => 'Affiche simplement le lien (sans boutton)',
        '#default_value' => $this->getSetting('disable_button'),
        '#description' => ""
      ],
      'haslinktag' => [
        '#type' => 'checkbox',
        '#title' => 'contient la balise a',
        '#default_value' => $this->getSetting('haslinktag'),
        '#description' => "(checkoff if render not have the a tag : hasLinkTag ?? <br> doit etre OFF pour les rendu suivant integer, string )"
      ]
    ] + parent::settingsForm($form, $form_state);
  }
  
}