<?php

namespace Drupal\more_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * @FieldFormatter(
 *   id = "more_fields_conditionalfield_formatter",
 *   label = @Translation("ConditionalField"),
 *   field_types = {"more_fields_conditionalfield"}
 * )
 */
class ConditionalfieldFormatter extends FormatterBase {

    public static function defaultSettings() {
        return [
            'display_condition' => TRUE,
            // Paramètres généraux
            'trim_length' => 0,
            'trim_on_break' => FALSE,
            // Paramètres image
            'image_style' => '',
            'image_link' => '',
            'image_class' => '',
            'lazy_loading' => TRUE,
            'empty_image_text' => '',
            // Paramètres lien
            'link_target' => '_self',
            'link_class' => '',
            'link_rel' => '',
            'empty_link_text' => '',
            // Paramètres boolean
            'boolean_format' => 'text',
            'true_label' => 'Yes',
            'false_label' => 'No',
            // Paramètres date
            'date_format' => 'medium',
            'custom_date_format' => 'Y-m-d H:i:s',
            'timezone' => '',
            'date_prefix' => '',
            'date_suffix' => '',
            'empty_date_text' => '',
            // Paramètres entity reference
            'link_to_entity' => TRUE,
            'view_mode' => 'label',
            'empty_reference_text' => '',
            // Paramètres list
            'list_display' => 'label',
            'list_class' => '',
            'empty_list_text' => '',
            // Paramètres email
            'link_email' => TRUE,
            'empty_email_text' => '',
            // Paramètres number
            'number_format' => 'default',
            'currency_symbol' => '$',
            'number_prefix' => '',
            'number_suffix' => '',
            'empty_number_text' => '',
            // Paramètres texte
            'text_class' => '',
            'empty_text' => '',
            // Paramètres string
            'preserve_lines' => FALSE,
            'string_class' => '',
            'empty_string_text' => '',
        ] + parent::defaultSettings();
    }

    public function settingsForm(array $form, FormStateInterface $form_state) {
        $form = parent::settingsForm($form, $form_state);
        $settings = $this->getSettings();
        $field_settings = $this->getFieldSettings();
        $field_type = $field_settings['content_field_type'] ?? 'string';

        $form['display_condition'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Respect display condition'),
            '#default_value' => $settings['display_condition'],
            '#description' => $this->t('If unchecked, content will always display regardless of condition.'),
        ];

        // Charger les paramètres spécifiques au type de champ
        $form += $this->getTypeSpecificSettings($field_type, $settings);

        return $form;
    }

    public function settingsSummary() {
        $summary = [];
        $settings = $this->getSettings();
        $field_settings = $this->getFieldSettings();
        $field_type = $field_settings['content_field_type'] ?? 'string';

        if (!$settings['display_condition']) {
            $summary[] = $this->t('Condition ignored - always display');
        } else {
            $summary[] = $this->t('Respect display condition');
        }

        // Ajouter le résumé des paramètres spécifiques
        $summary = array_merge($summary, $this->getTypeSpecificSummary($field_type, $settings));

        return $summary;
    }

