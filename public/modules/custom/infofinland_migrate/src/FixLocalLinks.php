<?php

namespace Drupal\infofinland_migrate\Plugin;

use DOMDocument;
use Drupal\Core\Database\Database;
use Drupal\paragraphs\Entity\Paragraph;

/**
 *
 * This file is used to fix page reference links after migration.
 * It should only be used by running it with drush
 *
 * Class FixLocalLinks
 * @package Drupal\infofinland_migrate\Plugin\migrate
 */
class FixLocalLinks {

  private function editLink($child, $text) {
    if ($child->tagName == 'a') {
      $url = $child->getAttribute('href');
      if (str_contains($url, 'prime://pagereference')) {
        $url = strtok($url, '?');
        $link_array = explode('/',$url);
        $id = end($link_array);
        $drupalDb = Database::getConnection('default', 'default');
        if ($text->language()->getId() == 'fi') {
          $nodeID = $drupalDb->select('migrate_map_content_import_pages_to_nodes_from_csv_fi', 'mm')
            ->fields('mm', ['destid1'])
            ->condition('mm.sourceid1', $id, '=')
            ->execute()
            ->fetchObject();
        } elseif ($text->language()->getId() == 'en') {
          $nodeID = $drupalDb->select('migrate_map_content_import_pages_to_nodes_from_csv_en', 'mm')
            ->fields('mm', ['destid1'])
            ->condition('mm.sourceid1', $id, '=')
            ->execute()
            ->fetchObject();
        } else {
          $nodeID = $drupalDb->select('migrate_map_content_import_pages_to_nodes_from_csv_translations', 'mm')
            ->fields('mm', ['destid1'])
            ->condition('mm.sourceid2', $id, '=')
            ->execute()
            ->fetchObject();
        }
        if (isset($nodeID->destid1) && $nodeID->destid1 !== null) {
          $child->setAttribute('href', '/node/'. $nodeID->destid1);
        } else {
          $child->setAttribute('class', 'broken-link');
          $child->removeAttribute('href');
        }
      }
    }
    return $child;
  }

  public function getLinks(): array {
    $language = $_SERVER['argv'][3];
    $paragraphs = [];
    $drupalDb = Database::getConnection('default', 'default');
    $results = $drupalDb->select('paragraph__field_text', 'pfm')
      ->fields('pfm', ['entity_id'])
      ->condition('field_text_value', '%href%', 'LIKE')
      ->condition('field_text_value', '%//pagereference%', 'LIKE');
    if ($language !== null) {
      $results->condition('langcode', $language, '=');
    }
    $results = $results->execute()->fetchCol();

    $textParagraphs = Paragraph::loadMultiple($results);

    foreach ($textParagraphs as $text) {
      if (str_contains($text->field_text->value, 'href')) {
        $newHtml = '';
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $text->field_text->value);
        $html = $dom->getElementsByTagName('body')->item(0);
        $doc = $html->ownerDocument;

        foreach ($html->childNodes as $child) {
          if ($child->hasChildNodes() && $child->lastChild->nodeValue != $child->firstChild->nodeValue && $child->tagName != 'ul') {
            foreach ($child->childNodes as $childChild) {
              if ($childChild->tagName == 'a') {
                $childChild->parentNode->replaceChild($this->editLink($childChild, $text), $childChild);
              }
            }
          } else if ($child->tagName == 'a') {
            $child = $this->editLink($child, $text);
          } else if ($child->firstChild->tagName == 'a') {
            $child->firstChild->parentNode->replaceChild($this->editLink($child->firstChild, $text), $child->firstChild);
          } else if ($child->tagName == 'ul') {
            foreach ($child->childNodes as $childChild) {
              if ($childChild->firstChild->tagName == 'a') {
                $newChild = $childChild->firstChild->parentNode->replaceChild($this->editLink($childChild->firstChild, $text), $childChild->firstChild);
                $childChild->parentNode->replaceChild($newChild, $childChild);

              }
            }
          }
          $newHtml .= $doc->saveHTML($child);
        }
      }
      if ($newHtml !== '') {
        $text->set('field_text', $newHtml);
        $text->save();
      }
    }
    return $paragraphs;
  }
}

$class = new FixLocalLinks;
$class->getLinks();
