<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\video\ProviderManagerInterface;
use Drupal\video\Plugin\Field\FieldFormatter\VideoPlayerListFormatter;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Template\Attribute;
use Drupal\fullswiperoptions\Fullswiperoptions;
use Drupal\Component\Serialization\Json;
use Drupal\image\Entity\ImageStyle;
use Drupal\more_fields_video\Entity\MultiformatVideo;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Plugin implementation of the 'text_long, text_with_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_hbk_galleries_formatter",
 *   label = @Translation("Galleries Files (images and videos)"),
 *   field_types = {
 *     "more_fields_hbk_file"
 *   }
 * )
 */
class HbkGalleriesFilesFormatter extends GenericFileFormatter implements ContainerFactoryPluginInterface {
  protected $imageStyleStorage;
  protected $videoFormatter;
  protected $imageFormatter;
  
  /**
   *
   * @var \Drupal\Core\File\FileUrlGenerator $fileUrlGenerator
   */
  protected $fileUrlGenerator;
  
  /**
   *
   * @var EntityStorageInterface $multifomatHandler
   */
  protected $multiformatHandler;
  
  /**
   *
   * @var EntityTypeManagerInterface $entityManager
   */
  protected $entityManager;
  
  /**
   *
   * @var EntityStorageInterface $fileHandler
   */
  protected $fileHandler;
  
