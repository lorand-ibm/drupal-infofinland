<?php

namespace Drupal\infofinland_common\Plugin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * An example controller.
 */
class ContentController extends ControllerBase {

  /**
   * Returns a render-able array for a test page.
   * @param EntityInterface $node
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function combineContentParagraphs(EntityInterface $node, $includeRevision = true) {
    $paragraphs = $node->get('field_content')->referencedEntities();
    $combined = '';
    $combinedKeys = [];
    $newContent = [];
    foreach ($paragraphs as $key => $paragraph) {
      if ($paragraph->getType() !== 'text') {
        continue;
      }
      if (((isset($paragraphs[$key + 1]) && $paragraphs[$key + 1]->getType() != 'text')
        || !isset($paragraphs[$key + 1])) && $combined == '') {
        continue;
      }
      if($paragraph->getType() == 'text') {
        if (!isset($delta)) {
          $delta = $key;
        }
        $fieldText = $paragraph->field_text->value;
        if(!str_starts_with($fieldText, '<p') && !str_starts_with($fieldText, '<h3')
          && !str_starts_with($fieldText, '<h4') && !str_starts_with($fieldText, '<a')
          && !str_starts_with($fieldText, '<ul') && !str_starts_with($fieldText, '<h6')) {
          $fieldText = '<p>' . $fieldText . '</p>';
        }
        $combined = $combined . $fieldText;
        $combinedKeys[] = $key;
      }
      if (((isset($paragraphs[$key + 1]) && $paragraphs[$key + 1]->getType() != 'text')
          || !isset($paragraphs[$key + 1]))
        && $combined != '' && count($combinedKeys) > 1) {
        $newParagraph = Paragraph::create([
          'type' => 'text',
          'langcode' => $node->langcode->value,
          'delta' => $delta,
          'field_text' => array(
            "value"  =>  ltrim($combined),
            "format" => "full_html"
          ),
        ]);
        $newContent[$delta] = $newParagraph;
        unset($delta);
        $combined = '';
      }
    }
    if (empty($combinedKeys)) {
      return;
    }
    foreach ($paragraphs as $key => $paragraph) {
      if (!in_array($key, $combinedKeys)) {
        $newContent[$key] = $paragraph;
      }
    }
    ksort($newContent);
    $node->field_content = $newContent;
    if ($includeRevision) {
      $node->setNewRevision(TRUE);
      $node->revision_log = 'Automatically combined content for ' . $node->id();
      $node->setRevisionCreationTime(time());
    }
    $node->save();
  }

}
