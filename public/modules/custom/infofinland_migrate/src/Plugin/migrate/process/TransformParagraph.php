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
 *   id = "transform_paragraph"
 * )
 *
 * To do custom value transformations use the following:
 *
 * @code
 * field_links:
 *   plugin: transform_paragraph
 *   source: text
 * @endcode
 *
 */
class TransformParagraph extends ProcessPluginBase {

  /**
   * @param Row $row
   * @return array
   */
  public function getData(Row $row): array
  {
    // In migrate source plugins, the migrate database is easy.
    // Example: $this->select('your_table').
    // Getting to the Drupal 8 db requires a little more code.
    $drupalDb = Database::getConnection('default', 'default');

    $paragraphs = [];
    $results = $drupalDb->select('migrate_map_links_import_link_paragraphs_to_csv', 'yt')
      ->fields('yt', ['destid1', 'destid2'])
      ->condition('yt.sourceid2', $row->getSourceProperty('id'), '=')
      ->execute()
      ->fetchAll();
    if (!empty($results)) {
      foreach ($results as $result) {
        // destid1 in the map table is the nid.
        // destid2 in the map table is the entity revision id.
        $paragraphs[] = [
          'target_id' => $result->destid1,
          'target_revision_id' => $result->destid2,
        ];
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
