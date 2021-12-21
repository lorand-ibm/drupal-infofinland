<?php

namespace Drupal\infofinland_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Perform custom value transformations.
 *
 * @MigrateProcessPlugin(
 *   id = "get_description_for_content"
 * )
 *
 * To do custom value transformations use the following:
 *
 * @code
 * field_description:
 *   plugin: get_description_for_content
 *   source: text
 * @endcode
 *
 */
class GetDescriptionForContent extends ProcessPluginBase {

  /**
   * @param Row $row
   * @return string
   */
  public function getData(Row $row): string
  {
    $drupalDb = Database::getConnection('default', 'default');
    if (!is_null($row->getSourceProperty('Dokumentin ID')) && $row->getSourceProperty('Dokumentin ID') !== '') {
      $results = $drupalDb->select('paragraph__field_migration_id', 'pfm')
        ->fields('pfm', ['field_migration_id_value', 'entity_id', 'revision_id'])
        ->condition('pfm.field_migration_id_value', $row->getSourceProperty('Dokumentin ID'), '=')
        ->execute()
        ->fetchAll();
      if (!empty($results)) {
        $firstParagraph = Paragraph::load($results[0]->entity_id);
        if (isset($firstParagraph->field_text->value)) {
          return $firstParagraph->field_text->value;
        }
      }
    }

    return "";
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $paragraphs = $this->getData($row);

    return $this->getData($row);
  }


}
