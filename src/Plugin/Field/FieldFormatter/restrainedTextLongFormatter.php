<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\more_fields\Truncator;
use Drupal\Core\Template\Attribute;

/**
 * Plugin implementation of the 'text_long, text_with_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "restrained_text",
 *   label = @Translation("Restrained text"),
 *   field_types = {
 *     "text_long",
 *     "text_with_summary"
 *   }
 * )
 */
class restrainedTextLongFormatter extends StringFormatter {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layoutgenentitystyles_view' => 'more_fields/restrained-field',
      'resumed' => 60,
      'count_by_chars' => false,
      'clean_html' => false,
      'message' => t("Log in to have full access to the article"),
      "link_label" => t("Connection"),
      "link" => "/user/login",
      "display_login_block" => false
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // utilile pour mettre à jour le style
      'layoutgenentitystyles_view' => [
        '#type' => 'hidden',
        "#value" => $this->getSetting("layoutgenentitystyles_view")
      ],
      'resumed' => [
        '#title' => t('Number of characters'),
        '#type' => 'number',
        '#default_value' => $this->getSetting("resumed")
      ],
      'count_by_chars' => [
        '#title' => t('Count by chars'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting("count_by_chars")
      ],
      'clean_html' => [
        '#title' => t('clean_html'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting("clean_html")
      ],
      'display_login_block' => [
        '#title' => t('display login block'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting("display_login_block")
      ],
      'message' => [
        '#title' => t('message for non subscribers'),
        '#type' => 'textfield',
        '#value' => $this->getsetting("message")
      ],
      'link_label' => [
        '#title' => t('label for redirect link'),
        '#type' => 'textfield',
        '#value' => $this->getsetting("link_label")
      ]
    ] + parent::settingsForm($form, $form_state);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $display_login_block = (bool) $this->getSetting('display_login_block');
    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    $state = (\Drupal::currentUser()->id() && $display_login_block) ?? false;
    $Attribute = new Attribute([
      'class' => [
        'restrainded-field-container'
      ]
    ]);
    $Attribute->addClass($display_login_block ? 'display_login_block' : '');
    foreach ($items as $delta => $item) {
      // $escapedItem = Html::escape($item->value);
      $escapedItem = $item->value;
      $options = [
        'length_in_chars' => (bool) $this->getSetting('count_by_chars'),
        'ellipsis' => '…'
      ];
      
      $value = ($state) ? $escapedItem : Truncator::truncate($escapedItem, (int) $this->getSetting("resumed"), $options);
      //
      $elements[$delta] = [
        '#theme' => 'more_fields_restrained_text_formatter',
        '#attributes' => $Attribute,
        '#settings' => $this->getSettings(),
        '#item' => [
          'value' => $this->viewValue($value),
          'offlineConfig' => $state ? [
            "message" => (string) $this->getSetting("message"),
            "link" => (string) $this->getSetting("link"),
            "link_label" => (string) $this->getSetting("link_label")
          ] : false
        ]
      ];
    }
    return $elements;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    return $elements;
  }
  
  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *        One field item.
   *        
   * @return array The textual output generated as a render array.
   */
  protected function viewValue($value) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return [
      '#type' => 'inline_template',
      '#template' => '{{ value|raw }}',
      '#context' => [
        'value' => $value
      ]
    ];
  }
  
}
