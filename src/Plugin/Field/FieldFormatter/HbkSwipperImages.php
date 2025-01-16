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
use Drupal\formatage_models\Plugin\Field\FieldFormatter\SwiperjsImageFormatter;

/**
 * Plugin implementation of the 'text_long, text_with_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_hbk_swiper_img_formatter",
 *   label = @Translation("Swipper only images with Swiper"),
 *   description = "Doit etre ameliorer, afin d'eviter de charger les videos",
 *   field_types = {
 *     "more_fields_hbk_file"
 *   }
 * )
 */
class HbkSwipperImages extends SwiperjsImageFormatter {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [ //
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    //
    return $form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    return $elements;
  }
}
