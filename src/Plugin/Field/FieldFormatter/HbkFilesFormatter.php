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
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Google\Service\HangoutsChat\Resource\Dms;

/**
 * Plugin implementation of the 'text_long, text_with_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_hbk_file_formatter",
 *   label = @Translation("File Image Video"),
 *   field_types = {
 *     "more_fields_hbk_file"
 *   }
 * )
 */
class HbkFilesFormatter extends GenericFileFormatter implements ContainerFactoryPluginInterface {
  protected $imageStyleStorage;
  protected $fileUrlGenerator;
  protected $videoFormatter;
  protected $imageFormatter;
  
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
  public function __construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, ProviderManagerInterface $provider_manager, AccountInterface $current_user, EntityStorageInterface $image_style_storage, FileUrlGeneratorInterface $file_url_generator = NULL) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->videoFormatter = new VideoPlayerListFormatter($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user);
    $this->imageFormatter = new ImageFormatter($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage, $file_url_generator);
    $this->imageStyleStorage = $image_style_storage;
    if (!$file_url_generator) {
      @trigger_error('Calling ImageFormatter::__construct() without the $file_url_generator argument is deprecated in drupal:9.3.0 and the $file_url_generator argument will be required in drupal:10.0.0. See https://www.drupal.org/node/2940031', E_USER_DEPRECATED);
      $file_url_generator = \Drupal::service('file_url_generator');
    }
    $this->fileUrlGenerator = $file_url_generator;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['view_mode'], $configuration['third_party_settings'], $container->get('video.provider_manager'), $container->get('current_user'), $container->get('entity_type.manager')->getStorage('image_style'), $container->get('file_url_generator'));
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default = [
      "video_settings" => VideoPlayerListFormatter::defaultSettings(),
      "image_settings" => ImageFormatter::defaultSettings(),
      "layoutgenentitystyles_view" => "more_fields/field-files",
      "my_element" => "myddd element"
    ];
    $default["video_settings"]["field_extension"] = "mp4, ogv, webm";
    $default["image_settings"]["field_extension"] = "png, gif, jpg, jpeg, webp";
    return $default;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // dump(VideoPlayerListFormatter::defaultSettings());
    $default_configs = $this->defaultSettings();
    $configs = $this->getSettings();
    // dump($configs);
    $video_settings = $configs['video_settings'] ?? $default_configs["video_default"];
    $image_settings = $configs['image_settings'] ?? $default_configs["image_default"];
    // dump([$default_configs, $image_settings]);
    $temp_form = [];
    $image_settings_fields = [
      'image_style',
      'image_link',
      'field_extension'
    ];
    $video_settings_fields = [
      "width",
      "height",
      "controls",
      "autoplay",
      "loop",
      "muted",
      "preload",
      'field_extension'
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
    
    $video_settings_form = $this->videoFormatter->settingsForm($form, $form_state);
    $image_settings_form = $this->imageFormatter->settingsForm($form, $form_state);
    
    $field_extension = [
      "#title" => $this->t("field type extension"),
      "#type" => "textfield",
      "#default_value" => ""
    ];
    $temp_form["video_settings"]["field_extension"] = $field_extension;
    $temp_form["image_settings"]["field_extension"] = $field_extension;
    
    $temp_form['image_settings'] = array_merge($temp_form['image_settings'], $image_settings_form);
    $temp_form['video_settings'] = array_merge($temp_form['video_settings'], $video_settings_form);
    
    $settings_form = [
      // utilile pour mettre à jour le style
      'layoutgenentitystyles_view' => [
        '#type' => 'hidden',
        // "#value" => "more_fields/field-files",
        "#value" => $this->getSetting("layoutgenentitystyles_view")
      ]
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
    
    // dump($temp_form);
    $settings_form = array_merge($settings_form, $temp_form);
    return $settings_form + parent::settingsForm($form, $form_state);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $entity = $items->getEntity();
    $image_settings = $this->getSetting("image_settings");
    $video_settings = $this->getSetting("video_settings");
    
    $files = $this->getEntitiesToView($items, $langcode);
    
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
    $image_loading_settings = $image_settings['image_loading'];
    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
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
        $this->viewImageElement($file, $elements, $url, $image_style_setting, $base_cache_tags, $image_loading_settings, $delta, isset($link_file) ? $link_file : NULL);
      }
      elseif (strpos($video_settings["field_extension"], $file_extension) !== false) {
        // Gestion des videos
        $this->viewVideoElement([
          $file
        ], $elements, $delta);
      }
      else {
        // Autres types de fichiers
        $this->viewParentElement($file, $elements, $delta);
      }
    }
    
    return [
      "#theme" => "more_field_file_image_video",
      "items" => $elements
    ];
  }
  
  protected function viewVideoElement(array $files, &$elements, $delta) {
    $video_items = [];
    foreach ($files as $file) {
      $video_items[] = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
    }
    $elements[$delta] = [
      '#theme' => 'video_player_formatter',
      '#items' => $video_items,
      '#player_attributes' => $this->getSetting('video_settings')
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
    $item_attributes = $item->_attributes;
    unset($item->_attributes);
    
    $item_attributes['loading'] = $image_loading_settings['attribute'];
    
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
