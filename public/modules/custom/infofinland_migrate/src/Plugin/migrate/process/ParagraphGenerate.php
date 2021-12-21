<?php

namespace Drupal\infofinland_migrate\Plugin\migrate\process;

use DOMDocument;
use Drupal\Core\Database\Database;
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
    $rowID = $row->getSourceProperty('id');
    $result = $this->generateParagraphEntity($value, $row->getSourceProperty('id'));
          $count = 0;

    foreach ($result as $item) {
      $row->id = $count . '_' . $rowID;
      $count = $count +1;

    }
    return $result;
  }

  private function createListHTML($child, $language, $rowId) {
    $htmlString = '<ul>';
    foreach ($child->childNodes as $item) {
      $htmlString  .= '<li>' . ltrim($item->nodeValue) . '</li>';
    }
    $htmlString  .= '</ul>';
    return $htmlString;
    return Paragraph::create([
      'type' => 'text',
      'field_migration_id' => $rowId,
      'langcode' => $language,
      'field_text' => array(
        "value"  =>  $htmlString,
        "format" => "full_html"
      ),
    ]);
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

  private function createHeadingParagraph($string, $language, $rowId) {
    return Paragraph::create([
      'type' => 'heading',
      'field_migration_id' => $rowId,
      'langcode' => $language,
      'field_title' => ltrim($string),
    ]);
  }

  private function createLinkParagraph($string, $url, $rowId) {
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
      'field_migration_id' => $rowId
    ]);
  }

  private function createCorrectParagraph($child, $rowId) {
    $paragraph = [];
    if ($this->checkIfTextParagraph($child->tagName)) {
      $paragraph = $this->createTextParagraph($child->nodeValue, 'fi', $rowId);
    } elseif ($child->tagName === 'h2') {
      $paragraph = $this->createHeadingParagraph($child->nodeValue,'fi', $rowId);
    } elseif ($child->tagName === 'ul') {
      $paragraph = $this->createListHTML($child, 'fi', $rowId);
    } elseif ($child->tagName === 'a') {
      if (str_contains($child->getAttribute('href'), 'prime://repositorylink')) {
        $paragraph = $this->createLinkParagraph($child->nodeValue, $child->getAttribute('href'), $rowId);
      }

    }
    return $paragraph;
  }

  private function checkIfTextParagraph($tag) {
    if ($tag == 'p' || $tag == 'h3' || $tag == 'h4' || $tag == 'div') {
      return true;
    }
    return false;
  }

  private function findPagereferenceUrl($url) {
    $link_array = explode('/',$url);
    $id = end($link_array);

  }

  protected function generateParagraphEntity($value, $rowId) {
    $dom = new DOMDocument();
if ($rowId == '8132')  {
  $rowId = $rowId;
}
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $value);
    $html = $dom->getElementsByTagName('body')->item(0);
    $returnArray= [];
    $textParagraph = '';
    foreach ($html->childNodes as $child) {
      if($child->tagName == 'div' && $child->firstChild->tagName == 'p') {
        if ($textParagraph != '') {
          $textParagraph = $textParagraph . $child->nodeValue;
        } else {
          $paragraph = $this->createCorrectParagraph($child->firstChild, $rowId);
        }
      }
      if($child->tagName == 'div' && $child->firstChild->tagName == 'ul') {
        if ($textParagraph != '') {
          $textParagraph = $textParagraph . $textParagraph . $this->createListHTML($child->firstChild, 'fi', $rowId);
        } else {
          $paragraph = $this->createCorrectParagraph($child->firstChild, $rowId);
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
        $paragraph = $this->createCorrectParagraph($child->childNodes['1'], $rowId);
      } else if ($child->tagName == 'h2') {
        $paragraph = $this->createCorrectParagraph($child, $rowId);
      } else if ($this->checkIfTextParagraph($child->tagName) && isset($child->childNodes['1']) && $child->childNodes['1']->tagName != 'a' && (
        $this->checkIfTextParagraph($child->nextSibling->tagName) || $child->nextSibling->tagName == 'ul') &&
        ($child->childNodes['0']->tagName != 'a')) {
        $textParagraph = $textParagraph . '<' . $child->tagName . '>' . ltrim($child->nodeValue) . '</' . $child->tagName . '>';
      } else if ($child->tagName == 'ul'){
        $textParagraph = $textParagraph . $this->createListHTML($child, 'fi', $rowId);
      } else {
        if (isset($child->childNodes['0']) && $child->childNodes['0']->tagName == 'a' &&
          str_contains($child->childNodes['0']->getAttribute('href'), 'prime://repositorylink')) {
          $paragraph = $this->createCorrectParagraph($child->childNodes['0'], $rowId);
          } else {
          if ($textParagraph == '' && !isset($paragraph)) {
            $paragraph = $this->createCorrectParagraph($child, $rowId);
          }
        }

      }

      if(($this->checkIfTextParagraph($child->tagName) || $child->tagName == 'ul') && (
        !$this->checkIfTextParagraph($child->nextSibling->tagName) && $child->nextSibling->tagName !== 'ul')) {
        if ($textParagraph !== '') {
          $child->nodeValue = $textParagraph . '<' . $child->tagName . '>' . ltrim($child->nodeValue) . '</' . $child->tagName . '>';
        }
        if (!isset($paragraph)) {
          $paragraph = $this->createCorrectParagraph($child, $rowId);
        }
      }
      if ($child->nextSibling->tagName == 'p'  && ($child->nextSibling->firstChild->tagName == 'a'
          && str_contains($child->nextSibling->firstChild->getAttribute('href'), 'prime://repositorylink'))) {
        if ($textParagraph !== '') {
          $child->nodeValue = $textParagraph . '<' . $child->tagName . '>' . ltrim($child->nodeValue) . '</' . $child->tagName . '>';
        }
        if (!isset($paragraph)) {
          $paragraph = $this->createCorrectParagraph($child, $rowId);
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