    private function getTypeSpecificSettings($field_type, $current_settings) {
        $element = [];

        switch ($field_type) {
            case 'image':
                $element['image_style'] = [
                    '#type' => 'select',
                    '#title' => $this->t('Image style'),
                    '#options' => image_style_options(FALSE),
                    '#default_value' => $current_settings['image_style'] ?? '',
                    '#empty_option' => $this->t('None (original image)'),
                ];
                $element['image_link'] = [
                    '#type' => 'select',
                    '#title' => $this->t('Link image to'),
                    '#options' => [
                        '' => $this->t('Nothing'),
                        'content' => $this->t('Content'),
                        'file' => $this->t('File'),
                    ],
                    '#default_value' => $current_settings['image_link'] ?? '',
                ];
                break;

            case 'text_with_summary':
            case 'text_long':
            case 'text':
                $element['trim_length'] = [
                    '#type' => 'number',
                    '#title' => $this->t('Trimmed limit'),
                    '#default_value' => $current_settings['trim_length'] ?? 0,
                    '#min' => 0,
                    '#description' => $this->t('If set to 0, no trimming will be done.'),
                ];
                $element['trim_on_break'] = [
                    '#type' => 'checkbox',
                    '#title' => $this->t('Trim on break'),
                    '#default_value' => $current_settings['trim_on_break'] ?? FALSE,
                ];
                break;

            case 'link':
                $element['link_target'] = [
                    '#type' => 'select',
                    '#title' => $this->t('Link target'),
                    '#options' => [
                        '_self' => $this->t('Same window'),
                        '_blank' => $this->t('New window'),
                    ],
                    '#default_value' => $current_settings['link_target'] ?? '_self',
                ];
                $element['trim_length'] = [
                    '#type' => 'number',
                    '#title' => $this->t('Trim link text length'),
                    '#default_value' => $current_settings['trim_length'] ?? 0,
                    '#min' => 0,
                ];
                break;

            case 'entity_reference':
                $element['link_to_entity'] = [
                    '#type' => 'checkbox',
                    '#title' => $this->t('Link to the referenced entity'),
                    '#default_value' => $current_settings['link_to_entity'] ?? TRUE,
                ];
                $element['view_mode'] = [
                    '#type' => 'select',
                    '#title' => $this->t('View mode'),
                    '#options' => [
                        'default' => $this->t('Default'),
                        'teaser' => $this->t('Teaser'),
                        'full' => $this->t('Full'),
                    ],
                    '#default_value' => $current_settings['view_mode'] ?? 'default',
                ];
                break;

            case 'list_string':
            case 'list_integer':
            case 'list_float':
                $element['list_display'] = [
                    '#type' => 'select',
                    '#title' => $this->t('Display as'),
                    '#options' => [
                        'label' => $this->t('Label only'),
                        'key' => $this->t('Key only'),
                        'both' => $this->t('Key and label'),
                    ],
                    '#default_value' => $current_settings['list_display'] ?? 'label',
                ];
                break;

            case 'datetime':
                $element['date_format'] = [
                    '#type' => 'select',
                    '#title' => $this->t('Date format'),
                    '#options' => [
                        'short' => $this->t('Short'),
                        'medium' => $this->t('Medium'),
                        'long' => $this->t('Long'),
                        'custom' => $this->t('Custom'),
                    ],
                    '#default_value' => $current_settings['date_format'] ?? 'medium',
                ];
                $element['custom_date_format'] = [
                    '#type' => 'textfield',
                    '#title' => $this->t('Custom date format'),
                    '#default_value' => $current_settings['custom_date_format'] ?? 'Y-m-d H:i:s',
                    '#description' => $this->t('See <a href="http://php.net/manual/function.date.php" target="_blank">PHP date format string</a>.'),
                    '#states' => [
                        'visible' => [
                            ':input[name="fields[field_example][settings_edit_form][settings][date_format]"]' => ['value' => 'custom'],
                        ],
                    ],
                ];
                break;
        }

        return $element;
    }

    private function getTypeSpecificSummary($field_type, $settings) {
        $summary = [];

        switch ($field_type) {
            case 'image':
                if (!empty($settings['image_style'])) {
                    $image_styles = image_style_options(FALSE);
                    $summary[] = $this->t('Image style: @style', ['@style' => $image_styles[$settings['image_style']]]);
                }
                if (!empty($settings['image_link'])) {
                    $summary[] = $this->t('Linked to: @link', ['@link' => $settings['image_link']]);
                }
                break;

            case 'text_with_summary':
            case 'text_long':
            case 'text':
                if (!empty($settings['trim_length'])) {
                    $summary[] = $this->t('Trimmed to @limit characters', ['@limit' => $settings['trim_length']]);
                }
                break;

            case 'link':
                if (!empty($settings['link_target'])) {
                    $summary[] = $this->t('Target: @target', ['@target' => $settings['link_target']]);
                }
                break;

            case 'entity_reference':
                if (!empty($settings['link_to_entity'])) {
                    $summary[] = $this->t('Linked to entity');
                }
                $summary[] = $this->t('View mode: @mode', ['@mode' => $settings['view_mode'] ?? 'default']);
                break;

            case 'datetime':
                $summary[] = $this->t('Date format: @format', ['@format' => $settings['date_format'] ?? 'medium']);
                break;
        }

        return $summary;
    }


