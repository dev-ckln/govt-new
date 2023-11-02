<?php

namespace Drupal\module_schemesubscribe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides a ibac support form.
 */
class LoginForm extends FormBase {

  /**2
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_loginfrm_multistepform';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	\Drupal::service('page_cache_kill_switch')->trigger();
	 
	$tempstore = \Drupal::service('tempstore.private')->get('module_schemesubscribe');
    $user_data = $tempstore->get('user_data');
	
	//checking session is set
	if(isset($user_data['subscriber_id'])){
		return $this->redirect('module_schemesubscribe.subscribermodify');
	}
		
	$form['customfield'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('Please enter your email:-'),
	'#required' => true,
  );
  
	$form['customfield']['email'] = [
      '#type' => 'email',
      '#placeholder' => $this->t('Email'),
	  '#required' => true,
    ];
	
	 $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Login'),
      '#attributes' => ['class' => ['btnNext align-right']]
    ];
     return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
	    $email = $form_state->getValue('email');
		if (!\Drupal::service('email.validator')->isValid($email)) {
		  $form_state->setErrorByName('email',t('The email address %mail is not valid.', array('%mail' => $email)));
		}
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	  $email = $form_state->getValue('email');
	  $node_entity = \Drupal::entityTypeManager();
	  $subscribers = $node_entity->getStorage('node')->loadByProperties(['type' => 'scheme_subscribers','field_subscriber_email'=> $email,'field_subscriber_email_verified'=> 1,'field_subscriber_otp_verified'=> 1]);
	  if(!empty($subscribers)) {
			foreach ($subscribers as $s) {
				$node_id = $s->id();
				$language_id = $s->field_subscriber_language->target_id;
				$subscriber_name = $s->getTitle();
				$subscriber_email = $s->field_subscriber_email->value;
			}
			
		    $verification_code = rand(100000,999999);
		    $node = Node::load($node_id);
		    $node->field_verification =  $verification_code;
		    $node->save();
			
			$url_id = base64_encode($node_id) . '#' .  base64_encode($verification_code) ;
			$url_id = base64_encode($url_id);
			
	
			$host = \Drupal::request()->getSchemeAndHttpHost();
           	$url = '/subscription/dashboard/' . $url_id;
			if($language_id!='en')
            $url = '/'.$language_id.'/subscription/dashboard/' . $url_id;
			$access_url = $host . $url;
			
			$params['name'] = ucwords($subscriber_name);
			$params['subscriber_email'] = $subscriber_email;
			$params['langcode'] = $language_id;
			$params['activation_link'] = $access_url;
			$this->emailActivateMail($params);
			
		   $this->messenger()->addStatus($this->t('An email has been send to your email id!'));
	  }else{
		  $this->messenger()->addError($this->t('Invalid Access!!!'));
	  }
 }
 
  function emailActivateMail($params) {
	  $module = "module_schemesubscribe";
	  $key = "subscriber_login_mail";
	  $to = $params['subscriber_email'];
	  $langcode = $params['langcode'];
	  $msg = \Drupal::service('plugin.manager.mail')->mail($module, $key, $to, $langcode,$params);
  }

}
