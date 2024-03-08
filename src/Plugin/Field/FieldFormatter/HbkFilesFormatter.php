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

/**
 * Plugin implementation of the 'text_long, text_with_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_hbk_file_formatter",
 *   label = @Translation("Galleries File Image Video"),
 *   field_types = {
 *     "more_fields_hbk_file"
 *   }
 * )
 */
class HbkFilesFormatter extends GenericFileFormatter implements ContainerFactoryPluginInterface {
  protected $imageStyleStorage;
  protected $videoFormatter;
  protected $imageFormatter;
  
  /**
   *
   * @var Drupal/Core/File/FileUrlGenerator $fileUrlGenerator
   */
  protected $fileUrlGenerator;
  
  /**
   *
   * @var EntityStorageInterface $multifomatHandler
   */
  protected $multiformatHandler;
  
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
   * @param \Drupal\video\ProviderManagerInterface $provider_manager
   *        The video embed provider manager.
   */
  public function __construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, ProviderManagerInterface $provider_manager, AccountInterface $current_user, EntityStorageInterface $image_style_storage, EntityStorageInterface $multiformat_handler, EntityStorageInterface $file_handler, FileUrlGeneratorInterface $file_url_generator = NULL) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->videoFormatter = new VideoPlayerListFormatter($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user);
    $this->imageFormatter = new ImageFormatter($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage, $file_url_generator);
    $this->imageStyleStorage = $image_style_storage;
    if (!$file_url_generator) {
      @trigger_error('Calling ImageFormatter::__construct() without the $file_url_generator argument is deprecated in drupal:9.3.0 and the $file_url_generator argument will be required in drupal:10.0.0. See https://www.drupal.org/node/2940031', E_USER_DEPRECATED);
      $file_url_generator = \Drupal::service('file_url_generator');
    }
    $this->fileUrlGenerator = $file_url_generator;
    $this->multiformatHandler = $multiformat_handler;
    $this->fileHandler = $file_handler;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['view_mode'], $configuration['third_party_settings'], $container->get('video.provider_manager'), $container->get('current_user'), $container->get('entity_type.manager')->getStorage('image_style'), $container->get('entity_type.manager')->getStorage('multiformat_video'), $container->get('entity_type.manager')->getStorage('file'), $container->get('file_url_generator'));
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
      'swiper_main' => Fullswiperoptions::options(),
      'swiper_thumb' => Fullswiperoptions::options(),
      "layoutgenentitystyles_view" => "more_fields/field-files",
      "my_element" => "myddd element"
    ];
    $default["video_settings"]["field_extension"] = "mp4, ogv, webm";
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
    // dump(VideoPlayerListFormatter::defaultSettings());
    $default_configs = $this->defaultSettings();
    $configs = $this->getSettings();
    // dump($default_configs);
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
      "#value" => $this->getSetting("layoutgenentitystyles_view")
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
    
    // ----------------creating Swipers Settings form----------------//
    $swiper_main_options = $this->getSetting('swiper_main');
    
    $form['swiper_main'] = [
      '#title' => $this->t('Main slider'),
      '#type' => 'fieldset',
      '#open' => false
    ];
    Fullswiperoptions::buildGeneralOptionsForm($form['swiper_main'], $swiper_main_options);
    Fullswiperoptions::buildSwiperjsOptions($form['swiper_main'], $swiper_main_options);
    $swiper_thumb_options = $this->getSetting('swiper_thumb');
    
    $form['swiper_thumb'] = [
      '#title' => $this->t('Thumbs slider'),
      '#type' => 'fieldset',
      '#open' => false
    ];
    Fullswiperoptions::buildGeneralOptionsForm($form['swiper_thumb'], $swiper_thumb_options);
    Fullswiperoptions::buildSwiperjsOptions($form['swiper_thumb'], $swiper_thumb_options);
    
    $form = array_merge($form, $temp_form);
    return $form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $thumb_elements = [];
    $entity = $items->getEntity();
    $image_settings = $this->getSetting("image_settings");
    $video_settings = $this->getSetting("video_settings");
    $thumbs_settings = $this->getSetting("thumbs_settings");
    $files = $this->getEntitiesToView($items, $langcode);
    // array containing the field type at a given index ("image" or "video")
    $items_types = [];
    $url = NULL;
    $image_link_setting = $image_settings["image_link"] ?? "file";
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }
    
    $image_style_setting = $this->getSetting("image_settings")['image_style'];
    $thumb_image_style_setting = $thumbs_settings["image_style"];
    $image_loading_settings = $image_settings['image_loading'];
    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    $thumb_base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }
    if (!empty($image_style_setting)) {
      $thumb_image_style = $this->imageStyleStorage->load($image_style_setting);
      $thumb_base_cache_tags = $thumb_image_style->getCacheTags();
    }
    /**
     *
     * @var File $file
     */
    foreach ($files as $delta => $file) {
      // get file extension
      $file_extension = pathinfo($file->getFileUri(), PATHINFO_EXTENSION);
      if (strpos($image_settings["field_extension"], $file_extension) !== false) {
        // Gestion des images
        $items_types[] = 'image';
        $this->viewImageElement($file, $elements, $url, $image_style_setting, $base_cache_tags, $image_loading_settings, $delta, isset($link_file) ? $link_file : NULL);
        $this->viewImageElement($file, $thumb_elements, $url, $thumb_image_style_setting, $thumb_base_cache_tags, $thumbs_settings["image_loading"], $delta, isset($link_file) ? $link_file : NULL);
      }
      elseif (strpos($video_settings["field_extension"], $file_extension) !== false) {
        // Gestion des videos
        $items_types[] = 'video';
        $thumb_file = null;
        /**
         *
         * @var MultiformatVideo $multiformat_video
         */
        $multiformat_video = $this->multiformatHandler->load($file->id());
        if (isset($multiformat_video)) {
          $thumb_id = $multiformat_video->getThumbId();
          /**
           *
           * @var File $thumb_file
           */
          $thumb_file = $this->fileHandler->load($thumb_id);
        }
        
        $this->viewVideoElement($file, $elements, $delta, $thumb_file);
        if (isset($thumb_file)) {
          $this->viewThumbElement($thumb_file, $thumb_elements, $thumbs_settings, $delta);
        }
        else {
          $thumb_elements[$delta] = $elements[$delta];
        }
        $video_id = $file->id();
      }
      else {
        // Autres types de fichiers
        $this->viewParentElement($file, $elements, $delta);
        $thumb_elements[$delta] = $elements[$delta];
      }
    }
    
    // generation swiper id
    $base_class = 'hbk3-gallery-';
    $random_id = rand(1000000, 9999999);
    
    $main_slider_attributes = new Attribute([
      "data-key-parent" => $base_class . "parent-" . (string) $random_id,
      "data-key-children" => $base_class . "thumbs-" . (string) $random_id,
      "class" => [
        'swiper-full-options',
        'swiper'
      ]
    ]);
    $thumbs_slider_attributes = new Attribute([
      "data-key-parent" => $base_class . "parent-" . (string) $random_id,
      "data-key-children" => $base_class . "thumbs-" . (string) $random_id,
      "class" => [
        'swiper-full-options',
        'swiper'
      ]
    ]);
    // ////////
    // constructing attributes of the main slide
    $swiper_main = $this->getSetting('swiper_main');
    $swiper_main_options = Fullswiperoptions::formatOptions($swiper_main);
    $main_slider_attributes->setAttribute('data-swiper', Json::encode($swiper_main_options));
    $main_slider_items_attributes = new Attribute([
      "class" => [
        "slide-item",
        "main-slide-item"
      ]
    ]);
    //
    $swipper_attributes_paginations = new Attribute();
    $swipper_attributes_paginations->addClass('swiper-pagination', $swiper_main['pagination_color'], $swiper_main['pagination_postion']);
    //
    $swipper_attributes_buttons_prev = new Attribute();
    $swipper_attributes_buttons_prev->addClass('swiper-button', 'swiper-button-prev', $swiper_main['buttons_color'], $swiper_main['buttons_position']);
    //
    $swipper_attributes_buttons_next = new Attribute();
    $swipper_attributes_buttons_next->addClass('swiper-button', 'swiper-button-next', $swiper_main['buttons_color'], $swiper_main['buttons_position']);
    // ////////
    // Constructing attributes of the thumbs slide
    $swiper_thumb = $this->getSetting('swiper_thumb');
    $swiper_thumb_options = Fullswiperoptions::formatOptions($swiper_thumb);
    $thumbs_slider_attributes->setAttribute('data-swiper', Json::encode($swiper_thumb_options));
    $thumbs_slider_items_attributes = new Attribute([
      "class" => [
        "slide-item",
        "thumb-slide-item"
      ]
    ]);
    //
    $thumbs_attributes_paginations = new Attribute();
    $thumbs_attributes_paginations->addClass('swiper-pagination', $swiper_thumb['pagination_color'], $swiper_thumb['pagination_postion']);
    //
    $thumbs_attributes_buttons_prev = new Attribute();
    $thumbs_attributes_buttons_prev->addClass('swiper-button', 'swiper-button-prev', $swiper_thumb['buttons_color'], $swiper_thumb['buttons_position']);
    //
    $thumbs_attributes_buttons_next = new Attribute();
    $thumbs_attributes_buttons_next->addClass('swiper-button', 'swiper-button-next', $swiper_thumb['buttons_color'], $swiper_thumb['buttons_position']);
    //
    return [
      "#theme" => "more_field_file_image_video",
      "#main_slider_items" => $elements,
      "#main_slider_items_attributes" => $main_slider_items_attributes,
      "#main_slider_attributes" => $main_slider_attributes,
      "#swiperjs_options" => $swiper_main_options,
      "#swipper_attributes_paginations" => $swipper_attributes_paginations,
      "#swipper_attributes_buttons_prev" => $swipper_attributes_buttons_prev,
      "#swipper_attributes_buttons_next" => $swipper_attributes_buttons_next,
      //
      "#thumbs_slider_items" => $thumb_elements,
      "#thumbs_slider_items_attributes" => $thumbs_slider_items_attributes,
      "#thumbs_slider_attributes" => $thumbs_slider_attributes,
      "#thumbs_slider_settings" => $swiper_thumb_options,
      //
      "#thumbs_attributes_paginations" => $thumbs_attributes_paginations,
      '#thumbs_attributes_buttons_prev' => $thumbs_attributes_buttons_prev,
      "#thumbs_attributes_buttons_next" => $thumbs_attributes_buttons_next,
      "#items_types" => $items_types,
      "#videos_settings" => $video_settings
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
      '#theme' => 'more_fields_video_player_formatter',
      '#items' => $video_items,
      '#video_attributes' => $video_attributes
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
      "swiper-image-full",
      "swiper-zoom-target"
    ];
    // dump($item_attributes);
    $elements[$delta] = [
      '#theme' => 'image_formatter',
      '#item' => $item,
      '#item_attributes' => $item_attributes,
      '#image_style' => $image_style_setting,
      '#url' => $url,
      '#cache' => [
        'tags' => $cache_tags
      ]
    ];
  }
  
  /**
   *
   * @param File $file
   */
  protected function viewThumbElement($file, &$elements, $thumbs_settings, $delta) {
    $image_style_setting = $thumbs_settings["image_style"];
    $image_loading_settings = $thumbs_settings["image_loading"];
    
    $arr_attributes = [
      "loading" => $image_loading_settings['attribute'],
      "class" => [
        "swiper-image-full",
        "swiper-zoom-target"
      ]
    ];
    $item_attributes = new Attribute($arr_attributes);
    $uri = $file->getFileUri();
    if ($image_style_setting) {
      /**
       *
       * @var ImageStyle $imageStyle
       */
      $imageStyle = $this->imageStyleStorage->load($image_style_setting);
      $uri = $imageStyle->buildUrl($uri);
    }
    $url = $this->fileUrlGenerator->generateString($uri);
    $elements[$delta] = [
      '#theme' => 'more_fields_thumb_formatter',
      '#item_attributes' => $item_attributes,
      '#url' => $url
    ];
  }
  
  protected function viewParentElement($file, &$elements, $delta) {
    $item = $file->_referringItem;
    $elements[$delta] = [
      '#theme' => 'file_link',
      '#file' => $file,
      '#cache' => [
        'tags' => $file->getCacheTags()
      ]
    ];
    // Pass field item attributes to the theme function.
    if (isset($item->_attributes)) {
      $elements[$delta] += [
        '#attributes' => []
      ];
      $elements[$delta]['#attributes'] += $item->_attributes;
      // Unset field item attributes since they have been included in the
      // formatter output and should not be rendered in the field template.
      unset($item->_attributes);
    }
  }
  
}
