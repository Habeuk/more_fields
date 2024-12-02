<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\more_fields\Truncator;
use Drupal\Core\Template\Attribute;
use Exception;

/**
 * Plugin implementation of the 'text_long, text_with_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "flexible_text_long",
 *   label = @Translation("Flexible text"),
 *   field_types = {
 *     "text_long",
 *     "text_with_summary"
 *   }
 * )
 */
class FlexibleTextLongFormatter extends StringFormatter {

  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'flexible_settings' => [
        'container_classes' => "",
        'conditions' => "",
        'title' => [
          'show' => False,
          'label' => "",
          'class' => ""
        ],
        'value' => [
          'prefix' => "",
          'suffix' => "",
          'class' => "d-flex flex-wrap"
        ]
      ],
    ] + parent::defaultSettings();
  }

  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form1 =  [
      'flexible_settings' =>
      [
        '#type' => 'details',
        '#title' => $this->t('flexibles Settings'),
        '#tree' => TRUE,
        '#open' => FALSE,
        'container_classes' => [
          '#title' => t('Container class'),
          '#type' => 'textfield',
          '#value' => $this->getsetting("flexible_settings.container_class")
        ],
        'conditions' => [
          '#title' => t('conditional field'),
          '#description' => t('>The field that have to exist for this to be field to be rendered'),
          '#type' => 'textfield',
          '#value' => $this->getsetting("flexible_settings.condition")
        ],
        'title' => [
          '#type' => 'details',
          '#title' => $this->t('field title settings'),
          '#tree' => TRUE,
          '#open' => FALSE,
          'show' => [
            '#title' => t('title label'),
            '#type' => 'checkbox',
            '#default_value' => False,
            '#value' => $this->getsetting("flexible_settings")["title"]["show"] ?? $this->defaultSettings()['flexible_settings']["title"]["show"]
          ],
          'label' => [
            '#title' => t('title label'),
            '#type' => 'text_format',
            '#format' => $this->getsetting("flexible_settings")["title"]["label"]["format"] ?? 'basic_html',
            '#value' => $this->getsetting("flexible_settings")["title"]["label"]["value"] ?? $this->defaultSettings()['flexible_settings']["title"]["label"]
          ],
          'class' => [
            '#title' => t('Title classes'),
            '#description' => t('classes for the title'),
            '#type' => 'textfield',
            '#value' => $this->getsetting("flexible_settings")["title"]["class"] ?? $this->defaultSettings()['flexible_settings']["title"]["class"]
          ]
        ],
        "value" => [
          '#type' => 'details',
          '#title' => $this->t('value Settings'),
          '#tree' => TRUE,
          '#open' => FALSE,
          'prefix' => [
            '#title' => t('Prefix'),
            '#type' => 'text_format',
            '#format' => $this->getsetting("flexible_settings")["value"]["prefix"]["format"] ?? "basic_html",
            '#value' => $this->getsetting("flexible_settings")["value"]["prefix"]["value"] ?? $this->defaultSettings()['flexible_settings']["value"]["prefix"]
          ],
          'suffix' => [
            '#title' => t('Prefix'),
            '#type' => 'text_format',
            '#format' => $this->getsetting("flexible_settings")["value"]["suffix"]["format"] ?? "basic_html",
            '#value' => $this->getsetting("flexible_settings")["value"]["suffix"]["value"] ?? $this->defaultSettings()['flexible_settings']["value"]["suffix"]
          ],
          'class' => [
            '#title' => t('field value classes'),
            '#description' => t('classes for the field value'),
            '#type' => 'textfield',
            '#value' => $this->getsetting("flexible_settings")["value"]["class"] ?? $this->defaultSettings()['flexible_settings']["value"]["class"]
          ]
        ]
      ]
    ] + parent::settingsForm($form, $form_state);
    // dd($form);
    return $form1;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $flexibleSettings  = $this->getsetting("flexible_settings");
    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    $showTitle = $flexibleSettings["title"]["show"] ?? $this->defaultSettings()['flexible_settings']["title"]["show"];
    if ($flexibleSettings["conditions"]) {
      $field_name = $flexibleSettings["conditions"];
      $entity = $items->getEntity();
      if (!$entity->hasField($field_name)) {
        throw new Exception(t("the field " . $field_name . " doesn't belong to this entity"));
      }
      if (!$entity->get($field_name)->getValue())
        return [];
    }
    $title  = !$showTitle ? [] : [
      "attributes" => new Attribute([
        'class' =>
        explode(" ", $flexibleSettings["title"]["class"] ?? $this->defaultSettings()['flexible_settings']["title"]["class"]),
      ]),
      "label" => $flexibleSettings["title"]["label"]["value"] ?? $this->defaultSettings()['flexible_settings']["title"]["label"],
    ];
    $params = [
      'prefix' => $flexibleSettings["value"]["prefix"]["value"] ?? $this->defaultSettings()['flexible_settings']["value"]["prefix"],
      'suffix' => $flexibleSettings["value"]["suffix"]["value"] ?? $this->defaultSettings()['flexible_settings']["value"]["suffix"],
      'class' => $flexibleSettings["value"]["class"] ?? $this->defaultSettings()['flexible_settings']["value"]["class"],
    ];
    $Attribute = new Attribute([
      'class' => ['flexible-text-long-container'] + explode(" ", $flexibleSettings["value"]["class"] ?? "")
    ]);

    foreach ($items as $delta => $item) {
      // $value = "";
      $elements[$delta] = [
        '#theme' => 'more_fields_flexible_text_long_formatter',
        '#attributes' => $Attribute,
        '#settings' => $this->getSettings(),
        '#item' => [
          'field' => [
            'value' => $this->viewValue($item->value, $params["prefix"], $params["suffix"], $params["class"]),
            'attributes' => new Attribute(['class' => explode(" ", $params["class"])])
          ]
        ]
      ];
      if ($title) {
        $elements[$delta]["#item"]["title"] = $title;
      }
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
  protected function viewValue($value, $prefix = '', $suffix = '', $class = '') {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return [
      '#type' => 'inline_template',
      '#template' => '{{ value|raw }}',
      '#context' => [
        'value' => "<div class='" . $class . "'>" . $prefix . $value . $suffix . "</div>"
      ]
    ];
  }
}
