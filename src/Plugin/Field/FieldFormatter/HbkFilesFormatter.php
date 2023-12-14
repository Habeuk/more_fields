<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\video\ProviderManagerInterface;
use Drupal\video\Plugin\Field\FieldFormatter\VideoEmbedPlayerFormatter;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

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
     *   Third party settings.
     * @param \Drupal\video\ProviderManagerInterface $provider_manager
     *   The video embed provider manager.
     */
    public function __construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, ProviderManagerInterface $provider_manager, AccountInterface $current_user, EntityStorageInterface $image_style_storage, FileUrlGeneratorInterface $file_url_generator = NULL) {
        parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
        $this->providerManager = $provider_manager;
        $this->videoFormatter = new VideoEmbedPlayerFormatter($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $this->providerManager);
        $this->imageFormatter = new ImageFormatter($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage, $file_url_generator);
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
            $container->get('video.provider_manager'),
            $container->get('current_user'),
            $container->get('entity_type.manager')->getStorage('image_style'),
            $container->get('file_url_generator')
        );
    }
    /**
     *
     * {@inheritdoc}
     */
    public static function defaultSettings() {
        $default = [
            "video_settings" => VideoEmbedPlayerFormatter::defaultSettings(),
            "image_settings" => ImageFormatter::defaultSettings(),
            "layoutgenentitystyles_view" => null,
            "my_element" => "myddd element",
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
        $default_configs = $this->defaultSettings();
        $configs = $this->getSettings();
        // dump($configs);
        $video_settings = $configs['video_settings'] ??  $default_configs["video_default"];
        $image_settings = $configs['image_settings'] ??  $default_configs["image_default"];
        // dump([$default_configs, $image_settings]);
        $temp_form = [];
        $image_settings_fields = ['image_style', 'image_link', 'field_extension'];
        $video_settings_fields = ['width', 'height', 'autoplay', 'related_videos', 'field_extension'];


        $temp_form['video_settings'] = [
            '#type' => 'details',
            '#title' => $this->t('Video Settings'),
            '#tree' => TRUE,
            '#open' => FALSE,
        ];
        $temp_form['image_settings'] = [
            '#type' => 'details',
            '#title' => $this->t('Image Settings'),
            '#tree' => TRUE,
            '#open' => FALSE,
        ];

        $video_settings_form  = $this->videoFormatter->settingsForm($form, $form_state);
        $image_settings_form = $this->imageFormatter->settingsForm($form, $form_state);


        $field_extension = [
            "#title" => $this->t("field type extension"),
            "#type" => "textfield",
            "#default_value" => "",
        ];
        $temp_form["video_settings"]["field_extension"] = $field_extension;
        $temp_form["image_settings"]["field_extension"] = $field_extension;

        $temp_form['image_settings'] = array_merge($temp_form['image_settings'], $image_settings_form);
        $temp_form['video_settings'] = array_merge($temp_form['video_settings'], $video_settings_form);

        $settings_form = [
            // utilile pour mettre à jour le style
            'layoutgenentitystyles_view' => [
                '#type' => 'hidden',
                "#value" => null,
            ]
        ];
        // dump($video_settings);
        //update default value for video
        foreach ($video_settings_fields as $value) {
            $temp_form["video_settings"][$value]["#default_value"] = $video_settings[$value];
        }

        //update default value for image
        foreach ($image_settings_fields as $value) {
            $temp_form["image_settings"][$value]["#default_value"] = $image_settings[$value];
        }




        // dump($temp_form);
        $settings_form = array_merge($settings_form, $temp_form);
        // $settings_form["#submit"] = [static::class, 'my_form_submit'];
        // dump($settings_form);
        // dump($configs);
        return $settings_form + parent::settingsForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode) {
        $elements = [];
        return $elements;
    }
    public function my_form_submit(&$form, &$form_state) {
        dump($form_state);
    }
}
