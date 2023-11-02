<?php

namespace Drupal\module_schemesubscribe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

/**
 * Provides a ibac support form.
 */
class SchemeSelectedForm extends FormBase {

  /**2
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_scheme_selected_multistepform';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	\Drupal::service('page_cache_kill_switch')->trigger();
	
	$tempstore = \Drupal::service('tempstore.private')->get('module_schemesubscribe');
    $user_data = $tempstore->get('user_data');
	
	//checking session is set
	if(empty($user_data['central_scheme_id']) && empty($user_data['state_scheme_id'])){
		return $this->redirect('module_schemesubscribe.states_subscription');
	}
	
	
	$form['customfield'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('You have subscribed alerts for this schemes:'),
  );
	
	$langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
	
	$selected_central_schemes = '';
	if(isset($user_data['central_scheme_types'])){
		$nodeCollection  = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'scheme','nid'=> $user_data['central_scheme_types']]);
			foreach ($nodeCollection as $n) {
				 if($n->hasTranslation($langcode)){
					 $translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($n, $langcode);
					 $node_title = $translated_term->getTitle();
					 $selected_central_schemes .=  '<li>' . $node_title . '</li>';
				 }
			}
			
			
			$form['customfield']['form_title_1'] = [
			  '#type' => 'item',
			  '#title' => '<h5>' . $this->t('Central Schemes') . '</h5>',
			  '#markup' => '<ul>' . $selected_central_schemes . '</ul>'
			];
	}
	
	
	
	$selected_state_schemes = '';
	if(isset($user_data['state_scheme_types'])){
			$nodeCollection  = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'scheme','nid'=> $user_data['state_scheme_types']]);
			foreach ($nodeCollection as $n) {
				 if($n->hasTranslation($langcode)){
					  $translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($n, $langcode);
					  $node_title = $translated_term->getTitle();
				      $selected_state_schemes .= '<li>' . $node_title . '</li>';
				 }
			}
			
			
			
			if(isset($user_data['state_scheme_id'])){
			 $state_scheme = '';
			 $all_schemes = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'govt','tid' => $user_data['state_scheme_id']]);
				$schemes = [];
				foreach ($all_schemes as $term) {
					 if($term->hasTranslation($langcode)){
					  	$translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $langcode);
				   		$state_scheme = $translated_term->getName();
					 }
    			}
			}
	
			
			$scheme_title = '<h5>' . $this->t('State Schemes') .  ' - ' . $state_scheme . '</h5>';
			$form['customfield']['form_title_2'] = [
			  '#type' => 'item',
			  '#title' => $scheme_title,
			  '#markup' => '<ul>' . $selected_state_schemes . '</ul>'
			];
	}
	
	
	 $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
     $base_url = '/subscription/';
     if($langcode!='en')
     $base_url = '/'.$langcode.'/subscription/';

     $url = $base_url . 'schemes';
     if(!empty($user_data['scheme_id']) && in_array("CS", $user_data['scheme_id']))
     $url = $base_url . 'centralschemes';
	 
	 if(!empty($user_data['scheme_id']) && in_array("SS", $user_data['scheme_id']))
     $url = $base_url . 'stateschemes';
 
	 $form['actions']['previous'] = [
        '#type' => 'item',
        '#markup' => '<a href="'. $url.'" class="btnNext align-left btn btn-primary">'.$this->t('Previous').'</a>',
    ];
	
	 $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#attributes' => ['class' => ['btnNext align-right']]
    ];
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	$session = \Drupal::service('session');
    if (!$session->isStarted()) {
      $session->migrate();
    }
	$tempstore = \Drupal::service('tempstore.private')->get('module_schemesubscribe');
	$user_data = $tempstore->get('user_data');
	
	$url = Url::fromRoute('module_schemesubscribe.userfrm');
	if(isset($user_data['subscriber_id'])){
		
		 $node_id = isset($user_data['subscriber_id']) ? $user_data['subscriber_id'] : 0;
		 $state_id = isset($user_data['state_id']) ? $user_data['state_id'] : 0;
		 $language_id = isset($user_data['language_id']) ? $user_data['language_id'] : 0;
		 
		  $scheme_types = [];
		  if(isset($user_data['central_scheme_id'])){
			   $scheme_types[] = ['target_id' => $user_data['central_scheme_id'],'target_type' => 'taxonomy_term'];
		  }
	   
		  if(isset($user_data['state_scheme_id'])){
			   $scheme_types[] = ['target_id' => $user_data['state_scheme_id'],'target_type' => 'taxonomy_term'];
		  }
	   
	      $schemes = [];
		   if(!empty($user_data['central_scheme_types'])){
				foreach($user_data['central_scheme_types'] as $key=>$val){
					$schemes[] = ['target_id' => $val,'target_type' => 'node'];
				}
		   }
		   
		   if(!empty($user_data['state_scheme_types'])){
				foreach($user_data['state_scheme_types'] as $key=>$val){
					$schemes[] = ['target_id' => $val,'target_type' => 'node'];
				}
		   }
		 
		 
		 if(isset($user_data)){
			 $this->confirmationMail($user_data);
		 }
		 
		 if($state_id!==0 && $language_id!==0){
			 $node = Node::load($node_id);
			 $node->field_subscriber_state = ['target_id' => $state_id,'target_type' => 'taxonomy_term'];
			 $node->field_subscriber_language = ['target_id' => $language_id,'target_type' => 'taxonomy_term'];
			 $node->field_scheme_types =  $scheme_types;
			 $node->field_schemes =  $schemes;
			 $node->save();
		 }
				  
		 //$tempstore->delete('user_data');
		 $this->messenger()->addStatus($this->t('Schemes updated successfully!'));
		 $url = Url::fromRoute('module_schemesubscribe.subscribermodify');
	}
	
	$form_state->setRedirectUrl($url);
  }
  
  
  
  function confirmationMail($user_data) {
	  $module = "module_schemesubscribe";
	  $key = "confirmation_mail";
	  $to =  $user_data['subscriber_email'];
	  $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
	  $params['name'] = ucwords($user_data['subscriber_name']);
	  $msg = \Drupal::service('plugin.manager.mail')->mail($module, $key, $to, $langcode,$params);
  }

}
