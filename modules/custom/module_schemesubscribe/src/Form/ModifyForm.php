<?php

namespace Drupal\module_schemesubscribe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides a ibac support form.
 */
class ModifyForm extends FormBase {

  /**2
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_modifyfrm_multistepform';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	 \Drupal::service('page_cache_kill_switch')->trigger();
								
	$tempstore = \Drupal::service('tempstore.private')->get('module_schemesubscribe');
    $user_data = $tempstore->get('user_data');
	
	//checking session is set
	if(empty($user_data['subscriber_id'])){
		return $this->redirect('module_schemesubscribe.subscriberlogin');
	}
	
	$form['customfield'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('You have subscribed alerts for this schemes:'),
  );
  
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
	
	$selected_central_schemes = '';
	$schemes_for_central = [];
	if(isset($user_data['central_scheme_id']) && !empty($user_data['central_scheme_types'])){
		$nodeCollection  = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'scheme','nid'=> $user_data['central_scheme_types'],'field_govt' => 127]);
			foreach ($nodeCollection as $n) {
				 if($n->hasTranslation($langcode)){
					$translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($n, $langcode);
					$schemes_for_central[] = $n->id();
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
	if(isset($user_data['state_scheme_id']) && !empty($user_data['state_scheme_types'])){
			if(!empty($schemes_for_central)){
				$user_data['state_scheme_types'] = array_diff($user_data['state_scheme_types'],$schemes_for_central);
			}
		
		   
			$nodeCollection  = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'scheme','nid'=> $user_data['state_scheme_types']]);
				foreach ($nodeCollection as $n) {
					if($n->hasTranslation($langcode)){
						$translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($n, $langcode);
						$node_title = $translated_term->getTitle();
						$selected_state_schemes .= '<li>' . $node_title . '</li>';
					}
				}
			
			
			 $state_scheme = '';
			 $all_schemes = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'govt','tid' => $user_data['state_scheme_id']]);
				$schemes = [];
				foreach ($all_schemes as $term) {
					if($term->hasTranslation($langcode)){
						$translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $langcode);
				   		$state_scheme = $translated_term->getName();
					}
    			}
				
				$scheme_title = '<h5>' . $this->t('State Schemes') .  ' - ' . $state_scheme . '</h5>';
				$form['customfield']['form_title_2'] = [
				  '#type' => 'item',
				  '#title' => $scheme_title,
				  '#markup' => '<ul>' . $selected_state_schemes . '</ul>'
				];
			
	}
		
	
	$form['customfield']['previous'] = [
      '#type' => 'submit',
	  '#name' => 'scheme',
      '#value' => $this->t('Modify Schemes'),
      '#attributes' => ['class' => ['btnNext align-left']]
    ];
	
	/*$form['customfield']['next'] = [
      '#type' => 'submit',
	  '#name' => 'logout',
      '#value' => $this->t('Logout'),
      '#attributes' => ['class' => ['btnNext align-right']]
    ];*/
     return $form;
  }

  /**
   * {@inheritdoc}
   */
    public function submitForm(array &$form, FormStateInterface $form_state) {
		 if($form_state->getTriggeringElement()['#name']=='scheme'){
			  $module = 'module_schemesubscribe.states_subscription';
			  $url = Url::fromRoute($module);
			  $form_state->setRedirectUrl($url);
		 }else{
			  $session = \Drupal::service('session');
			  if (!$session->isStarted()) {
				$session->migrate();
			  }
			  $tempstore = \Drupal::service('tempstore.private')->get('module_schemesubscribe');
			  $tempstore->delete('user_data');
	          
			  $this->messenger()->addStatus($this->t('You have logout successfully!'));
			  
			  $module = 'module_schemesubscribe.subscriberlogin';
			  $url = Url::fromRoute($module);
			  $form_state->setRedirectUrl($url);
		 }
    }

}
