<?php

namespace Drupal\infofinland_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Perform custom value transformations.
 *
 * @MigrateProcessPlugin(
 *   id = "add_paragraphs"
 * )
 *
 * To do custom value transformations use the following:
 *
 * @code
 * field_links:
 *   plugin: add_paragraphs
 *   source: text
 * @endcode
 *
 */
class AddParagraphToContent extends ProcessPluginBase {

  /**
   * @param Row $row
   * @return array
   */
  public function getData(Row $row): array
  {
    // In migrate source plugins, the migrate database is easy.
    // Example: $this->select('your_table').
    // Getting to the Drupal 8 db requires a little more code.
    $paragraphs = [];
    $drupalDb = Database::getConnection('default', 'default');
    if (!is_null($row->getSourceProperty('Dokumentin ID')) && $row->getSourceProperty('Dokumentin ID') !== '') {
      $results = $drupalDb->select('paragraph__field_migration_id', 'pfm')
        ->fields('pfm', ['field_migration_id_value', 'entity_id', 'revision_id'])
        ->condition('pfm.field_migration_id_value', $row->getSourceProperty('Dokumentin ID'), '=')
        ->execute()
        ->fetchAll();
      if (!empty($results)) {
        foreach ($results as $result) {
          $paragraphs[] = [
            'target_id' => $result->entity_id,
            'target_revision_id' => $result->revision_id,
          ];
        }
      }
    }



    return $paragraphs;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $return = [];
    $paragraphs = $this->getData($row);
    foreach($paragraphs as $target) {
      $return[] = ['target_id' => $target];
    }

    return $paragraphs;
  }


}
