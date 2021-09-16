<?php

namespace Drupal\infofinland_municipality_selection\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Infofinland Municipality Selection block which allows user to select their municipality
 *
 * @Block(
 *   id = "infofinland_municipality_selection_block",
 *   admin_label = @Translation("Infofinland Municipality Selection block"),
 * )
 */

class InfofinlandMunicipalitySelectionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder service.
   *
   * @var FormBuilderInterface
   */
  protected $formBuilder;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Instantiate this block class.
    return new static($configuration, $plugin_id, $plugin_definition,
      // Load the service required to construct this class.
      $container->get('form_builder')
    );
  }

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
