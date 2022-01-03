<?php

namespace Drupal\infofinland_migrate\Plugin\migrate\process;

use DOMDocument;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityGenerate;
use Drupal\paragraphs\Entity\Paragraph;

/**
 *
 * @MigrateProcessPlugin(
 *   id = "paragraph_generate"
 * )
 */
class ParagraphGenerate extends EntityGenerate {

  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    $this->row = $row;
    $this->migrateExecutable = $migrateExecutable;
    return $this->generateParagraphEntity($value, $row->getSourceProperty('id'), $row->getSourceProperty('Kieli'));
  }

  /**
   * @param $child
   * @param $language
   * @param $rowId
   * @return string
   */
  private function createListHTML($child, $language, $rowId): string
  {
    $htmlString = '<ul>';
    foreach ($child->childNodes as $item) {
      $htmlString  .= '<li>' . ltrim($item->nodeValue) . '</li>';
    }
    $htmlString  .= '</ul>';
    return $htmlString;
  }

  private function createTextParagraph($string, $language, $rowId) {
    return Paragraph::create([
      'type' => 'text',
      'field_migration_id' => $rowId,
      'langcode' => $language,
      'field_text' => array(
        "value"  =>  ltrim($string),
        "format" => "full_html"
      ),
    ]);
  }

  /**
   * @param string $string
   * @param string $language
   * @param $rowId
   * @return EntityInterface
   */
  private function createHeadingParagraph($string, $language, $rowId): EntityInterface {
    return Paragraph::create([
      'type' => 'heading',
      'field_migration_id' => $rowId,
      'langcode' => $language,
      'field_title' => ltrim($string),
    ]);
  }

  /**
   * @param $url
   * @param $rowId
   * @param $lang
   * @return EntityInterface
   */
  private function createLinkParagraph($url, $rowId, $lang): EntityInterface {
    $linkId = substr($url, 23);
    $drupalDb = Database::getConnection('default', 'default');
    $results = $drupalDb->select('migrate_map_links_import_link_nodes_csv_fi', 'liln')
      ->fields('liln', ['destid1'])
      ->condition('liln.sourceid1',$linkId, '=')
      ->execute()
      ->fetchAll();

    return Paragraph::create([
      'type' => 'language_link_collection',
      'field_link_collection' => array(
        "target_id"  =>  $results[0]->destid1,
      ),
      'field_migration_id' => $rowId,
      'langcode' => $lang
    ]);
  }

  /**
   * @param $child
   * @param int $rowId
   * @param string $lang
   * @return array|\Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface|string
   */
  private function createCorrectParagraph($child, $rowId, $lang) {
    $paragraph = [];
    if ($this->checkIfTextParagraph($child->tagName)) {
      $paragraph = $this->createTextParagraph($child->nodeValue, $lang, $rowId);
    } elseif ($child->tagName === 'h2') {
      $paragraph = $this->createHeadingParagraph($child->nodeValue,$lang, $rowId);
    } elseif ($child->tagName === 'ul') {
      $paragraph = $this->createListHTML($child, $lang, $rowId);
    } elseif ($child->tagName === 'a') {
      if (str_contains($child->getAttribute('href'), 'prime://repositorylink')) {
        $paragraph = $this->createLinkParagraph($child->getAttribute('href'), $rowId, $lang);
      }

    }
    return $paragraph;
  }

  /**
   * @param $tag
   * @return bool
   */
  private function checkIfTextParagraph($tag): bool
  {
    if ($tag == 'p' || $tag == 'h3' || $tag == 'h4' || $tag == 'div') {
      return true;
    }
    return false;
  }

  /**
   * @param $value
   * @param $rowId
   * @param $language
   * @return array|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function generateParagraphEntity($value, $rowId, $language): ?array
  {
    $dom = new DOMDocument();
    $langcode = trim($language);
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $value);
    $html = $dom->getElementsByTagName('body')->item(0);
    $returnArray= [];
    $textParagraph = '';
    foreach ($html->childNodes as $child) {
      if($child->tagName == 'div' && $child->firstChild->tagName == 'p') {
        if ($textParagraph != '') {
          $textParagraph = $textParagraph . $child->nodeValue;
        } else {
          $paragraph = $this->createCorrectParagraph($child->firstChild, $rowId, $langcode);
        }
      }
      if($child->tagName == 'div' && $child->firstChild->tagName == 'ul') {
        if ($textParagraph != '') {
          $textParagraph = $textParagraph . $textParagraph . $this->createListHTML($child->firstChild, $langcode, $rowId);
        } else {
          $paragraph = $this->createCorrectParagraph($child->firstChild, $rowId, $langcode);
        }
      }
      if (isset($child->childNodes['1']) && $child->childNodes['1']->tagName == 'a' && !str_contains($child->childNodes['1']->getAttribute('href'), 'prime://repositorylink')) {
        // If the p text is same as a text it means that the p actually doesnt have text
        if ($child->nodeValue == $child->childNodes['1']->nodeValue) {
          $child->nodeValue = '<a href=' . $child->childNodes['1']->getAttribute('href') . '>' . $child->childNodes['1']->nodeValue . '</a>';
        } else {
          $nodeString = $child->childNodes['0']->nodeValue . ' <a href=' . $child->childNodes['1']->getAttribute('href') . '>' .$child->childNodes['1']->nodeValue . '</a>';
          if (isset($child->childNodes['2'])) {
            $nodeString = $nodeString . $child->childNodes['2']->nodeValue;
          }
          $child->nodeValue = $nodeString;
        }
      }
      if ($this->checkIfTextParagraph($child->tagName) && isset($child->childNodes['0']) && $child->childNodes['0']->tagName == 'a' && !str_contains($child->childNodes['0']->getAttribute('href'), 'prime://repositorylink')) {
        $child->nodeValue = '<a href=' . $child->childNodes['0']->getAttribute('href') . '>' . $child->childNodes['0']->nodeValue . '</a>';
      }
      if ($this->checkIfTextParagraph($child->tagName) && isset($child->childNodes['1']) && $child->childNodes['1']->tagName == 'a' && str_contains($child->childNodes['1']->getAttribute('href'), 'prime://repositorylink')) {
        $paragraph = $this->createCorrectParagraph($child->childNodes['1'], $rowId, $langcode);
      } else if ($child->tagName == 'h2') {
        $paragraph = $this->createCorrectParagraph($child, $rowId, $langcode);
      } else if ($this->checkIfTextParagraph($child->tagName) && isset($child->childNodes['1']) && $child->childNodes['1']->tagName != 'a' && (
        $this->checkIfTextParagraph($child->nextSibling->tagName) || $child->nextSibling->tagName == 'ul') &&
        ($child->childNodes['0']->tagName != 'a')) {
        $textParagraph = $textParagraph . '<' . $child->tagName . '>' . ltrim($child->nodeValue) . '</' . $child->tagName . '>';
      } else if ($child->tagName == 'ul'){
        $textParagraph = $textParagraph . $this->createListHTML($child, $langcode, $rowId);
      } else {
        if (isset($child->childNodes['0']) && $child->childNodes['0']->tagName == 'a' &&
          str_contains($child->childNodes['0']->getAttribute('href'), 'prime://repositorylink')) {
          $paragraph = $this->createCorrectParagraph($child->childNodes['0'], $rowId, $langcode);
          } else {
          if ($textParagraph == '' && !isset($paragraph)) {
            $paragraph = $this->createCorrectParagraph($child, $rowId, $langcode);
          }
        }

      }

      if(($this->checkIfTextParagraph($child->tagName) || $child->tagName == 'ul') && (
        !$this->checkIfTextParagraph($child->nextSibling->tagName) && $child->nextSibling->tagName !== 'ul')) {
        if ($textParagraph !== '') {
          $child->nodeValue = $textParagraph . '<' . $child->tagName . '>' . ltrim($child->nodeValue) . '</' . $child->tagName . '>';
        }
        if (!isset($paragraph)) {
          $paragraph = $this->createCorrectParagraph($child, $rowId, $langcode);
        }
      }
      if ($child->nextSibling->tagName == 'p'  && ($child->nextSibling->firstChild->tagName == 'a'
          && str_contains($child->nextSibling->firstChild->getAttribute('href'), 'prime://repositorylink'))) {
        if ($textParagraph !== '') {
          $child->nodeValue = $textParagraph . '<' . $child->tagName . '>' . ltrim($child->nodeValue) . '</' . $child->tagName . '>';
        }
        if (!isset($paragraph)) {
          $paragraph = $this->createCorrectParagraph($child, $rowId, $langcode);
        }
      }
      if (isset($paragraph) && !empty($paragraph) && is_object($paragraph)) {
        $paragraph->save();
        $returnArray[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
        $textParagraph = '';
        unset($paragraph);
      }

    }
    return empty($returnArray) ? NULL : $returnArray;
  }

}
