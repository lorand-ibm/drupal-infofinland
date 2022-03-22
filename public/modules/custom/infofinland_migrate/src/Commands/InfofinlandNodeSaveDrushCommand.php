<?php

namespace Drupal\infofinland_migrate\Commands;

use Drush\Commands\DrushCommands;
use \Drupal\node\Entity\Node;


/**
 * A drush command file.
 *
 * @package Drupal\infofinland_migrate\Commands
 */
class InfofinlandNodeSaveDrushCommand extends DrushCommands {

  /**
   * Drush command that saves nodes.
   *
   * @param string $amount
   *   Amount of nodes to be saved
   * @param string $startNid
   *   Nid where to start with entity query
   * @command infofinland_node_save_drush_command:savenodes
   * @aliases infofinland_node_save
   * @usage infofinland_node_save_drush_command:savenodes 10 32611
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
}
