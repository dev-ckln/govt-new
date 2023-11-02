<?php
$entity_manager = \Drupal::entityTypeManager();
$cids = $entity_manager
    ->getStorage('comment')
    ->getQuery('AND')
//    ->condition('entity_id', 'NODE_ID')
    ->condition('entity_type', 'node')
    ->condition('comment_type', 'comment')
    ->execute();


$comments = [];

foreach($cids as $cid) {
	print("$cid\n");
$comment=	\Drupal::entityTypeManager()->getStorage('comment')->load($cid);
//print_r($comment);
if(     date('Y-m-d H:i:s', $comment->get('created')->value)  > "2023-08-17" ){
	print_r($comment->get('comment_body')->value );
}

/*
 $comments[] = [
     'cid' => $cid,
     'uid' => $comment->getOwnerId(),
     'subject' => $comment->get('subject')->value,
     'body' => $comment->get('field_comment_body')->value,
     'created' => $comment->get('created')->value
 ];
 */
}
