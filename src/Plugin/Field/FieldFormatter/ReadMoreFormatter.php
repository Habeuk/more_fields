<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;

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
        '#type' => 'textfield',
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
    if ($this->getSetting('link_to_entity'))
      foreach ($elements as &$element) {
        $element['#options']['attributes']['class'][] = $this->getSetting('disable_button') ? '' : 'htl-btn';
        $element['#options']['attributes']['class'][] = $this->getSetting('size');
        $element['#options']['attributes']['class'][] = $this->getSetting('variant');
        $element['#options']['attributes']['class'][] = !$this->getSetting('haslinktag') ? 'hasnotlink' : '';
        $element['#options']['attributes']['class'][] = $this->getSetting('custom_class');
        //
        if (isset($element['#title']['#context']['value']))
          $element['#title']['#context']['value'] = $this->t($this->getSetting('text_display'));
      }
    return $elements;
  }
  
}