  /**
   * Constructs a new instance of the plugin.
   *
   * @param string $plugin_id
   *        The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *        The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *        The definition of the field to which the formatter is associated.
   * @param array $settings
   *        The formatter settings.
   * @param string $label
   *        The formatter label display setting.
   * @param string $view_mode
   *        The view mode.
   * @param array $third_party_settings
   *        Third party settings.
   */
  public function __construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, EntityStorageInterface $file_handler, EntityTypeManagerInterface $entity_type_manager_interface, FileUrlGeneratorInterface $file_url_generator = NULL) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->videoFormatter = new VideoPlayerListFormatter($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user);
    $this->imageFormatter = new ImageFormatter($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage, $file_url_generator);
    $this->imageStyleStorage = $image_style_storage;
    if (!$file_url_generator) {
      @trigger_error(
        'Calling ImageFormatter::__construct() without the $file_url_generator argument is deprecated in drupal:9.3.0 and the $file_url_generator argument will be required in drupal:10.0.0. See https://www.drupal.org/node/2940031',
        E_USER_DEPRECATED);
      $file_url_generator = \Drupal::service('file_url_generator');
    }
    $this->fileUrlGenerator = $file_url_generator;
    $this->fileHandler = $file_handler;
    $this->entityManager = $entity_type_manager_interface;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['view_mode'], $configuration['third_party_settings'], $container->get(
      'current_user'), $container->get('entity_type.manager')->getStorage('image_style'), $container->get('entity_type.manager')->getStorage('file'), $container->get('entity_type.manager'), $container->get(
      'file_url_generator'));
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default = [
      "video_settings" => VideoPlayerListFormatter::defaultSettings(),
      "image_settings" => ImageFormatter::defaultSettings(),
      "thumbs_settings" => ImageFormatter::defaultSettings(),
      "layoutgenentitystyles_view" => "more_fields/galleries-files-images-videos"
    ];
    $default["video_settings"]["field_extension"] = "mp4, ogg, webm";
    $default["image_settings"]["field_extension"] = "png, gif, jpg, jpeg, webp";
    unset($default["thumbs_settings"]["image_link"]);
    unset($default['video_settings']['width']);
    unset($default['video_settings']['height']);
    return $default;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    
    $default_configs = $this->defaultSettings();
    $configs = $this->getSettings();
    
    $video_settings = $configs['video_settings'] ?? $default_configs["video_default"];
    $image_settings = $configs['image_settings'] ?? $default_configs["image_default"];
    $thumbs_settings = $configs['thumbs_settings'] ?? $default_configs["thumbs_settings"];
    // dump([$default_configs, $image_settings]);
    $temp_form = [];
    $image_settings_fields = [
      'image_style',
      'image_link',
      'image_loading',
      'field_extension'
    ];
    $video_settings_fields = [
      "controls",
      "autoplay",
      "loop",
      "muted",
      "preload",
      'field_extension'
    ];
    $thumbs_settings_fields = [
      "image_style",
      "image_loading"
    ];
    
    $temp_form['video_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Video Settings'),
      '#tree' => TRUE,
      '#open' => FALSE
    ];
    $temp_form['image_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Image Settings'),
      '#tree' => TRUE,
      '#open' => FALSE
    ];
    $temp_form['thumbs_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Thumbs Settings'),
      '#tree' => TRUE,
      '#open' => FALSE
    ];
    
    $video_settings_form = $this->videoFormatter->settingsForm($form, $form_state);
    $image_settings_form = $this->imageFormatter->settingsForm($form, $form_state);
    $thumbs_settings_form = $this->imageFormatter->settingsForm($form, $form_state);
    
    unset($video_settings_form["width"]);
    unset($video_settings_form["height"]);
    unset($thumbs_settings_form["image_link"]);
    
    $field_extension = [
      "#title" => $this->t("field type extension"),
      "#type" => "textfield",
      "#default_value" => ""
    ];
    $temp_form["video_settings"]["field_extension"] = $field_extension;
    $temp_form["image_settings"]["field_extension"] = $field_extension;
    
    $temp_form['image_settings'] = array_merge($temp_form['image_settings'], $image_settings_form);
    $temp_form['video_settings'] = array_merge($temp_form['video_settings'], $video_settings_form);
    $temp_form["thumbs_settings"] = array_merge($temp_form["thumbs_settings"], $thumbs_settings_form);
    
    // utilile pour mettre à jour le style
    $form['layoutgenentitystyles_view'] = [
      '#type' => 'hidden',
      // "#value" => "more_fields/field-files",
      "#value" => "more_fields/galleries-files-images-videos"
    ];
    
    // dump($video_settings);
    // update default value for video
    foreach ($video_settings_fields as $value) {
      $temp_form["video_settings"][$value]["#default_value"] = $video_settings[$value];
    }
    
    // update default value for image
    foreach ($image_settings_fields as $value) {
      $temp_form["image_settings"][$value]["#default_value"] = $image_settings[$value];
    }
    
    // update default value for thumgs
    foreach ($thumbs_settings_fields as $value) {
      $temp_form["thumbs_settings"][$value]["#default_value"] = $thumbs_settings[$value];
    }
    
    $form = array_merge($form, $temp_form);
    return $form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $files = $this->getEntitiesToView($items, $langcode);
    $image_settings = $this->getSetting("image_settings");
    $video_settings = $this->getSetting("video_settings");
    $image_style_setting = $this->getSetting("image_settings")['image_style'];
    $image_loading_settings = $image_settings['image_loading'];
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }
    // reste à null car ne peut faire office de lien
    $url = NULL;
    $link_file = null;
    $elements = [];
    /**
     *
     * @var File $file
     */
    foreach ($files as $delta => $file) {
      $file_extension = pathinfo($file->getFileUri(), PATHINFO_EXTENSION);
      if (strpos($image_settings["field_extension"], $file_extension) !== false) {
        // Gestion des images
        $this->viewImageElement($file, $elements, $url, $image_style_setting, $base_cache_tags, $image_loading_settings, $delta, $link_file);
      }
      elseif (strpos($video_settings["field_extension"], $file_extension) !== false) {
        $thumb_file = null;
        // On va verifier par la suite s'il ya effectivement un image d'attente.
        $this->viewVideoElement($file, $elements, $delta, $thumb_file);
      }
    }
    return [
      "#theme" => "more_field_galleries_images_videos",
      "#items" => $elements
    ];
  }
  
  /**
   * create the view of the file
   *
   * @param File $file
   *        the file to be show
   * @param File|null $thumb_file
   *        the file's thumb if it has been generated
   */
  protected function viewVideoElement($file, &$elements, $delta, $thumb_file = null) {
    // dump($this->getSetting("video_settings"));
    $video_items = [];
    $video_items[] = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
    $video_settings = $this->getSetting('video_settings');
    // dump($video_settings);
    $attributes = [
      "class" => [
        "swiper-video-full",
        "swiper-zoom-target"
      ]
    ];
    
    $attributes["preload"] = $video_settings["preload"] ?? "";
    if (isset($video_settings["autoplay"]) && $video_settings["autoplay"])
      $attributes["autoplay"] = "";
    if (isset($video_settings["loop"]) && $video_settings["loop"])
      $attributes["loop"] = "";
    if (isset($video_settings["muted"]) && $video_settings["muted"])
      $attributes["muted"] = "";
    if (isset($video_settings["controls"]) && $video_settings["controls"])
      $attributes["controls"] = "";
    if (isset($thumb_file)) {
      $thumb_url = $this->fileUrlGenerator->generateString($thumb_file->getFileUri());
      $attributes['poster'] = $thumb_url;
    }
    // dump($attributes);
    $video_attributes = new Attribute($attributes);
    $elements[$delta] = [
      'content' => [
        '#theme' => 'more_fields_video_player_formatter',
        '#items' => $video_items,
        '#video_attributes' => $video_attributes
      ],
      '#attributes' => []
    ];
  }
  
  /**
   *
   * @param File $file
   */
  protected function viewImageElement($file, &$elements, $url, $image_style_setting, $base_cache_tags, $image_loading_settings, $delta, $link_file = NULL) {
    if (isset($link_file)) {
      $image_uri = $file->getFileUri();
      $url = $this->fileUrlGenerator->generate($image_uri);
    }
    $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());
    // Extract field item attributes for the theme function, and unset them
    // from the $item so that the field template does not re-render them.
    $item = $file->_referringItem;
    if (isset($item)) {
      $item_attributes = $item->_attributes;
      # code...
      unset($item->_attributes);
    }
    $item_attributes['loading'] = $image_loading_settings['attribute'];
    $item_attributes["class"] = [
      "img-fluide"
    ];
    $elements[$delta] = [
      'content' => [
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#url' => $url,
        '#cache' => [
          'tags' => $cache_tags
        ]
      ],
      '#attributes' => []
    ];
  }
}