    public function viewElements(FieldItemListInterface $items, $langcode) {
        $element = [];
        $settings = $this->getSettings();
        $field_settings = $this->getFieldSettings();

        foreach ($items as $delta => $item) {
            // Vérifier la condition seulement si activée
            if (!$settings['display_condition'] || $this->checkCondition($item->condition_value, $field_settings)) {
                $element[$delta] = $this->formatContent($item, $field_settings, $settings);
            }
        }

        return $element;
    }

    private function checkCondition($condition, $settings) {
        if (empty($condition)) {
            return true;
        }
        // Check if condition value exists in current URL
        $current_url = \Drupal::request()->getRequestUri();
        return strpos($current_url, $condition) !== FALSE;
    }

    private function formatContent($item, $field_settings, $formatter_settings) {
        $field_type = $field_settings['content_field_type'] ?? 'string';

        switch ($field_type) {
            case 'boolean':
                return $this->renderBoolean($item->content_value);

            case 'datetime':
                return $this->renderDateTime($item->content_value, $formatter_settings);

            case 'entity_reference':
                return $this->renderEntityReference($item->content_value, $field_settings, $formatter_settings);

            case 'text_with_summary':
            case 'text_long':
                return $this->renderText($item->content_value, $item->content_format ?? 'basic_html', $formatter_settings);

            case 'text':
                return $this->renderText($item->content_value, 'basic_html', $formatter_settings);

            case 'link':
                return $this->renderLink($item, $formatter_settings);

            case 'image':
                return $this->renderImage($item, $formatter_settings);

            case 'integer':
            case 'decimal':
            case 'float':
                return $this->renderNumber($item->content_value);

            case 'list_string':
            case 'list_integer':
            case 'list_float':
                return $this->renderList($item->content_value, $field_settings, $formatter_settings);

            case 'email':
                return $this->renderEmail($item->content_value, $formatter_settings);

            default: // string, string_long
                return $this->renderString($item->content_value, $formatter_settings);
        }
    }


