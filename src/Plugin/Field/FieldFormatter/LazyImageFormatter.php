<?php
declare(strict_types = 1);

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'lazy_image' formatter.
 *
 * @FieldFormatter(
 *   id = "more_fields_lazy_image",
 *   label = @Translation("Image with Lazy Loading (data-src/loading)"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class LazyImageFormatter extends ImageFormatter {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'lazy_method' => 'data-src',
      'placeholder_type' => 'inline_svg',
      'placeholder_custom' => '',
      'threshold' => 200,
      'decoding' => 'auto',
      'fetchpriority' => 'auto'
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    // Lazy loading method.
    $elements['lazy_method'] = [
      '#title' => $this->t('Lazy loading method'),
      '#type' => 'select',
      '#options' => [
        'data-src' => $this->t('data-src + JavaScript (IntersectionObserver)'),
        'loading' => $this->t('loading="lazy" (native)'),
        'eager' => $this->t('loading="eager" (native)'),
        'both' => $this->t('Both (data-src + loading="lazy")')
      ],
      '#default_value' => $this->getSetting('lazy_method'),
      '#description' => $this->t('The data-src method requires the JS library provided by the module.')
    ];
    
    // Placeholder type.
    $elements['placeholder_type'] = [
      '#title' => $this->t('Placeholder type'),
      '#type' => 'select',
      '#options' => [
        'inline_svg' => $this->t('Inline SVG (preserves dimensions)'),
        'custom' => $this->t('Custom image'),
        'none' => $this->t('None (empty src)')
      ],
      '#default_value' => $this->getSetting('placeholder_type'),
      '#description' => $this->t('The placeholder prevents layout shifts (CLS).')
    ];
    
    // Custom placeholder image.
    $elements['placeholder_custom'] = [
      '#title' => $this->t('Placeholder image path'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('placeholder_custom'),
      '#states' => [
        'visible' => [
          ':input[name="fields[field_image][settings_edit_form][settings][placeholder_type]"]' => [
            'value' => 'custom'
          ]
        ]
      ],
      '#description' => $this->t('Relative or absolute path to a lightweight image (e.g., /themes/custom/my_theme/images/placeholder.svg).')
    ];
    
    // Threshold.
    $elements['threshold'] = [
      '#title' => $this->t('Loading threshold (pixels)'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 1000,
      '#step' => 10,
      '#default_value' => $this->getSetting('threshold'),
      '#description' => $this->t("Load the image when it is X pixels from the viewport (only for data-src method)."),
      '#states' => [
        'visible' => [
          ':input[name="fields[field_image][settings_edit_form][settings][lazy_method]"]' => [
            [
              'value' => 'data-src'
            ],
            [
              'value' => 'both'
            ]
          ]
        ]
      ]
    ];
    // Decoding attribute.
    $elements['decoding'] = [
      '#title' => $this->t('Decoding hint'),
      '#type' => 'select',
      '#options' => [
        'auto' => $this->t('Auto (browser default)'),
        'sync' => $this->t('Sync (decode synchronously)'),
        'async' => $this->t('Async (decode asynchronously)')
      ],
      '#default_value' => $this->getSetting('decoding'),
      '#description' => $this->t('Hint to the browser on how to decode the image. Async is recommended for most images to improve performance.')
    ];
    
    // Fetchpriority attribute.
    $elements['fetchpriority'] = [
      '#title' => $this->t('Fetch priority'),
      '#type' => 'select',
      '#options' => [
        'auto' => $this->t('Auto (browser default)'),
        'high' => $this->t('High (priority)'),
        'low' => $this->t('Low (non-priority)')
      ],
      '#default_value' => $this->getSetting('fetchpriority'),
      '#description' => $this->t('Hint to the browser about the relative priority of this image. Use "high" for the largest contentful paint (LCP) element.')
    ];
    
    return $elements;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();
    
    // Style.
    if (!empty($settings['image_style'])) {
      $summary[] = $this->t('Style: @style', [
        '@style' => $settings['image_style']
      ]);
    }
    else {
      $summary[] = $this->t('Style: original');
    }
    
    // Method.
    $methods = [
      'data-src' => $this->t('data-src JS'),
      'loading' => $this->t('loading="lazy" native'),
      'both' => $this->t('data-src + loading="lazy"')
    ];
    $summary[] = $this->t('Lazy loading: @method', [
      '@method' => $methods[$settings['lazy_method']]
    ]);
    
    // Placeholder.
    if ($settings['placeholder_type'] !== 'none') {
      $summary[] = $this->t('Placeholder: @type', [
        '@type' => $settings['placeholder_type'] === 'inline_svg' ? 'Inline SVG' : 'Custom'
      ]);
    }
    // Decoding.
    $decoding_options = [
      'auto' => $this->t('Auto'),
      'sync' => $this->t('Sync'),
      'async' => $this->t('Async')
    ];
    $summary[] = $this->t('Decoding: @decoding', [
      '@decoding' => $decoding_options[$settings['decoding']]
    ]);
    
    // Fetchpriority.
    $fetchpriority_options = [
      'auto' => $this->t('Auto'),
      'high' => $this->t('High'),
      'low' => $this->t('Low')
    ];
    $summary[] = $this->t('Fetch priority: @fetchpriority', [
      '@fetchpriority' => $fetchpriority_options[$settings['fetchpriority']]
    ]);
    return $summary;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();
    $cacheability = new CacheableMetadata();
    
    /** @var \Drupal\file\FileInterface|null $entity */
    foreach ($items as $delta => $item) {
      if (!$item->entity) {
        continue;
      }
      
      $file = $item->entity;
      $image_uri = $file->getFileUri();
      $cacheability->addCacheableDependency($file);
      
      // Generate URL with or without style.
      $image_url = $this->buildImageUrl($image_uri, $settings['image_style']);
      
      // Build base attributes.
      $attributes = [
        'alt' => $item->alt ?: '',
        'title' => $item->title ?: '',
        'width' => $item->width,
        'height' => $item->height,
        'class' => [
          'img-fluid',
          'lazy-image'
        ]
      ];
      
      // Add decoding attribute if not auto.
      if ($settings['decoding'] !== 'auto') {
        $attributes['decoding'] = $settings['decoding'];
      }
      
      // Add fetchpriority attribute if not auto.
      if ($settings['fetchpriority'] !== 'auto') {
        $attributes['fetchpriority'] = $settings['fetchpriority'];
      }
      
      // Handle placeholder.
      \Stephane888\Debug\debugLog::kintDebugDrupal([
        $attributes,
        $settings
      ], 'buildPlaceholderUrl', true);
      $placeholder_url = $this->buildPlaceholderUrl((int) $attributes['width'], (int) $attributes['height'], $settings);
      
      // Apply lazy loading method.
      switch ($settings['lazy_method']) {
        case 'data-src':
          $attributes['data-src'] = $image_url;
          $attributes['src'] = $placeholder_url;
          $attributes['data-threshold'] = $settings['threshold'];
          break;
        
        case 'loading':
          $attributes['src'] = $image_url;
          $attributes['loading'] = 'lazy';
          break;
        
        case 'both':
          $attributes['data-src'] = $image_url;
          $attributes['src'] = $placeholder_url;
          $attributes['loading'] = 'lazy';
          $attributes['data-threshold'] = $settings['threshold'];
          break;
      }
      
      $elements[$delta] = [
        '#theme' => 'image',
        '#attributes' => $attributes
      ];
    }
    
    // Attach JS library if needed.
    if (in_array($settings['lazy_method'], [
      'data-src',
      'both'
    ])) {
      $elements['#attached']['library'][] = 'more_fields/lazy-load';
    }
    
    $cacheability->applyTo($elements);
    return $elements;
  }
  
  /**
   * Builds image URL (styled or original).
   */
  private function buildImageUrl(string $uri, ?string $image_style): string {
    if ($image_style && ImageStyle::load($image_style)) {
      return ImageStyle::load($image_style)->buildUrl($uri);
    }
    return $this->fileUrlGenerator->generateAbsoluteString($uri);
  }
  
  /**
   * Builds placeholder URL.
   */
  private function buildPlaceholderUrl(?int $width, ?int $height, array $settings): string {
    if ($settings['placeholder_type'] === 'none') {
      return '';
    }
    
    if ($settings['placeholder_type'] === 'custom' && !empty($settings['placeholder_custom'])) {
      // Custom path: transform to absolute URL if needed.
      return file_url_transform_relative(file_create_url($settings['placeholder_custom']));
    }
    
    // Default inline SVG placeholder.
    $width = $width ?: 100;
    $height = $height ?: 100;
    $svg = sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d"><rect width="%d" height="%d" fill="#f0f0f0"/></svg>', $width, $height, $width, $height, $width, $height);
    return 'data:image/svg+xml,' . rawurlencode($svg);
  }
  
}