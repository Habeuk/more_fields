<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Field\FieldItemListInterface;
// use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'FieldGalleries' formatter.
 *
 * @FieldFormatter(
 *   id = "more_field_gallery_overlay",
 *   label = @Translation("Gallery overlay"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image" 
 *   }
 * )
 */
class GalleryOverlay extends ImageFormatter {

  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      "layoutgenentitystyles_view" => "more_fields/field-gallery-overlay",
      'overlay_container' => 'paragraph',
      'overlay_transition_time' => 800,
      'image_overlay_style' => 'wide',
      'image_link' => 'file',
      'field_classes' => [
        'image_wrappers_class' => 'col-lg-3 col-md-6 col-sm-6 col-xs-12 image',
        'each_image_class' => 'img-responsive',
        'icon_class' => 'fa fa-plus-circle',
        'field_class' => '',
        'gallery_class' => '',
      ],
    ] + parent::defaultSettings();
  }

  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $parentForm =    parent::settingsForm($form, $form_state);
    $conf = $this->getSettings();
    // dd($conf); 
    $elements['gabarit'] = [
      '#type' => 'select',
      '#title' => $this->t('Parent of overlay image'),
      '#default_value' => $conf['overlay_container'],
      '#options' => [
        "paragraph" => "Current Paragraph",
        "body" => "Body"
      ]
    ];
    $elements['image_overlay_style'] = [
      '#title' => $this->t('Image overlay style'),
      '#default_value' => $conf['image_overlay_style'],
    ] + $parentForm['image_style'];

    $elements['image_link'] = [
      '#type' => 'hidden',
      '#default_value' => 'file'
    ];
    // // dump($conf);

    $elements += $parentForm;

    $elements["overlay_transition_time"] = [
      '#title' => t('Transition speed \'in milliseconds\''),
      '#type' => 'number',
      '#default_value' => $conf["overlay_transition_time"]
    ];

    $elements['field_classes'] = [
      '#type' => 'details',
      '#title' => $this->t('Field classes'),
      '#open' => false,
      '#weight' => 11
    ];
    $elements['field_classes']['field_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field container class'),
      '#default_value' => $conf['field_classes']['field_class'],
    ];

    $elements['field_classes']['gallery_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gallery class'),
      '#default_value' => $conf['field_classes']['gallery_class'],
    ];

    $elements['field_classes']['image_wrappers_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field container class'),
      '#default_value' => $conf['field_classes']['image_wrappers_class']
    ];
    $elements['field_classes']['each_image_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field label class'),
      '#default_value' => $conf['field_classes']['each_image_class']
    ];
    $elements['field_classes']['icon_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field value class'),
      '#default_value' => $conf['field_classes']['icon_class'],
      '#description' => $this->t("font awesome classes of the icon that should be rendered")
    ];
    return $elements;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('gabarit: @gabarit', [
      '@gabarit' => $this->getSetting('gabarit')
    ]);
    return array_merge($summary, parent::settingsSummary());
  }

  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $settings = $this->getSettings();
    foreach ($elements as  &$element) {
      if (!isset($element["#item_attributes"]["class"])) {
        $element["#item_attributes"]["class"] = [];
      }
      $element["#item_attributes"]["class"] = array_merge($element["#item_attributes"]["class"], explode(" ", $settings["field_classes"]['each_image_class']));
      /**
       * @var \Drupal\Core\Url $url
       */
      $url = $element["#url"];
      $path = "/" . implode("/", array_slice(explode("/", $url->getUri()), -2, 2));


      /**
       * @var \Drupal\image\Entity\ImageStyle $overlayImageStyle
       */
      $overlayImageStyle = ImageStyle::load($settings["image_overlay_style"]);
      $overlayUrl = $overlayImageStyle->buildUrl($path);
      /**
       * @var  \Drupal\Core\Url  $url
       */
      $element["#url"] = null;
      $element = [
        "image" => $element,
        "url" => $overlayUrl
      ];
    }

    $longueurChaine = 8;
    $rand = bin2hex(random_bytes($longueurChaine));

    $settings["field_classes"]["field_attribute"] = [
      "class" => $settings["field_classes"]["field_class"],
      "id" => "gallery-" . $rand
    ];

    unset($settings["field_classes"]["field_class"]);

    $settings["field_classes"]["gallery_attribute"] = [
      "class" => $settings["field_classes"]["gallery_class"],
      "id" => "image-gallery-" . $rand
    ];

    unset($settings["field_classes"]["gallery_class"]);

    return [
      "#theme" => "more_field_gallery_overlay",
      "#elements" => $elements,
      "#image_attributes" => $settings["field_classes"] + [
        "overlay_attributes" => [
          "data-transition-time" => $settings["overlay_transition_time"],
          "data-overlay-container" => $settings["overlay_container"]
        ],
      ],
      "#settings" => [
        "fade_time" => (int) $settings["overlay_transition_time"]
      ]
    ];
  }
}