    private function getListValue($value, $settings) {
        // Extraire la valeur d'affichage depuis allowed_values
        $allowed_values = $settings['allowed_values'] ?? '';
        $lines = explode("\n", $allowed_values);

        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '|') !== FALSE) {
                list($key, $label) = explode('|', $line, 2);
                if (trim($key) == $value) {
                    return trim($label);
                }
            }
        }

        return $value; // Retourner la valeur brute si pas trouvée
    }







    private function renderBoolean($value) {
        return ['#markup' => $value ? $this->t('Yes') : $this->t('No')];
    }

    private function renderDateTime($timestamp, $settings) {
        if (empty($timestamp)) {
            return ['#markup' => $settings['empty_date_text'] ?? ''];
        }

        $format_type = $settings['date_format'] ?? 'medium';
        $timezone = $settings['timezone'] ?? NULL;

        if ($format_type === 'custom' && !empty($settings['custom_date_format'])) {
            $formatted_date = \Drupal::service('date.formatter')->format($timestamp, 'custom', $settings['custom_date_format'], $timezone);
        } else {
            $formatted_date = \Drupal::service('date.formatter')->format($timestamp, $format_type, '', $timezone);
        }

        // Ajouter un préfixe/suffixe si configuré
        $prefix = $settings['date_prefix'] ?? '';
        $suffix = $settings['date_suffix'] ?? '';

        return ['#markup' => $prefix . $formatted_date . $suffix];
    }

    private function renderEntityReference($entity_id, $field_settings, $settings) {
        $target_type = $field_settings['target_type'] ?? 'node';

        if (empty($entity_id) || !$entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($entity_id)) {
            return ['#markup' => $settings['empty_reference_text'] ?? ''];
        }

        $view_mode = $settings['view_mode'] ?? 'default';
        // Si un view_mode spécifique est demandé, utiliser le rendu complet
        if ($view_mode !== 'label') {
            $view_builder = \Drupal::entityTypeManager()->getViewBuilder($target_type);
            $build = $view_builder->view($entity, $view_mode);

            if (!empty($settings['link_to_entity'])) {
                return [
                    '#type' => 'link',
                    '#url' => $entity->toUrl(),
                    '#title' => $build,
                ];
            }

            return $build;
        }

        // Sinon, juste le label
        $label = $entity->label();

        // Appliquer la troncature si configurée
        $trim_length = $settings['trim_length'] ?? 0;
        if ($trim_length > 0 && strlen($label) > $trim_length) {
            $label = substr($label, 0, $trim_length) . '...';
        }

        if (!empty($settings['link_to_entity'])) {
            return [
                '#type' => 'link',
                '#url' => $entity->toUrl(),
                '#title' => $label,
            ];
        }

        return ['#markup' => $label];
    }

    private function renderText($text, $format, $settings) {
        if (empty($text)) {
            return ['#markup' => $settings['empty_text'] ?? ''];
        }

        $trim_length = $settings['trim_length'] ?? 0;
        $trim_on_break = $settings['trim_on_break'] ?? FALSE;

        if ($trim_length > 0) {
            // Pour le HTML, on doit stripper les tags pour la troncature
            $plain_text = strip_tags($text);
            if (strlen($plain_text) > $trim_length) {
                if ($trim_on_break) {
                    $plain_text = preg_replace('/\s+?(\S+)?$/', '', substr($plain_text, 0, $trim_length + 1));
                } else {
                    $plain_text = substr($plain_text, 0, $trim_length) . '...';
                }
                $text = $plain_text;
                // Si on a tronqué, on utilise le format basic_html pour sécurité
                $format = 'basic_html';
            }
        }

        $build = [
            '#type' => 'processed_text',
            '#text' => $text,
            '#format' => $format,
        ];

        // Ajouter des classes CSS si configuré
        if (!empty($settings['text_class'])) {
            $build['#attributes'] = ['class' => [$settings['text_class']]];
        }

        return $build;
    }

    private function renderLink($item, $settings) {
        if (empty($item->content_uri)) {
            return ['#markup' => $settings['empty_link_text'] ?? ''];
        }

        $title = $item->content_title ?: $item->content_uri;
        $trim_length = $settings['trim_length'] ?? 0;

        if ($trim_length > 0 && strlen($title) > $trim_length) {
            $title = substr($title, 0, $trim_length) . '...';
        }

        try {
            $url = Url::fromUri($item->content_uri);
        } catch (\Exception $e) {
            return ['#markup' => $title];
        }

        $link = [
            '#type' => 'link',
            '#url' => $url,
            '#title' => $title,
        ];

        // Attributs supplémentaires
        $attributes = [];

        if (!empty($settings['link_target'])) {
            $attributes['target'] = $settings['link_target'];
        }

        if (!empty($settings['link_class'])) {
            $attributes['class'] = explode(' ', $settings['link_class']);
        }

        if (!empty($settings['link_rel'])) {
            $attributes['rel'] = $settings['link_rel'];
        }

        if ($attributes) {
            $link['#attributes'] = $attributes;
        }

        return $link;
    }

    private function renderImage($item, $settings) {
        if (empty($item->content_target_id)) {
            return ['#markup' => $settings['empty_image_text'] ?? ''];
        }

        $file = \Drupal\file\Entity\File::load($item->content_target_id);
        if (!$file) {
            return ['#markup' => $settings['empty_image_text'] ?? ''];
        }

        $image = [
            '#theme' => 'image',
            '#uri' => $file->getFileUri(),
            '#alt' => $item->content_alt ?? '',
            '#title' => $item->content_title ?? '',
        ];

        // Style d'image
        if (!empty($settings['image_style'])) {
            $image['#style_name'] = $settings['image_style'];
        }

        // Classes CSS
        if (!empty($settings['image_class'])) {
            $image['#attributes'] = ['class' => [$settings['image_class']]];
        }

        // Lien
        if (!empty($settings['image_link'])) {
            if ($settings['image_link'] == 'file') {
                $image['#url'] = $file->createFileUrl();
            }
            // Retirer le lien 'content' qui nécessite $this->view
        }

        // Lazy loading
        if (isset($settings['lazy_loading']) && !$settings['lazy_loading']) {
            $image['#attributes']['loading'] = 'eager';
        }

        return $image;
    }

    private function renderNumber($value) {
        if (!isset($value)) {
            return ['#markup' => ''];
        }
        return ['#markup' => number_format($value)];
    }

    private function renderList($value, $field_settings, $settings) {
        if (!isset($value)) {
            return ['#markup' => $settings['empty_list_text'] ?? ''];
        }

        $display = $settings['list_display'] ?? 'label';
        $allowed_values = $field_settings['allowed_values'] ?? '';

        $label = $this->getListLabel($value, $allowed_values);

        $output = '';
        switch ($display) {
            case 'key':
                $output = $value;
                break;
            case 'both':
                $output = '<span class="list-key">' . $value . '</span> - <span class="list-label">' . $label . '</span>';
                break;
            case 'label':
            default:
                $output = $label;
        }

        // Ajouter une classe CSS
        $class = $settings['list_class'] ?? '';
        if ($class) {
            $output = '<span class="' . $class . '">' . $output . '</span>';
        }

        return ['#markup' => $output];
    }

    private function renderEmail($email, $settings) {
        if (empty($email)) {
            return ['#markup' => $settings['empty_email_text'] ?? ''];
        }

        $display_text = $email;
        $trim_length = $settings['trim_length'] ?? 0;

        if ($trim_length > 0 && strlen($display_text) > $trim_length) {
            $display_text = substr($display_text, 0, $trim_length) . '...';
        }

        if (!empty($settings['link_email'])) {
            return [
                '#type' => 'link',
                '#url' => Url::fromUri('mailto:' . $email),
                '#title' => $display_text,
                '#attributes' => [
                    'class' => ['email-link'],
                ],
            ];
        }

        return ['#markup' => $display_text];
    }

    private function renderString($text, $settings) {
        if (empty($text)) {
            return ['#markup' => $settings['empty_string_text'] ?? ''];
        }

        $trim_length = $settings['trim_length'] ?? 0;
        $preserve_lines = $settings['preserve_lines'] ?? FALSE;

        if ($trim_length > 0 && strlen($text) > $trim_length) {
            $text = substr($text, 0, $trim_length) . '...';
        }

        // Préserver les sauts de ligne
        if ($preserve_lines) {
            $text = nl2br($text);
        }

        $output = $text;

        // Ajouter une classe CSS
        $class = $settings['string_class'] ?? '';
        if ($class) {
            $output = '<span class="' . $class . '">' . $output . '</span>';
        }

        return ['#markup' => $output];
    }
    private function getListLabel($value, $allowed_values) {
        if (empty($allowed_values)) {
            return $value;
        }

        $lines = explode("\n", $allowed_values);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Support multiple formats: key|label or key : label
            if (strpos($line, '|') !== FALSE) {
                list($key, $label) = explode('|', $line, 2);
            } elseif (strpos($line, ':') !== FALSE) {
                list($key, $label) = explode(':', $line, 2);
            } else {
                // Si pas de séparateur, utiliser la ligne comme clé et label
                $key = $line;
                $label = $line;
            }

            $key = trim($key);
            $label = trim($label);

            if ($key === $value) {
                return $label;
            }
        }

        // Si pas trouvé, retourner la valeur originale
        return $value;
    }
}
