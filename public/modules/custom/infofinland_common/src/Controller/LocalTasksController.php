<?php

namespace Drupal\infofinland_common\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LocalTasksController extends ControllerBase {
  /**
   * Publish all node translations at once.
   *
   * @param Drupal\node\Entity $node
   *   Current node.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response we want to send to the user.
   */
  public function publishAllTranslations($node): RedirectResponse {

    $translation_languages = $node->getTranslationLanguages();

    foreach ($translation_languages as $language) {
      $node_translation = $node->getTranslation($language->getId());
      if ($node_translation->get('moderation_state')->getString() === 'published') {
        continue;
      }
      $node_translation->setNewRevision(TRUE);
      $node_translation->revision_log = 'All language versions published at once';
      $node_translation->set('moderation_state', 'published');
      $node_translation->save();
    }
    $this->messenger()->addMessage($this->t('All language versions for this page published.'));
    return $this->redirect('entity.node.edit_form', ['node' => $node->id()]);
  }
}
