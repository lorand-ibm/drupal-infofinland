<?php

namespace Drupal\infofinland_common\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\path_alias\AliasManagerInterface;
use DOMDocument;
use DOMXPath;

/**
 * Change internal links to URL aliases in text fields.
 *
 * @ResourceFieldEnhancer(
 *   id = "text_field_enhancer",
 *   label = @Translation("Text field (Links to URL aliases"),
 *   description = @Translation("Use Text enhancer for text field with links")
 * )
 */
class TextFieldEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $aliasManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $aliasManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('path_alias.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    $doc = new DOMDocument();

    if (isset($data['value'])) {
      $doc->loadHTML($data['value']);
      $xpath = new DOMXPath($doc);
      $nodeList = $xpath->query('//a/@href');
      if ($nodeList) {
        for ($i = 0; $i < $nodeList->length; $i++) {
          if (str_starts_with($nodeList->item($i)->value, '/node/')) {
            $alias = $this->aliasManager->getAliasByPath($nodeList->item($i)->value);
            $data['value'] = str_replace($nodeList->item($i)->value, $alias, $data['value']);
          }
        }
      }
    }

    if (isset($data['processed'])) {
      $doc->loadHTML($data['processed']);
      $processedXpath = new DOMXPath($doc);
      $nodeList2 = $processedXpath->query('//a/@href');
      if ($nodeList2) {
        for ($i = 0; $i < $nodeList2->length; $i++) {
          if (str_starts_with($nodeList->item($i)->value, '/node/')) {
            $alias2 = $this->aliasManager->getAliasByPath($nodeList2->item($i)->value);
            $data['processed'] = str_replace($nodeList2->item($i)->value, $alias2, $data['processed']);
          }
        }
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($value, Context $context) {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'type' => 'object',
    ];
  }

}
