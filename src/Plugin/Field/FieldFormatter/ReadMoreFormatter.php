<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Render\Element\InlineTemplate;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_read_more",
 *   label = @Translation("Read more formatter"),
 *   field_types = {
 *     "string",
 *     "uri",
 *     "integer",
 *   }
 * )
 */
class ReadMoreFormatter extends HtlBtn {
  
  public static function defaultSettings() {
    return [
      'text_display' => 'Read more',
      'text_display' => true,
      'icone' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="2rem" class="ms-3" fill="currentColor"><path d="M502.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-128-128c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l370.7 0-73.4 73.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l128-128z"></path></svg>',
      'after_text' => true
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'text_display' => [
        '#type' => 'textarea',
        '#title' => 'Texte à afficher',
        '#default_value' => $this->getSetting('text_display'),
        '#required' => true,
        '#description' => "don't forget to active linck to content"
      ],
      'icone' => [
        '#type' => 'textarea',
        '#title' => 'icone',
        '#default_value' => $this->getSetting('icone')
      ],
      'after_text' => [
        '#type' => 'checkbox',
        '#title' => 'show icone after texte ?',
        '#default_value' => $this->getSetting('after_text')
      ]
    ] + parent::settingsForm($form, $form_state);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    // dd($items);
    
    /**
     *
     * @var \Drupal\Core\Language\Language $currentLanguage
     */
    $currentLanguage = \Drupal::languageManager()->getLanguage($langcode);
    
    foreach ($elements as &$element) {
      /**
       *
       * @var \Drupal\Core\Url $url
       */
      if (!empty($elements[0]["#url"])) {
        $url = $elements[0]["#url"];
        $url->setOption("language", $currentLanguage);
      }
    }
    $after_text = $this->getSetting('after_text');
    $icone = $this->getSetting('icone');
    if ($this->getSetting('link_to_entity'))
      foreach ($elements as &$element) {
        $element['#options']['attributes']['class'][] = $this->getSetting('disable_button') ? '' : 'htl-btn';
        $element['#options']['attributes']['class'][] = $this->getSetting('size');
        $element['#options']['attributes']['class'][] = $this->getSetting('variant');
        $element['#options']['attributes']['class'][] = !$this->getSetting('haslinktag') ? 'hasnotlink' : '';
        $element['#options']['attributes']['class'][] = $this->getSetting('custom_class');
        //
        $element['#title'] = $this->viewValueFull($this->t($this->getSetting('text_display')), $after_text, $icone);
      }
    return $elements;
  }
  
  /**
   * Afin de permettre d'ajouter des SVG ou autre balise au niveau des buttons.
   *
   * @param string $value
   * @return array
   */
  protected function viewValueFull($value, $after_text, $icone) {
    $data = [];
    if (!$after_text && $icone)
      $data[] = [
        '#markup' => $icone,
        '#allowed_tags' => [
          'svg',
          'title',
          'g',
          'path',
          'div',
          'defs',
          'span'
        ]
      ];
    $data[] = [
      '#markup' => $value,
      '#allowed_tags' => [
        'svg',
        'title',
        'g',
        'path',
        'div',
        'defs',
        'span'
      ]
    ];
    if ($after_text && $icone)
      $data[] = [
        '#markup' => $icone,
        '#allowed_tags' => [
          'svg',
          'title',
          'g',
          'path',
          'div',
          'defs',
          'span'
        ]
      ];
    return $data;
  }
}
