<?php

namespace Drupal\module_schemesubscribe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a ibac support form.
 */
class UserForm extends FormBase {

  /**2
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_userfrm_multistepform';
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
    '#title' => $this->t('Kindly Enter your Email /Whatspp number'),
	'#required' => true,
  );
  
	$form['customfield']['phone_number'] = [
      '#type' => 'number',
	  '#title' => $this->t('For whatsapp alerts only <span class="info-text"> [Please do not enter country code!]</span>'),
      '#placeholder' => $this->t('Phone Number'),
	  '#required' => true,
	  '#default_value' => isset($user_data['subscriber_phone_number']) ? $user_data['subscriber_phone_number'] : '',
    ];
	
			
	$form['customfield']['email'] = [
      '#type' => 'email',
	  '#title' => $this->t('Your email'),
      '#placeholder' => $this->t('Email'),
	  '#required' => true,
	  '#default_value' => isset($user_data['subscriber_email']) ? $user_data['subscriber_email'] : '',
    ];
	
	$form['customfield']['subscriber_name'] = [
      '#type' => 'textfield',
	  '#title' => $this->t('Your Name'),
      '#placeholder' => $this->t('Name'),
	  '#required' => true,
	  '#default_value' => isset($user_data['subscriber_name']) ? $user_data['subscriber_name'] : '',
    ];
	
	 $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Verify Email'),
      '#attributes' => ['class' => ['btnNext align-right']]
    ];
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
	    $phone_number = $form_state->getValue('phone_number');
		if (mb_strlen($phone_number) != 10) {
		  $form_state->setErrorByName('name',t('The phone number %phone is not valid.', array('%phone' => $phone_number)));
		}
	
		$email = $form_state->getValue('email');
		if (!\Drupal::service('email.validator')->isValid($email)) {
		  $form_state->setErrorByName('email',t('The email address %mail is not valid.', array('%mail' => $email)));
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
	$email = $form_state->getValue('email');
	$subscriber_name = $form_state->getValue('subscriber_name');
	$phone_number = $form_state->getValue('phone_number');
	$user_data['subscriber_phone_number'] = $phone_number;
	$user_data['subscriber_email'] = $email;
	$user_data['subscriber_name'] = $subscriber_name;
    $tempstore->set('user_data', $user_data);
	
	
	$node_entity = \Drupal::entityTypeManager();
	$subscribers = $node_entity->getStorage('node')->loadByProperties(['type' => 'scheme_subscribers','field_subscriber_email'=> $email]);
	if(empty($subscribers)) {
		
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
	   
	   $verification_code = rand(100000,999999);
	   $node = $node_entity->getStorage('node')->create(
       array(
          'type' => 'scheme_subscribers',
          'field_subscriber_email' => $email,
		  'title' => $subscriber_name,
		  'field_subscriber_phone' => $phone_number,
		  'field_subscriber_state' => ['target_id' => $state_id,'target_type' => 'taxonomy_term'],
		  'field_subscriber_language' => ['target_id' => $language_id,'target_type' => 'taxonomy_term'],
		  'field_scheme_types' => $scheme_types,
		  'field_schemes' => $schemes,
		  'field_verification' => $verification_code,
		  'published' => 0,
		  'status' => 0
        ));
		
		$status = $node->save();
		$node_id = $node->id();
		
		$host = \Drupal::request()->getSchemeAndHttpHost();
		$langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

		$url = '/subscription/activation/';
		if($langcode!='en')
        	$url = '/'.$langcode.'/subscription/activation/';
		$activation_email_url = $host . $url .base64_encode($node_id) ;
		
		
		$params['name'] = ucwords($subscriber_name);
		$params['subscriber_email'] = $email;
		$params['activation_link'] = $activation_email_url;
		$params['langcode'] = $langcode;
		$result = $this->emailActivateMail($params);
		
		// remove session data
		$tempstore->delete('user_data');
		
		$url = Url::fromRoute('module_schemesubscribe.success');
		$form_state->setRedirectUrl($url);
		
		$this->messenger()->addStatus($this->t('An email has been sent to %mail,please verify it!',array('%mail' => $email)));
	}else{
		$this->messenger()->addError($this->t('This email %mail is already exist!!',array('%mail' => $email)));
	}
	
  }
  
  function emailActivateMail($params) {
	  $module = "module_schemesubscribe";
	  $key = "email_verification_mail";
	  $to =  $params['subscriber_email'];
	  $langcode = $params['langcode'];
	  $msg = \Drupal::service('plugin.manager.mail')->mail($module, $key, $to, $langcode,$params);
  }

}
