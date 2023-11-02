<?php
$node = \Drupal\node\Entity\Node::load(6047);
$entity_manager = \Drupal::entityTypeManager();
  try {
    $storage = $entity_manager->getStorage('comment');
    $commentField = $node->get('comment');
    $comments = $storage->loadThread($node, $commentField->getFieldDefinition()->getName(), \Drupal\comment\CommentManagerInterface::COMMENT_MODE_FLAT);
    if (empty($comments)){
      return;
    }
    /** @var \Drupal\comment\Entity\Comment $comment */
    foreach ($comments as $comment) {
      //Logic here
    }

  } catch (\Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException $e) {
  } catch (\Drupal\Component\Plugin\Exception\PluginNotFoundException $e) {
  }
