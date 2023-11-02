<?php

namespace Drupal\module_schemesubscribe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a ibac support form.
 */
class StatesForm extends FormBase {

  /**2
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_states_languages_multistepform';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	\Drupal::service('page_cache_kill_switch')->trigger();
	
	$tempstore = \Drupal::service('tempstore.private')->get('module_schemesubscribe');
    $user_data = $tempstore->get('user_data');
	 
	$stree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('state_govt', 0, 1, TRUE);
	$states = [];
	foreach ($stree as $term) {
	  $states[$term->get('tid')->value] = $term->get('name')->value;
	}
	
	$languages = [];
	$site_languages = \Drupal::languageManager()->getLanguages();
	foreach ($site_languages as $languagecode => $language) {
	   $languages[$languagecode] = $language->getName();
	}
	
	
	$form['customfield'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('Please select your state and language:-'),
	'#required' => true,
  );
  
	$form['customfield']['subscription_states'] = [
      '#type' => 'select',
	  '#title' => $this->t('States'),
      '#options' => $states,
      '#description_display' =>'before',
	  '#default_value' => isset($user_data['state_id']) ? $user_data['state_id'] : '',
    ];
	
	$form['customfield']['subscription_languages'] = [
      '#type' => 'select',
	  '#title' => $this->t('Languages'),
      '#options' => $languages,
	  '#default_value' => isset($user_data['language_id']) ? $user_data['language_id'] : '',
    ];
	
	if(isset($user_data['subscriber_id'])){
		 $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
                
		 $url = '/subscription/modifyschemes';
		 if($langcode!='en')
		 $url = '/'.$langcode.'/subscription/modifyschemes';
		 $form['actions']['previous'] = [
			  '#type' => 'item',
			  '#markup' => '<a href="'.$url.'" class="btnNext align-left btn btn-primary">'.$this->t('Previous').'</a>',
		  ];
	}
	
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
 /* public function validateForm(array &$form, FormStateInterface $form_state) {

  }*/

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
	$user_data['state_id'] = $form_state->getValue('subscription_states');
	$user_data['language_id'] = $form_state->getValue('subscription_languages');
    $tempstore->set('user_data', $user_data);
	
	$langcode = isset($user_data['language_id']) ? $user_data['language_id'] : 'en';
	$language_manager = \Drupal::service('language_manager');
	$url = Url::fromRoute( 'module_schemesubscribe.schemes_subscription', [], ['language' => $language_manager->getLanguage($langcode)]);
	$form_state->setRedirectUrl($url);
  }

}
