<?php

namespace Drupal\scheme_list\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller for showing all schemes for a state.
 */
class StateWiseSchemeList extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
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
   * Returns a page title.
   */
  public function getTitle($state) {
    $statename = str_replace("-"," ", $state);
    $state = ucwords($statename);
    // print_r($state);
    return 'Scheme List for '. $state;
  }

  /**
   * View all the schemes for a state.
   *
   * @param string $state
   *   The state name.
   */
  public function viewScheme($state) {
    $statename = str_replace("-"," ", $state);
    $state = ucwords($statename);

    if($state == "Jammu And Kashmir")
    {
	    $state="Jammu and Kashmir";
    }
    $tid = $this->getTidByName($state, 'govt');
    if($tid == 'no scheme') {
      $result[0]['title'] = 'No Scheme Found';
      $result[0]['url'] = '#';
      $build['scheme_list'] = [
        '#theme' => 'scheme_list',
        '#data' => $result,
      ];
      return $build;
    }
    $term = $this->entityTypeManager()->getStorage('taxonomy_term')->load($tid);
    $leader_image = $term->field_leader->entity->getFileUri();
    $leader_image_url = file_create_url($leader_image);
    $nodes = $this->entityTypeManager()->getStorage('node')->loadByProperties([
        'field_govt' => $tid,
      ]);
    
    foreach($nodes as $key => $node) {
      $languages = \Drupal::languageManager()->getLanguages();
      foreach($languages as $language) {
        $lang_id = $language->getId();
        if ($node->hasTranslation($lang_id)) {
          $translation = $node->getTranslation($lang_id);
          $result[$key][$lang_id]['title'] = $translation->label();
          $result[$key][$lang_id]['url'] = $translation->toUrl()->toString();
          $result[$key][$lang_id]['langcode'] = $language->getName();
        } 
      }
    }

    $build['scheme_list'] = [
      '#theme' => 'scheme_list',
      '#data' => $result,
      '#leader_image' => $leader_image_url,
    ];
    return $build;
  }

  private function getTidByName(string $name, string $vid) {
    $term = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => $name, 'vid' => $vid]);
    $term = reset($term);
    if ($term) {
      return $term->id();
    }
    return 'no scheme';
  }

}
