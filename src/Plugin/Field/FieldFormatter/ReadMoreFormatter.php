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
      'text_display' => true
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
    if ($this->getSetting('link_to_entity'))
      foreach ($elements as &$element) {
        $element['#options']['attributes']['class'][] = $this->getSetting('disable_button') ? '' : 'htl-btn';
        $element['#options']['attributes']['class'][] = $this->getSetting('size');
        $element['#options']['attributes']['class'][] = $this->getSetting('variant');
        $element['#options']['attributes']['class'][] = !$this->getSetting('haslinktag') ? 'hasnotlink' : '';
        $element['#options']['attributes']['class'][] = $this->getSetting('custom_class');
        //
        $element['#title'] = $this->viewValueFull($this->t($this->getSetting('text_display')));
      }
    return $elements;
  }
  
  /**
   * Afin de permettre d'ajouter des SVG ou autre balise au niveau des buttons.
   *
   * @param string $value
   * @return array
   */
  protected function viewValueFull($value) {
    return [
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
  }
}
