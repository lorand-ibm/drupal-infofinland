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
   * Get the raw html
   * @param $node
   * @return string
   */
  private function getInnerXML($node) {
    $doc  = $node->ownerDocument;
    $frag = $doc->createDocumentFragment();
    if (in_array($node->tagName, ['h3', 'h4', 'h5', 'h6'])) {
      if (!isset($node->childNodes[0]) || $node->childNodes[0]->tagName == null) {
        return '<' . $node->tagName . '>' . $node->nodeValue . '</' . $node->tagName . '>';
      } else {
        if ($node->childNodes[0]->tagName == 'a') {
          if (!str_contains($node->childNodes[0]->getAttribute('href'), 'prime://repositorylink')) {
            return '<' . $node->tagName . '>' . '<a href=' . $node->childNodes[0]->getAttribute('href') . '>' . $node->childNodes[0]->nodeValue . '</a>' . '</' . $node->tagName . '>';
          }
        } else {
          return '<' . $node->tagName . '>' . '<' . $node->childNodes[0]->tagName . '>' . $node->childNodes[0]->nodeValue . '</' . $node->childNodes[0]->tagName . '>' . '</' . $node->tagName . '>';
        }
      }
    }
    foreach ($node->childNodes as $child) {
      $frag->appendChild($child->cloneNode(TRUE));
    }
    return $doc->saveXML($frag);
  }

  /**
   * @param $child
   * @param $language
   * @param $rowId
   * @return \Drupal\Core\Entity\EntityBase|EntityInterface
   */
  private function createListHTML($child, $language, $rowId, $tag)
  {
    if (str_contains($child->nodeValue, '<ul><li>') || str_contains($child->nodeValue, '<ol><li>')) {
      $htmlString = $child->nodeValue;
    } else {
      $htmlString = '<' . $tag . '>';
      foreach ($child->childNodes as $item) {
        if ($item->hasChildNodes()) {
          $htmlString .=  '<li>' . $this->getInnerXML($item) . '</li>';
        }
        $htmlString  .= '<li>' . ltrim($item->nodeValue) . '</li>';
      }
      $htmlString  .= '</' . $tag . '>';
    }

    return Paragraph::create([
      'type' => 'text',
      'field_migration_id' => $rowId,
      'langcode' => $language,
      'field_text' => array(
        "value"  =>  ltrim($htmlString),
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
   * @return \Drupal\Core\Entity\EntityBase|EntityInterface|void
   */
  private function createLinkParagraph($url, $rowId, $lang) {
    if (substr_count($url, '"') == 1) {
      $url = substr_replace($url ,"", -1);
    }
    $linkId = substr($url, 23);
    $drupalDb = Database::getConnection('default', 'default');
    if ($lang == 'fi') {
      $results = $drupalDb->select('migrate_map_links_import_link_nodes_from_csv_fi', 'liln')
        ->fields('liln', ['destid1'])
        ->condition('liln.sourceid1',$linkId, '=')
        ->execute()
        ->fetchAll();
    } else {
      $results = $drupalDb->select('migrate_map_links_import_link_nodes_from_csv_translations', 'liln')
        ->fields('liln', ['destid1'])
        ->condition('liln.sourceid2',$linkId, '=')
        ->condition('liln.destid2', $lang, '=')
        ->execute()
        ->fetchAll();
    }

    // If we didnt find the link in the language we expected, then we need to do it the other way
    if($results == null || $results[0] == null) {
      if ($lang == 'fi') {
        $results = $drupalDb->select('migrate_map_links_import_link_nodes_from_csv_translations', 'liln')
          ->fields('liln', ['destid1'])
          ->condition('liln.sourceid2',$linkId, '=')
          ->execute()
          ->fetchAll();
      } else {
        $results = $drupalDb->select('migrate_map_links_import_link_nodes_from_csv_fi', 'liln')
          ->fields('liln', ['destid1'])
          ->condition('liln.sourceid1',$linkId, '=')
          ->execute()
          ->fetchAll();
      }
    }
    if($results == null || $results[0] == null) {
      echo "Result in link is null for url " . $url .  ' AND link id ' . $linkId;
      return;
    }
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
   * @param $tag
   * @return array|\Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface|string
   */
  private function createCorrectParagraph($child, $rowId, $lang, $tag = '') {
    $tag = $tag != '' ? $tag : $child->tagName;
    $paragraph = [];
    if ($this->checkIfTextParagraph($tag)) {
      $paragraph = $this->createTextParagraph($child->nodeValue, $lang, $rowId);
    } elseif ($tag === 'h2') {
      $paragraph = $this->createHeadingParagraph($child->nodeValue,$lang, $rowId);
    } elseif ($tag === 'ul' || $tag === 'ol') {
      $paragraph = $this->createListHTML($child, $lang, $rowId, $tag);
    } elseif ($tag === 'a') {
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
    if ($tag == 'p' || $tag == 'h3' || $tag == 'h4' || $tag == 'h5') {
      return true;
    }
    return false;
  }

  /**
   * We need to remove the possible Anchor list from the beginning of the content.
   * @param $html
   * @return mixed
   */
  private function removeAnchorList($html) {
    if ($html->firstChild->tagName == 'ul') {
      if (str_contains($this->getInnerXML($html->firstChild), 'href="#')){
        $html->removeChild($html->firstChild);
      }
    } else if ($html->firstChild->tagName == 'p' && $html->firstChild->nextSibling->tagName == 'ul') {
      if (str_contains($this->getInnerXML($html->firstChild->nextSibling), 'href="#')){
        $html->removeChild($html->firstChild->nextSibling);
      }
    }
    return $html;
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
    echo "Now processing row with id " . $rowId;
    $dom = new DOMDocument();

    //Strip out tag we dont want like divs and images
    $stripedHTML = strip_tags($value, "<p><a><h2><h3><h4><h5><h6><b><br><ul><ol><li>");
    $langcode = trim($language);
    $html_data  = mb_convert_encoding($stripedHTML , 'HTML-ENTITIES', 'UTF-8');
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html_data);
    $html = $dom->getElementsByTagName('body')->item(0);
    $this->removeAnchorList($html);
    $returnArray= [];
    $textParagraph = "";
    $paragraphs = [];
    if (is_null($html)) {
      return NULL;
    }
    foreach ($html->childNodes as $child) {
      // If we have a text type paragraph or a list
      if ($this->checkIfTextParagraph($child->tagName) || $child->tagName == 'ul' || $child->tagName == 'ol') {
        $innerHtml = $this->getInnerXML($child);
        if (!str_contains($innerHtml, 'prime://repositorylink') &&
        !str_contains($innerHtml, 'h2')) {
          if (!in_array($child->tagName, ['h3', 'h4', 'h5', 'h6'])) {
            $textParagraph .= '<' . $child->tagName . '>' . $innerHtml . '</' . $child->tagName . '>';
          } else {
            $textParagraph .= $innerHtml;
          }
        } else {
          $childNotesCount = count($child->childNodes);
          //If the child has children, typically this is an a inside of p
          if ($childNotesCount > 1) {
            for ($key = 0; $key <= $childNotesCount; $key++) {
              if ($child->childNodes[$key]->tagName == 'a') {
                if (!str_contains($child->childNodes[$key]->getAttribute('href'), 'prime://repositorylink')) {
                  $textParagraph .=  '<a href=' . $child->getAttribute('href') . '>' . $child->nodeValue . '</a>';
                } else {
                  $paragraphs[] = $this->createCorrectParagraph($child->childNodes[$key], $rowId, $langcode);
                }
              } else {
                $paragraphs[] = $this->createCorrectParagraph($child->childNodes[$key], $rowId, $langcode);
              }
            }
          } else {
            if ($child->childNodes[0]->tagName == 'a') {
              if (!str_contains($child->childNodes[0]->getAttribute('href'), 'prime://repositorylink')) {
                $textParagraph .= '<a href=' . $child->getAttribute('href') . '>' . $child->nodeValue . '</a>';;
              } else {
                $paragraphs[] = $this->createCorrectParagraph($child->childNodes[0], $rowId, $langcode);
              }
            } else {
              $paragraphs[] = $this->createCorrectParagraph($child->childNodes[0], $rowId, $langcode);
            }
          }
        }
      } else if ($child->tagName == 'a') {
        if (!str_contains($child->getAttribute('href'), 'prime://repositorylink')) {
          $textParagraph .= '<a href=' . $child->getAttribute('href') . '>' . $child->nodeValue . '</a>';;
        } else {
          $paragraphs[] = $this->createCorrectParagraph($child, $rowId, $langcode);
        }
      } else if ($child->tagName == 'h2') {
        $paragraphs[] = $this->createCorrectParagraph($child, $rowId, $langcode);
      }

      // Save paragraph if the next one isnt the same type.
      if (empty($paragraphs) && $textParagraph !== "" &&
        (!isset($child->nextSibling) || ($child->nextSibling->tagName != 'p' && !in_array($child->nextSibling->tagName, ['ul', 'h3', 'h4', 'h5', 'h6', 'ol'])) ||
          ($child->nextSibling->tagName == 'p' && $child->nextSibling->hasChildNodes() && $child->nextSibling->childNodes[0]->tagName == 'a' && str_contains($child->nextSibling->childNodes[0]->getAttribute('href'), 'prime://repositorylink')) ||
          !$child->nextSibling)) {
        $child->nodeValue = $textParagraph;
        $tag = substr($textParagraph, 1, 1) == 'u' || substr($textParagraph, 1, 1) == 'h' ?
          substr($textParagraph, 1, 2) : substr($textParagraph, 1, 1);
        $paragraphs[] = $this->createCorrectParagraph($child, $rowId, $langcode, $tag);
      }
      if (isset($paragraphs) && !empty($paragraphs)) {
        foreach ($paragraphs as $paragraph) {
          if (is_object($paragraph)) {
            $paragraph->save();
            $returnArray[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
          } else {
            $paragraph = $this->createCorrectParagraph($child, $rowId, $langcode);
            if (is_object($paragraph)) {
              $paragraph->save();
              $returnArray[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
            }
          }
        }
        $textParagraph = '';
        $paragraphs = [];
      }

    }
    return empty($returnArray) ? NULL : $returnArray;
  }

}
