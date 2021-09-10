<?php

namespace Drupal\infofinland_municipality_selection\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Infofinland Municipality Selection block which allows user to select their municipality
 *
 * @Block(
 *   id = "infofinland_municipality_selection_block",
 *   admin_label = @Translation("Infofinland Municipality Selection block"),
 * )
 */

class InfofinlandMunicipalitySelectionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\infofinland_municipality_selection\Form\InfofinlandMunicipalitySelectionBlockForm');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return parent::blockForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('infofinland_municipality_selection_block_settings', $form_state->getValue('infofinland_municipality_selection_block_settings'));
  }

}
