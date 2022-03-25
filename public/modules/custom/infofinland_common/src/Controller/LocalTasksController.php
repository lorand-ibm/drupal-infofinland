<?php

namespace Drupal\infofinland_common\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LocalTasksController extends ControllerBase {

  /**
   * Constructs a LocalTasksController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Access control for local tab.
   *
   * @param Drupal\node\Entity $node
   *   Current node id.
   */
  public function localTaskAccess($node) {
    $nodeObject = $this->entityTypeManager->getStorage('node')->load($node);
    if ($nodeObject->bundle() === 'page') {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

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
      $node_translation->setNewRevision(TRUE);
      $node_translation->revision_log = 'All language versions published at once';
      $node_translation->set('moderation_state', 'published');
      $node_translation->save();
    }
    $this->messenger()->addMessage($this->t('All language versions for this page published.'));
    return $this->redirect('entity.node.edit_form', ['node' => $node->id()]);
  }
}
