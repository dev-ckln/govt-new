<?php

namespace Drupal\module_schemesubscribe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

/**
 * Provides a ibac support form.
 */
class CentralSchemeForm extends FormBase {

  /**2
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_central_schemes_listing_multistepform';
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
    '#title' => $this->t('Select Central Schemes'),
	'#required' => true,
  );
  
  
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $all_schemes = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'govt', 'name' => 'CENTRAL GOVT','status' => 1]);
	$tid = 0;
	$schemes = [];
	foreach ($all_schemes as $term) {
	  if($term->hasTranslation($langcode)){
			$translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $langcode);
			$tid = $term->get('tid')->value;
			$schemes[$term->get('tid')->value] = $translated_term->getName();
	  }
  }

  $form['customfield_1']['central_schemes'] = [
      '#type' => 'select',
	  '#title' => $this->t('Central Schemes'),
      '#options' => $schemes,
      '#description_display' =>'before',
    ];
	
	
	$nodeCollection  = \Drupal::entityTypeManager()->getStorage('node')
	  ->loadByProperties(['type' => 'scheme','field_govt'=> $tid, 'status' => 1]);
	  
	 $nodes = [];
	 foreach ($nodeCollection as $n) {
		  if($n->hasTranslation($langcode)){
			    $translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($n, $langcode);
				$nodes[$n->id()] = $translated_term->getTitle();
		  }
	  }
	  
  
  $form['customfield_1']['central_scheme_types'] = [
      '#type' => 'select',
      '#title' => $this->t('You can select maximum of 5 scheme types only from below:-'),
      '#options' => $nodes,
	  '#attributes' => ['size' =>[15]],
	  '#multiple' => TRUE,
	  '#default_value' => $user_data['central_scheme_types'],
    ];
	
	
	$langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $url = '/subscription/schemes';
        if($langcode!='en')
        $url = '/'.$langcode.'/subscription/schemes';
		
	  $form['actions']['previous'] = [
        '#type' => 'item',
        '#markup' => '<a href="'.$url.'" class="btnNext align-left btn btn-primary">'.$this->t('Previous').'</a>',
    ];
	
	 $form['actions']['next'] = [
      '#type' => 'submit',
	  '#name' => 'twosub',
      '#value' => $this->t('Next'),
      '#attributes' => ['class' => ['btnNext align-right']],
    ];
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
        $central_scheme_types = $form_state->getValue('central_scheme_types');
		if(empty($central_scheme_types)){
			$form_state->setErrorByName('name', $this->t('Please choose scheme types'));
		}
		if(sizeof($central_scheme_types) > 5){
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
			$central_scheme_types = $form_state->getValue('central_scheme_types');
			$schemes = array();
			foreach($central_scheme_types as $key=>$val){
				if($val!=0)$schemes[] = $key;
			}
			$user_data['central_scheme_id'] =  $form_state->getValue('central_schemes');
			$user_data['central_scheme_types'] =  $schemes;
			
			$tempstore->set('user_data', $user_data);
			$user_data = $tempstore->get('user_data');
				
			$module = 'module_schemesubscribe.schemes_selected';
			if(!empty($user_data['scheme_id']) && in_array("SS", $user_data['scheme_id']))
			$module = 'module_schemesubscribe.state_schemes_listing_subscription';
		
			$url = Url::fromRoute($module);
			$form_state->setRedirectUrl($url);
	  
	  }

}
