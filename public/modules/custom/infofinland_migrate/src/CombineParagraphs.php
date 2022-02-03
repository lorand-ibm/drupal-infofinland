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
    $drupalDb = Database::getConnection('default', 'default');
    return $drupalDb->select('node__field_content', 'nfc')
      ->fields('nfc', ['entity_id'])
      ->groupBy('nfc.entity_id')
      ->groupBy('langcode')
      ->having('COUNT(nfc.entity_id) > :entity_id', [':entity_id' => 80])
      ->execute()
      ->fetchAll();
  }

  public function combineTextParagraphs() {
    $id = $_SERVER['argv'][3];
    if ($id === null) {
      $content = $this->getLargeContent();
      if (empty($content)) {
        return;
      }
      foreach ($content as $row) {
        echo PHP_EOL;
        echo "Now fixing entity " . $row->entity_id;
        $node = Node::load($row->entity_id);
        $languages = $node->getTranslationLanguages();
        $content = new ContentController;
        foreach ($languages as $langcode => $language) {
          echo PHP_EOL;
          echo "for language " . $langcode;
          $content->combineContentParagraphs($node->getTranslation($langcode), false);
        }
      }
    } else {
      $content = new ContentController;
      $node = Node::load($id);
      $languages = $node->getTranslationLanguages();
      foreach ($languages as $langcode => $language) {
        $content->combineContentParagraphs($node->getTranslation($langcode), false);
      }
    }
  }
}
$class = new CombineParagraphs();
$class->combineTextParagraphs();
