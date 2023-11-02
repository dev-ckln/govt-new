<?php
namespace Drupal\module_schemesubscribe\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
/**
 * This is our multistep data form controller.
 */
class MultistepformController {

   public function getform() {
	 return true;
  }
  
  public function schemelisting($state_id) {
	 \Drupal::service('page_cache_kill_switch')->trigger();
	 $tempstore = \Drupal::service('tempstore.private')->get('module_schemesubscribe');
     $user_data = $tempstore->get('user_data');
	 
	 
	 $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
	 $nodeCollection  = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'scheme','field_govt'=> $state_id,'status' => 1]);
	 $nodes = [];
	 
	 foreach ($nodeCollection as $n) {
		if($n->hasTranslation($langcode)){
				$translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($n, $langcode);
				$node_id = $n->id();
				$node_title = $translated_term->getTitle();
				$selected = 0;
				
				if(isset($user_data['state_scheme_types']) && in_array($node_id,$user_data['state_scheme_types'])){
					$selected = 1;
				}
				$nodes[] = array('node_id' => $node_id, 'title'=> $node_title,'selected'=> $selected);
			}
	  }
	  
	  if(empty($nodes)){
		 $nodes[] = array('node_id' => 0, 'title'=> 'No scheme types found','selected'=> 0);
	  }
	  
	  return new JsonResponse($nodes);  
  }
  
   public function emailactivation($id) {
	    $node_id = base64_decode($id);
	    $path = \Drupal\Core\Url::fromRoute('module_schemesubscribe.states_subscription')->toString();
		if(is_numeric($node_id)){
			$node_entity = \Drupal::entityTypeManager();
			$messenger = \Drupal::service("messenger");
			
			$nodeCollection  = $node_entity->getStorage('node')->loadByProperties(['type' => 'scheme_subscribers','nid'=> $node_id]);
			if(!empty($nodeCollection)){
				 foreach ($nodeCollection as $n) {
						$nid = $n->id();
						$subscriber_email_verified = $n->field_subscriber_email_verified->value;
						$subscriber_otp_verified = $n->field_subscriber_otp_verified->value;
						
						if($subscriber_email_verified==0){
							  $node = Node::load($nid);
							  $node->field_subscriber_email_verified = 1;
                              $node->save();
							  $messenger->addStatus(t('Congratulations! your email has been verified successfully!'));
							  $path = \Drupal\Core\Url::fromRoute('module_schemesubscribe.verifyotp',array('id' => base64_encode($nid)))->toString();
						}
						
						if($subscriber_otp_verified==0){
							  $path = \Drupal\Core\Url::fromRoute('module_schemesubscribe.verifyotp',array('id' => base64_encode($nid)))->toString();
						}
						
						if($subscriber_email_verified==1 && $subscriber_otp_verified==1){
							 $messenger->addError(t('Invalid Access!!'));
				 			 $path = \Drupal\Core\Url::fromRoute('module_schemesubscribe.states_subscription')->toString();
						}
	  			}// node collection foreach
			}else{ //node collections
				 $messenger->addError(t('Invalid Access!!'));
				 $path = \Drupal\Core\Url::fromRoute('module_schemesubscribe.states_subscription')->toString();
			}
		}
  		return new RedirectResponse($path);
  }
  
  
   public function successpage() {
		$information_data['msg'] = 'Please check your inbox and continue there...';
		
		 return [
		  '#theme' => 'multistep_form',
		  '#title' => t('Thank You!!'),
		  '#data' => $information_data,
    	];
   }
   
   
   public function manageschemesinfo() {
		 $information_data['msg'] = t('Schemes Information updated successfully!');
		 return [
		  '#theme' => 'schemesupdate_form',
		  '#title' => t('Thank You!!'),
		  '#data' => $information_data,
    	];
   }
}
