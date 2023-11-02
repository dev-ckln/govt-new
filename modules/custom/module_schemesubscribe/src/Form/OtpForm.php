<?php

namespace Drupal\module_schemesubscribe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides a ibac support form.
 */
class OtpForm extends FormBase {

  /**2
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_otpfrm_multistepform';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = 0) {
	\Drupal::service('page_cache_kill_switch')->trigger();
	
	$node_id = base64_decode($id);
	$node_entity = \Drupal::entityTypeManager();
	$messenger = \Drupal::service("messenger");
	
	$field_verification = '';
	if(is_numeric($node_id)){
		$nodeCollection  = $node_entity->getStorage('node')->loadByProperties(['type' => 'scheme_subscribers','nid'=> $node_id]);
		if(!empty($nodeCollection)){
			 foreach ($nodeCollection as $n) {
					$subscriber_otp_verified = $n->field_subscriber_otp_verified->value;
					$field_verification = $n->field_verification->value;
			 }
			 
			 if($subscriber_otp_verified==1){
				 \Drupal::service("messenger")->addError($this->t('Invalid Access!!'));
	    		 return $this->redirect('module_schemesubscribe.states_subscription');
			 }
				 
		}else{
			\Drupal::service("messenger")->addError($this->t('Invalid Access!!'));
	    	return $this->redirect('module_schemesubscribe.states_subscription');
		}
	}else{
		\Drupal::service("messenger")->addError($this->t('Invalid Access!!'));
	    return $this->redirect('module_schemesubscribe.states_subscription');
	}
	
	
	$form['customfield'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('Kindly verify your otp here'),
	'#required' => true,
  );
  
	$form['customfield']['otpcode'] = [
      '#type' => 'number',
      '#placeholder' => $this->t('OTP Code here'),
	  '#required' => true,
	  '#default_value' => isset($field_verification) ? $field_verification : '',
    ];
	
	$form['customfield']['node_id'] = [
      '#type' => 'hidden',
	  '#default_value' => isset($node_id) ? $node_id : 0,
    ];
	
	
	 $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Verify OTP'),
      '#attributes' => ['class' => ['btnNext align-right']]
    ];
     return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
	    $otpcode = $form_state->getValue('otpcode');
		if (mb_strlen($otpcode) != 6) {
		  $form_state->setErrorByName('name',t('Invalid OTP Code!'));
		}
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $id = 0) {
	$otpcode = $form_state->getValue('otpcode');
	$node_id = $form_state->getValue('node_id');
	
	$node_entity = \Drupal::entityTypeManager();
	if(is_numeric($node_id)){
		$nodeCollection  = $node_entity->getStorage('node')->loadByProperties(['type' => 'scheme_subscribers','nid'=> $node_id]);
		if(!empty($nodeCollection)){
			 foreach ($nodeCollection as $n) {
				    $subscriber_name = $n->getTitle();
					$subscriber_email = $n->field_subscriber_email->value;
					$field_verification = $n->field_verification->value;
					$field_subscriber_otp_verified = $n->field_subscriber_otp_verified->value;
					$field_subscriber_email_verified = $n->field_subscriber_email_verified->value;
					$state_id = $n->field_subscriber_state->target_id;
				    $language_id = $n->field_subscriber_language->target_id;
					
					$state_scheme_id = 0;
					$central_scheme_id = 0;
					if(!empty($n->field_scheme_types)){
						foreach ($n->field_scheme_types as $item) {
							if($item->target_id==127){
								$central_scheme_id = $item->target_id;
							}else
							$state_scheme_id = $item->target_id;
						}
					}
				
					$scheme_types = [];
					if(!empty($n->field_schemes)){
						foreach ($n->field_schemes as $item) {
							$scheme_types[] = $item->target_id;
						}
					}
			 }
			 
			 
			 if($field_subscriber_otp_verified==0 && $otpcode==$field_verification){
				  $node_storage = $node_entity->getStorage('node');
				  $node = $node_storage->load($node_id);
				  $node->field_subscriber_otp_verified = 1;
				  $node->field_verification = NULL;
				  $node->save();
				  
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
				  
				  
				  $info['subscriber_name'] = $subscriber_name;
				  $info['subscriber_email'] = $subscriber_email;
				  if(isset($info)){
		 		 	 $this->confirmationMail($info);
				  }
	
				  $this->messenger()->addStatus($this->t('Thank you %subscriber_name, you will get selected government scheme updates regularly',array('%subscriber_name' => $subscriber_name)));
				  $url = Url::fromRoute('module_schemesubscribe.subscribermodify');
				   $form_state->setRedirectUrl($url);
				 
			 }
			
			
			 if($subscriber_email_verified==1 && $subscriber_otp_verified==1){
				 $this->messenger()->addError($this->t('Invalid Access!!'));
				 $url = Url::fromRoute('module_schemesubscribe.states_subscription');
				 $form_state->setRedirectUrl($url);
			 }
				 
		}else{
			$this->messenger()->addError($this->t('Invalid Access!!'));
	    	$url = Url::fromRoute('module_schemesubscribe.states_subscription');
			$form_state->setRedirectUrl($url);
		}
	}else{
		$this->messenger()->addError($this->t('Invalid Access!!'));
	    $url = Url::fromRoute('module_schemesubscribe.states_subscription');
		$form_state->setRedirectUrl($url);
	}
  }
  
  
  function confirmationMail($user_data) {
	  $module = "module_schemesubscribe";
	  $key = "confirmation_mail";
	  $to =  $user_data['subscriber_email'];
	  $langcode = 'en';
	  $params['name'] = ucwords($user_data['subscriber_name']);
	  $msg = \Drupal::service('plugin.manager.mail')->mail($module, $key, $to, $langcode,$params);
  }

}
