<?php

/**
 * @file
 * Token integration for the Infofinland common module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info()
 */

function infofinland_common_token_info() {
  $info = [];

  $info['tokens']['array']['join-path-en'] = array(
    'name' => t('Menu link parents en'),
    'description' => t('Menu link parents in english'),
  );

  return $info;
}


/**
 * Implements hook_tokens()
 *
 * Custom token for getting menu path in english.
 */

function infofinland_common_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'array' && !empty($data['array'])) {
    $array = $data['array'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'join-path-en':
          $values = [];
          foreach (token_element_children($array) as $key) {
            $uuid = substr($key, strlen('menu_link_content:'));
            $menu_link_content = \Drupal::service('entity.repository')->loadEntityByUuid('menu_link_content', $uuid);

            if ($entity_link = $menu_link_content->get('link')->getString()) {
              $nid = substr($entity_link, strlen('entity:node/'));
              if ($node = \Drupal::entityTypeManager()->getStorage('node')->load($nid)) {
                $field_url = $node->get('field_url')->getString();
              }
            }

            if ($field_url) {
              $value = \Drupal::service('pathauto.alias_cleaner')->cleanString($field_url, $options);
            } else {
              $value = is_array($array[$key]) ? render($array[$key]) : (string) $array[$key];
            }
            $values[] = $value;
          }
          $replacements[$original] = implode('/', $values);
          break;
      }
    }
  }

  return $replacements;
}
