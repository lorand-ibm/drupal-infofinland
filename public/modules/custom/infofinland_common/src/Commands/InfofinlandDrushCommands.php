<?php

namespace Drupal\infofinland_common\Commands;

use Drush\Commands\DrushCommands;
use Drupal\node\Entity\Node;


/**
 * A drush command file.
 *
 * @package Drupal\infofinland_common\Commands
 */
class InfofinlandDrushCommands extends DrushCommands {

  /**
   * Drush command that saves nodes.
   *
   * @param string $amount
   *   Amount of nodes to be saved
   * @param string $startNid
   *   Nid where to start with entity query
   * @command infofinland:node-save
   * @usage infofinland:node-save 10 32611
   */
  public function savenodes($amount = 10, $startNid = 32610) {
    // Get an array of all 'page' node IDs.
    $nids = \Drupal::entityQuery('node')
    ->condition('type', 'page')
    ->condition('langcode', 'fi')
    ->condition('nid', $startNid, '>')
    ->sort('nid', 'ASC')
    ->range(0, $amount)
    ->execute();

    // Load all the nodes.
    $nodes = Node::loadMultiple($nids);
    foreach ($nodes as $node) {
      $node->save();
      $this->output()->writeln($node->id());
    }
  }

  /**
   * Drush command that saves nodes.
   *
   * @param string $nid
   *   node id
   * @command infofinland:unpublish-node
   * @usage infofinland:unpublish-node 32611
   */
  public function unpublishNode($nid) {
    if ($nid) {
      $node = Node::load($nid);
      $translation_languages = $node->getTranslationLanguages();

      foreach ($translation_languages as $language) {
        $lang = $language->getId();
        $node_translation = $node->getTranslation($lang);
        $node_translation->set('moderation_state', 'unpublished');
        $node_translation->save();
        $this->output()->writeln($node->id() . ' Lang:' . $lang);
      }
    }
  }
}
