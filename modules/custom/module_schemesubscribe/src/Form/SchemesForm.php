<?php

namespace Drupal\module_schemesubscribe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a ibac support form.
 */
class SchemesForm extends FormBase {

  /**2
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_schemes_multistepform';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	\Drupal::service('page_cache_kill_switch')->trigger();
	
	$tempstore = \Drupal::service('tempstore.private')->get('module_schemesubscribe');
    $user_data = $tempstore->get('user_data');
	
	//checking session is set
	if(empty($user_data['state_id'])){
		return $this->redirect('module_schemesubscribe.states_subscription');
	}
	
	$form['schemes_multistepform'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Which scheme do you want to subscribe?'),
      '#options' => [
        'CS' => $this->t('Central Schemes'), 
        'SS' => $this->t('State Schemes'),
      ],
      '#required' => true,
	  '#default_value' => $user_data['scheme_id'],
    ];
 
 
   $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
   $url = '/subscription/states';
   if($langcode!='en')
   $url = '/'.$langcode.'/subscription/states';
   $form['actions']['previous'] = [
        '#type' => 'item',
        '#markup' => '<a href="'.$url.'" class="btnNext align-left btn btn-primary">'.$this->t('Previous').'</a>',
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
			//keeps old values
			$user_data = $tempstore->get('user_data');
			
			//update new values
			$schemes = $form_state->getValue('schemes_multistepform');

			$scheme_id = array();
  
			foreach($schemes as $key=>$val){
                             if($val!==0)$scheme_id[] = $key;
			}
                       
                        
			$user_data['scheme_id'] =  $scheme_id;
			
			//unset session of previous scheme types
			if(!empty($user_data['scheme_id']) && !in_array("CS", $user_data['scheme_id'])){
				unset($user_data['central_scheme_id']);
				unset($user_data['central_scheme_types']);
			}
			if(!empty($user_data['scheme_id']) && !in_array("SS", $user_data['scheme_id'])){
				unset($user_data['state_scheme_id']);
				unset($user_data['state_scheme_types']);
			}
			
			$tempstore->set('user_data', $user_data);
			$user_data = $tempstore->get('user_data');
			
			$module = 'module_schemesubscribe.state_schemes_listing_subscription';
			if(!empty($user_data['scheme_id']) && in_array("CS", $user_data['scheme_id']))
			$module = 'module_schemesubscribe.central_schemes_listing_subscription';
		
			$url = Url::fromRoute($module);
			$form_state->setRedirectUrl($url);
		
  }

}
