<?php

namespace Drupal\infofinland_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Perform custom value transformations.
 *
 * @MigrateProcessPlugin(
 *   id = "add_hero_paragraph"
 * )
 *
 * To do custom value transformations use the following:
 *
 * @code
 * field_content:
 *   plugin: add_hero_paragraph
 *   source: text
 * @endcode
 *
 */
class AddHeroParagraph extends ProcessPluginBase {

  /**
   * @param Row $row
   * @return array
   */
  public function getData(Row $row): array {
    $paragraph = Paragraph::create([
      'type' => 'hero',
    ]);
    $paragraph->save();

    $paragraphs[] = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];

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
