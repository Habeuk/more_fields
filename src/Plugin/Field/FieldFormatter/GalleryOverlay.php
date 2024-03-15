<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormBuilderInterface;
// use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;


  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, FileUrlGeneratorInterface $file_url_generator = NULL, FormBuilderInterface $form_builder) {
    parent::__construct($plugin_id, $plugin_definition,  $field_definition,  $settings, $label, $view_mode,  $third_party_settings,  $current_user,  $image_style_storage,  $file_url_generator);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('file_url_generator'),
      $container->get('form_builder')
    );
  }

  public function configAjaxCallback($form,  FormStateInterface $form_state) {
    return $form;
  }

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
        'image_wrappers_class' => 'col-lg-3 col-md-6 col-sm-6 col-xs-12',
        'each_image_class' => 'img-responsive',
        'icon_class' => 'fa fa-plus-circle',
        'field_class' => '',
        'gallery_class' => '',
      ],
      'nb_element_per_pages' => 10,
      'allow_pagination' => False
    ] + parent::defaultSettings();
  }

  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $formId = "layout-builder-update-block";


    $parentForm = parent::settingsForm($form, $form_state);

    $conf = $this->getSettings();

    $elements = [
      "#attributes" => [
        "id" => $formId
      ]
    ];

    $elements['layoutgenentitystyles_view'] = [
      '#type' => 'hidden',
      "#value" => $this->getSetting("layoutgenentitystyles_view")
    ];
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
      "#value" => "file"
    ];
    // // dump($conf);

    $elements += $parentForm;
    $elements["allow_pagination"] = [
      "#type" => "checkbox",
      "#title" => $this->t("Allow Pagination"),
      "#default_value" => $conf["allow_pagination"] ?? False,
      "#ajax" => [
        'callback' => [$this,  'configAjaxCallback'],
        'wrapper' => $formId,
        'effect' => 'fade'
      ]
    ];
    // dd(isset($conf["allow_pagination"]) && $conf["allow_pagination"] == True);
    $elements["nb_element_per_pages"] = [
      '#title' => $this->t('Number of elements per page'),
      '#type' => isset($conf["allow_pagination"]) && $conf["allow_pagination"] == True ? 'number' : "hidden",
      '#default_value' => $conf["nb_element_per_pages"] ?? $this::defaultSettings()["nb_element_per_pages"]
    ];
    $elements["overlay_transition_time"] = [
      '#title' => $this->t('Transition speed \'in milliseconds\''),
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
    // dd($elements["#attributes"]);
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

    \Drupal::messenger()->addStatus("Just reloaded " . $rand, True);


    unset($settings["field_classes"]["field_class"]);

    $settings["field_classes"]["gallery_attribute"] = [
      "class" => $settings["field_classes"]["gallery_class"],
      "id" => "image-gallery-" . $rand
    ];

    unset($settings["field_classes"]["gallery_class"]);

    $datas =  [
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
      ],
    ];

    if ($settings["allow_pagination"]) {
      $datas = $this->formBuilder->getForm('Drupal\more_fields\Form\GalleryPaginationForm', $datas, $settings["nb_element_per_pages"] ?? $this::defaultSettings()["nb_element_per_pages"]);
    }
    return $datas;
  }
}
