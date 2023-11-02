<?php

namespace Drupal\module_schemesubscribe\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a ibac support form.
 */
class StateSchemeForm extends FormBase {

  /**2
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_state_schemes_listing_multistepform';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	\Drupal::service('page_cache_kill_switch')->trigger();
	$tempstore = \Drupal::service('tempstore.private')->get('module_schemesubscribe');
    $user_data = $tempstore->get('user_data');
	
	//checking session is set
	if(empty($user_data['scheme_id'])){
		return $this->redirect('module_schemesubscribe.states_subscription');
	}
	
	$form['customfield_1'] = array(
     '#type' => 'fieldset',
     '#title' => $this->t('Select State Schemes'),
	 '#required' => true,
    );
  
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $all_schemes = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'govt','status' => 1]);
	$tid = 0;
	$schemes = [];
	foreach ($all_schemes as $term) {
	  	if($term->hasTranslation($langcode)){
			$tid = $term->id();
			$translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $langcode);
			$schemes[$tid] = $translated_term->getName();
    	}
    }
	
	$check_central_govt = 'CENTRAL GOVT';
	$all_schemes = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'govt','name' => 'CENTRAL GOVT','status' =>1]);
	foreach ($all_schemes as $term) {
	  	if($term->hasTranslation($langcode)){
			$translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $langcode);
			$check_central_govt = $translated_term->getName();
    	}
    }
	 
   if (($key = array_search($check_central_govt, $schemes)) !== false) {
   		unset($schemes[$key]);
		$tid = key($schemes);
   }
   
  $form['customfield_1']['state_schemes'] = [
      '#type' => 'select',
	  '#title' => $this->t('State Schemes'),
      '#options' => $schemes,
      '#description_display' =>'before',
	  '#validated' => TRUE,
	  '#default_value' => $user_data['state_scheme_id'],
    ];
  
  $nodes = [];
  $form['customfield_1']['state_scheme_types'] = [
      '#type' => 'select',
      '#title' => $this->t('You can select maximum of 5 scheme types only from below:-'),
      '#options' => $nodes,
	  '#attributes' => ['size' =>[15]],
	  '#multiple' => TRUE,
	  '#default_value' => $user_data['state_scheme_types'],
	  '#validated' => TRUE,
    ];
	
	
	 $form['customfield_1']['langcode'] = [
      '#type' => 'hidden',
	  '#default_value' => $langcode,
    ];
	
 
     $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
     $url = '/subscription/';
     if($langcode!='en')
     $url = '/'.$langcode.'/subscription/';

     if(!empty($user_data['scheme_id']) && in_array("CS", $user_data['scheme_id']))
     $url = $url . 'centralschemes';
     else
     $url = $url . 'schemes';
 
	 $form['actions']['previous'] = [
        '#type' => 'item',
        '#markup' => '<a href="'. $url.'" class="btnNext align-left btn btn-primary">'.$this->t('Previous').'</a>',
    ];
	
	 $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#attributes' => ['class' => ['btnNext align-right']],
    ];
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
/**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
        $state_scheme_types = $form_state->getValue('state_scheme_types');
		if(empty($state_scheme_types)){
			$form_state->setErrorByName('name', $this->t('Please choose scheme types'));
		}
		if(sizeof($state_scheme_types) > 5){
			$session = \Drupal::service('session');
			if (!$session->isStarted()) {
			  $session->migrate();
			}
			$tempstore = \Drupal::service('tempstore.private')->get('module_schemesubscribe');
			//keeps old values
			$user_data = $tempstore->get('user_data');
			
			//update new values
			$state_scheme_types = $form_state->getValue('state_scheme_types');
			$schemes = array();
			foreach($state_scheme_types as $key=>$val){
				if($val!=0)$schemes[] = $key;
			}
			$user_data['state_scheme_types'] =  $schemes;
			
			$tempstore->set('user_data', $user_data);
			$user_data = $tempstore->get('user_data');
		
			$form_state->setErrorByName('name', $this->t('You can select upto 5 scheme types only'));
		}
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
			//keeps old values
			$user_data = $tempstore->get('user_data');
			
			//update new values
			$state_scheme_types = $form_state->getValue('state_scheme_types');
			$schemes = array();
			foreach($state_scheme_types as $key=>$val){
				if($val!=0)$schemes[] = $key;
			}
			
			$user_data['state_scheme_types'] =  $schemes;
			$user_data['state_scheme_id'] =  $form_state->getValue('state_schemes');
			
			$tempstore->set('user_data', $user_data);
			$user_data = $tempstore->get('user_data');
				
			$url = Url::fromRoute('module_schemesubscribe.schemes_selected');
			$form_state->setRedirectUrl($url);
	  }

}
