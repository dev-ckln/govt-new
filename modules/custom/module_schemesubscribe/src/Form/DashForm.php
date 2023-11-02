<?php

namespace Drupal\module_schemesubscribe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a ibac support form.
 */
class DashForm extends FormBase {

  /**2
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_dashfrm_multistepform';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,$id = 0) {

	$url_id = base64_decode($id);
	$url_id = explode('#',$url_id);
	
	if(isset($url_id[0])){
		$node_id = base64_decode($url_id[0]);
		$node_entity = \Drupal::entityTypeManager();
		$messenger = \Drupal::service("messenger");
			
		$subscribers  = $node_entity->getStorage('node')->loadByProperties(['type' => 'scheme_subscribers','nid'=> $node_id]);
		if(!empty($subscribers)){
			 foreach ($subscribers as $s) {
						$node_id = $s->id();
						$field_verification = $s->field_verification->value;
						$state_id = $s->field_subscriber_state->target_id;
						$language_id = $s->field_subscriber_language->target_id;
						$subscriber_name = $s->getTitle();
						$subscriber_email = $s->field_subscriber_email->value;
				
						$state_scheme_id = 0;
						$central_scheme_id = 0;
						if(!empty($s->field_scheme_types)){
							foreach ($s->field_scheme_types as $item) {
								if($item->target_id==127){
									$central_scheme_id = $item->target_id;
								}else
								$state_scheme_id = $item->target_id;
							}
						}
						
						$scheme_types = [];
						if(!empty($s->field_schemes)){
							foreach ($s->field_schemes as $item) {
								$scheme_types[] = $item->target_id;
							}
						}
			 } //foreach
			 
			 if(isset($url_id[1])){
				 $verification_id = base64_decode($url_id[1]);
				 if($field_verification!==$verification_id){
						$messenger->addError(t('Invalid Access!!'));
						return $this->redirect('module_schemesubscribe.subscriberlogin');
				 } 
		     } //isset of verification code
			 
			 $session = \Drupal::service('session');
			  if (!$session->isStarted()) {
				$session->migrate();
			  }
			  $user_data['subscriber_id'] = $node_id;
			  $user_data['state_id'] = $state_id;
			  $user_data['language_id'] = $language_id;
			  $user_data['subscriber_name'] = $subscriber_name;
			  $user_data['subscriber_email'] = $subscriber_email;
	  
	  
			  if($central_scheme_id!=0){
				  $user_data['central_scheme_id'] = $central_scheme_id;
				  $user_data['scheme_id'][] = 'CS';
			  }
			  
			  if($state_scheme_id!=0){
				  $user_data['state_scheme_id'] = $state_scheme_id;
				  $user_data['scheme_id'][] = 'SS';
			  }
			  
			  $user_data['central_scheme_types'] = $scheme_types;
			  $user_data['state_scheme_types'] = $scheme_types;
			  
  
             
			  $tempstore = \Drupal::service('tempstore.private')->get('module_schemesubscribe');
			  $tempstore->set('user_data',$user_data);
			 
			 //unset verification
			 $node = Node::load($node_id);
		     $node->field_verification = NULL;
		     $node->save();
			 
			 return $this->redirect('module_schemesubscribe.subscriberlogin');

			
		}else{
			$messenger->addError(t('Invalid Access!!'));
			return $this->redirect('module_schemesubscribe.subscriberlogin');
		}
		
	}else{
		$messenger->addError(t('Invalid Access!!'));
		return $this->redirect('module_schemesubscribe.subscriberlogin');
	}
  }
  
  /**
   * {@inheritdoc}
   */
    public function submitForm(array &$form, FormStateInterface $form_state) {
		// nothing here
	}
}
