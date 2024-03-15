<?php

namespace Drupal\more_fields\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a more fields form.
 */
class GalleryPaginationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'more_fields_gallery_pagination';
  }


  public static function filterElements(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {


    $longueurChaine = 8;
    $rand = bin2hex(random_bytes($longueurChaine));
    $formId = "more-fields-gallery-pagination-" . $rand;

    $form["#attributes"]["id"] = $formId;
    $form["#attributes"]["class"][] = "form-group";

    $datas = [];

    if (!$form_state->has("datas")) {
      $datas = $form_state->getBuildInfo()['args'][0];
      $form_state->set("datas", $datas);
    } else {
      $datas = $form_state->get('datas');
    }

    $nb_el_per_page = $form_state->getBuildInfo()["args"][1];
    $totalItems = count($datas["#elements"]);
    $nb_pages = (int) $totalItems / $nb_el_per_page;
    $page_options = [];
    $currentPage = $form_state->getValue("pagination") ?? 0;

    for ($i = 0; $i < $nb_pages; $i++) {
      $page_options[] = $i;
    }

    $datas["#elements"] = array_slice($datas["#elements"], $nb_el_per_page * $currentPage, $nb_el_per_page);

    $form["datas"] = $datas;

    $wrapper_id = $datas["#image_attributes"]["field_attribute"]["id"];
    if ($nb_pages > 1) {
      $form["pagination"] = [
        "#type" => "radios",
        "#default_value" => $currentPage,
        "#options" => $page_options,
        "#attributes" => [
          "class" => [
            "d-none"
          ]
        ],
        '#ajax' => [
          'callback' => self::class . '::filterElements',
          'wrapper' => $formId,
          'effect' => 'fade'
        ]
      ];
    }
    $form['actions'] = [
      '#type' => 'actions',
      "#access" => FALSE,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (mb_strlen($form_state->getValue('message')) < 10) {
      $form_state->setErrorByName('message', $this->t('Message should be at least 10 characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('<front>');
  }
}
