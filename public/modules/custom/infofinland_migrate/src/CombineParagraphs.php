<?php


namespace Drupal\infofinland_migrate;

use Drupal\Core\Database\Database;
use Drupal\infofinland_common\Plugin\Controller\ContentController;
use Drupal\node\Entity\Node;

/**
 *
 * This file is used to combine text paragraphs after migration
 * It should only be used by running it with drush
 *
 * Class FixLocalLinks
 * @package Drupal\infofinland_migrate\Plugin\migrate
 */
class CombineParagraphs {

  private function getLargeContent() {
    $id = $_SERVER['argv'][3];
    $drupalDb = Database::getConnection('default', 'default');
    $content = $drupalDb->select('node__field_content', 'nfc')
      ->fields('nfc', ['entity_id'])
      ->groupBy('nfc.entity_id')
      ->groupBy('langcode')
      ->having('COUNT(nfc.entity_id) > :entity_id', [':entity_id' => 80])
      ->execute()
      ->fetchAll();
    return $content;
  }

  public function combineTextParagraphs() {
    $id = $_SERVER['argv'][3];
    if ($id === null) {
      $content = $this->getLargeContent();
      if (empty($content)) {
        return;
      }
      foreach ($content as $row) {
        $node = Node::load($row->entity_id);
        $languages = $node->getTranslationLanguages();
        $content = new ContentController;
        foreach ($languages as $langcode => $language) {
          $content->combineContentParagraphs($node->getTranslation($langcode));
        }
      }
    } else {
      $content = new ContentController;
      $node = Node::load($id);
      $content->combineContentParagraphs($node);
    }
  }
}
$class = new CombineParagraphs();
$class->combineTextParagraphs();
