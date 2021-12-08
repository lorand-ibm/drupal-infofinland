<?php

namespace Drupal\infofinland_migrate\Plugin\migrate\process;

use DOMDocument;
use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityGenerate;
use Drupal\migrate_plus\Plugin\migrate\process\EntityLookup;
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

  private function createListParagraph($child, $language, $rowId) {
    $htmlString = '<ul>';
    foreach ($child->childNodes as $item) {
      $htmlString  .= '<li>' . ltrim($item->nodeValue) . '</li>';
    }
    $htmlString  .= '</ul>';

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

  private function createHeadingParagraph($string, $tag, $language, $rowId) {
    $term_array = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $tag]);
    $term = reset($term_array);
    return Paragraph::create([
      'type' => 'heading',
      'field_migration_id' => $rowId,
      'langcode' => $language,
      'field_heading_text' => ltrim($string),
      'field_heading_tag' => $term->id()
    ]);
  }

  private function createLinkParagraph($string, $url, $rowId) {
    $linkId = substr($url, 23);
    return Paragraph::create([
      'type' => 'simple_link',
      'field_migration_id' => $rowId,
      'field_simple_link' => array(
        "url"  =>  $url,
        "format" => "plain"
      ),
    ]);
  }

  private function createCorrectParagraph($child, $rowId) {
    $paragraph = [];
    if ($child->tagName === 'p') {
      $paragraph = $this->createTextParagraph($child->nodeValue, 'fi', $rowId);
    } elseif ($child->tagName === 'h2' | $child->tagName === 'h3' | $child->tagName === 'h4') {
      $paragraph = $this->createHeadingParagraph($child->nodeValue, $child->tagName, 'fi', $rowId);
    } elseif ($child->tagName === 'ul') {
      $paragraph = $this->createListParagraph($child, 'fi', $rowId);
    } elseif ($child->tagName === 'a') {
      if (str_contains($child->getAttribute('href'), 'prime://repositorylink')) {
        $paragraph = $this->createLinkParagraph($child->nodeValue, $child->getAttribute('href'), $rowId);
      }

    }
    return $paragraph;
  }

  protected function generateParagraphEntity($value, $rowId) {
    $dom = new DOMDocument();

    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $value);
    $html = $dom->getElementsByTagName('body')->item(0);
    $returnArray= [];
    $textParagraph = '';
    foreach ($html->childNodes as $child) {
      if (count($child->childNodes) >= 1 && $child->tagName !== 'ul') {
        foreach ($child->childNodes as $childChild) {
          if ($childChild->tagName == 'a' && !str_contains($child->getAttribute('href'), 'prime://repositorylink')) {
            // If the p text is same as a text it means that the p actually doesnt have text
            if ($child->nodeValue === $childChild->nodeValue) {
              $child->nodeValue = '<a href=' . $childChild->getAttribute('href') . '>' . $childChild->nodeValue . '</a>';
            } else {
              $child->nodeValue = $child->nodeValue . '<a href=' . $childChild->getAttribute('href') . '>' . $childChild->nodeValue . '</a>';
            }
            break;
          }
          if ($child->tagName == 'p' && $child->nextSibling->tagName == 'p') {
            $textParagraph = $textParagraph . '<p>' . ltrim($child->nodeValue) . '</p>';
          } else {
            $paragraph = $this->createCorrectParagraph($childChild, $rowId);
          }

          if (isset($paragraph) && !empty($paragraph)) {
            $paragraph->save();
            $returnArray[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
          }
        }
      }
      if ($child->tagName == 'p' && $child->nextSibling->tagName == 'p') {
        $textParagraph = $textParagraph . '<p>' . ltrim($child->nodeValue) . '</p>';
      } else {
        if ($textParagraph !== '') {
          $child->nodeValue = $textParagraph . '<p>' . ltrim($child->nodeValue) . '</p>';
        }
        $paragraph = $this->createCorrectParagraph($child, $rowId);
      }
      if (isset($paragraph) && !empty($paragraph)) {
        $paragraph->save();
        $returnArray[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
        $textParagraph = '';
      }

    }
    return empty($returnArray) ? NULL : $returnArray;
  }

}
