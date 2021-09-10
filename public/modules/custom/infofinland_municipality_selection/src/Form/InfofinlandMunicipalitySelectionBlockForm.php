<?php

namespace Drupal\infofinland_municipality_selection\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Infofinland Municipality Selection block form
 */
class InfofinlandMunicipalitySelectionBlockForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'infofinland_municipality_selection_block_form';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $items = [];

    // Get the term storage.
    $entity_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    // Query the terms sorted by weight.
    $query_result = $entity_storage->getQuery()
      ->condition('vid', 'municipalitys')
      ->sort('weight', 'ASC')
      ->execute();
    // Load the terms.
    $terms = $entity_storage->loadMultiple($query_result);

    foreach ($terms as $term) {
      $items[$term->getName()] = $term->getName();
    }

    $form['municipality'] = array(
      '#type' => 'select',
      '#title' => $this->t('Municipality'),
      '#options' => $items,
      '#description' => $this->t('Select your municipality'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Select'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    setcookie('infofinland_municipality_selection.selectedMunicipality', $form_state->getValue('municipality'),time() + (86400 * 365),null);

  }
}
