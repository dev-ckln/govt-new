<?php

namespace Drupal\module_hero\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * This is our hero controller.
 */
class HeroController {

  public function heroList() {

    $heroes = [
      ['name' => 'Hulk'],
      ['name' => 'Thor'],
      ['name' => 'Iron Man'],
      ['name' => 'Luke Cage'],
      ['name' => 'Black Widow'],
      ['name' => 'Daredevil'],
      ['name' => 'Captain America'],
      ['name' => 'Wolverine']
    ];

    $ourHeroes = '';
    foreach ($heroes as $hero) {
      $ourHeroes .= '<li>' . $hero['name'] . '</li>';
    }

    return [
      '#theme' => 'hero_list',
      '#items' =>$heroes,
      '#title' => 'Heero list'
    ];

  }
}
