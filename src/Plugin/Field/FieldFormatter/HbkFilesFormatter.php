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
  protected $providerManager;
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
    $files = $this->getEntitiesToView($items, $langcode);
    dump($files);
    /**
     *
     * @var \Drupal\more_fields\Plugin\Field\FieldType\HbkFiles $item
     */
    $image_settings = $this->getSetting("image_settings");
    $video_settings = $this->getSetting("video_settings");
    $images = [];
    $images_render_array = [];
    $videos = [];
    $videos_render_array = [];
    $otherFiles = [];
    $other_files_array = [];
    $order_array = [];
    foreach ($items as $delta => $item) {
      /**
       *
       * @var File $file
       */
      $file = File::load($item->target_id);
      $file_extension = pathinfo($file->getFileUri(), PATHINFO_EXTENSION);
      if (strpos($image_settings["field_extension"], $file_extension) !== false) {
        //
        $images[$delta] = $item;
        $order_array[] = 1;
      }
      elseif (strpos($video_settings["field_extension"], $file_extension) !== false) {
        $videos[$delta] = $item;
        $order_array[] = 2;
      }
      else {
        $otherFiles[$delta] = $item;
        $order_array[] = 0;
      }
    }
    
    // Handling images
    
    $this->videoFormatter->setSettings($video_settings);
    $this->imageFormatter->setSettings($image_settings);
    // $items contient les images et les videos, donc tu ne peux pas faire le
    // rendu ainsi ?
    $video_render_array = $this->videoFormatter->viewElements($items, $langcode);
    $image_render_array = $this->imageFormatter->viewElements($items, $langcode);
    $other_file_render_array = parent::viewElements($items, $langcode);
    
    $elements = [];
    foreach ($order_array as $key => $value) {
      switch ($value) {
        case 1:
          $elements[$key] = $image_render_array[$key];
          break;
        case 2:
          $elements[$key] = $video_render_array;
          $videos_render_array[$key][0]["#items"] = [];
          $videos_render_array[$key][0]["#items"][] = $video_render_array[0]["#items"][$key] ?? "";
          break;
        case 0:
          $elements[$key] = $other_file_render_array[$key];
          break;
        default:
          break;
      }
    }
    return [
      "#theme" => "more_field_file_image_video",
      "items" => $elements
    ];
  }
  
  protected function viewVideoElement(array $files, &$elements) {
    $video_items = [];
    foreach ($files as $delta => $file) {
      $video_items[] = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
    }
    $elements[] = [
      '#theme' => 'video_player_formatter',
      '#items' => $video_items,
      '#player_attributes' => $this->getSetting('video_settings')
    ];
  }
  
  public function buildRenderArray($formatter, $order_array, $context, $settings_array, $items, $langcode) {
    $result = [];
    $formatter->setSettings($settings_array);
    $render_array = $formatter->viewElements($items, $langcode);
    // dd($render_array);
    foreach ($order_array as $key => $value) {
      if ($value == $context) {
        $result[$key] = $render_array[$key];
      }
    }
    return $result;
  }
  
  // Implement other methods as needed.
}