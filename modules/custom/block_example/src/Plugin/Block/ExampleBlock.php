<?php

namespace Drupal\block_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a block with simple text
 *
 * @Block(
 *
 * id = "matching_schemes_sector",
 * admin_label = @Translation("Matching Schemes")
 * )
 */
class ExampleBlock extends BlockBase {

    private static function getTermName($tid) {
        $term = Term::load($tid);
        return $term->getName();
    }

    private function getNodeSchemeStateArr($node) {
        $field_name1 = 'field_govt';
        $field_name2 = 'field_scheme_type';

        $result = array();

        if ($node && $node->hasField($field_name1) && $node->hasField($field_name2)) {

            $tids = $node->get($field_name1)->getValue();

	    if(is_array($tids)){

		    $state_tid = $tids[0]['target_id'];

		    $tids = $node->get($field_name2)->getValue();

		    for ($i = 0; $i < count($tids); ++$i) {
			    $result [] = "" . $tids[$i]['target_id'] . ":" . $state_tid;
		    }
	    }

        }

        return $result;
    }

    public function build() {


        $messenger = \Drupal::messenger();
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

        $node = \Drupal::routeMatch()->getParameter('node');
        if (!($node instanceof \Drupal\node\NodeInterface)) {
            return;
        }
        //make scheme type:state of this node
        $node_scheme_state_arr = $this->getNodeSchemeStateArr($node);  //in format 23:59
        //dpm($node_scheme_state_arr,'node_scheme_state_arr');
        $state_tid = $this->getStateTid();


        $scheme_type_tids = $this->getSchemeTids();


        $result_arr = $this->getFilteredNodes($state_tid, $scheme_type_tids);  //return will be indexed by scheme type

        $html = array();

        //dpm($scheme_type_tids, 'schemetypetids');
        //dpm($result_arr, 'result_arr=');

        foreach ($scheme_type_tids as $tidval) {
            //prepare all state schemes
            for ($i = 0; $i < count($result_arr); ++$i) {
                $obj = $result_arr[$i];
                if ($obj->state_tid == '' || $node->id() == $obj->nid) {
                    continue;
                }

                //dpm($obj, 'objj');
		if($obj->scheme_type_tid_arr)
                if (in_array($tidval, $obj->scheme_type_tid_arr)) {  //if scheme type is same in current and $i node
                    $index = "$tidval:" . $obj->state_tid;

                    if (!array_key_exists($index, $html)) {
                        $html[$index] = array();
                        //dpm($index, "Created index");
                    }

                    $img = '';

                    if ($obj->leader_url) {
                        $img_render_array = ['#theme' => 'image_style', '#style_name' => 'small', '#uri' => $obj->leader_url,];
                     
                        $img = render($img_render_array)->__toString();
                    }

                    $val = "<a href='/node/" . $obj->nid . "'>" . $obj->title . "</a>";
                    $html[$index] [] = [$val, $img];
                }
            }
        }


        //dpm($html, "html=");
        //prepare all central schemes

        $totalhtml = '';
        //this will collect schemes in case of matching govt(ex Central=Central   or Delhi=Delhi)
        foreach ($node_scheme_state_arr as $value) {
            if (array_key_exists($value, $html)) {
                //dpm($value,'value');
                $markup = "<div  class='matchscm'  id='siblk$value'><h2>" . $this->t('Matching schemes for sector') .
                        ':' . ExampleBlock::getTermName(explode(':', $value)[0]) . '</h2><table><tr><th>Sno</th><th>CM</th><th>Scheme</th><th>Govt</th></tr>';
                $sno = 0;
                foreach ($html[$value] as $elm) {
                 
                    ++$sno;
                    $state = ExampleBlock::getTermName(explode(':', $value)[1]);
                    $markup = $markup . "<tr><td>$sno</td><td>".$elm[1]."</td><td>$elm[0]</td><td>$state</td></tr>";
                }
                $markup = $markup . "</table></div>";
                $totalhtml = $totalhtml . $markup;
            }
        }
        //dpm($totalhtml, '$totalhtml=');
        //in case current state is state govt(Delhi) then we can as well show Central schemes too   
        //get state tid

	if($node->get("field_govt")!=null && $node->get("field_govt")->getValue()!=null &&is_array($node->get("field_govt")->getValue())){
		$state_tid = $node->get("field_govt")->getValue()[0]['target_id'];
		if ($state_tid != 127) {//not center
			foreach ($node_scheme_state_arr as $value) {
				$type = explode(':', $value)[0];
				$state = 127;
				$value = "$type:$state";
				//dpm($value, 'value=');
				//now select schemes with center as the state
				if (array_key_exists($value, $html)) {
					$markup = "<div class='matchscm' id='siblk$value'><h2>" . $this->t('Matching schemes for sector') .
						':' . ExampleBlock::getTermName(explode(':', $value)[0]) . '</h2><table><tr><th>Sno</th><th>CM</th><th>Scheme</th><th>Govt</th></tr>';
					$sno = 0;
					foreach ($html[$value] as $elm) {
						++$sno;
						$state = ExampleBlock::getTermName(explode(':', $value)[1]);
						$markup = $markup . "<tr><td>$sno</td><td>".$elm[1]."</td><td>$elm[0]</td><td>$state</td></tr>";
					}
				}
				$markup = $markup . "</table></div>";

				$totalhtml = $totalhtml . $markup;
			}
		}
	}

        return [
            '#children' => $totalhtml,
        ];
        return [
            '#type' => 'inline_template',
            '#template' => '{{ somecontent }}',
            '#context' => [
                'somecontent' => $markup
            ]
        ];

        return [
            '#markup' =>
            $markup,
        ];
    }

     protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

    /* return all scheme nodes data */

    private function getFilteredNodes($scheme_type_tids, $state_tid) {
        $CENTER_TID = 127;
        $nids = \Drupal::entityQuery('node')
                ->condition('type', 'scheme')
                //	->addMetaData('account', \Drupal\user\Entity\User::load(1))
                ->execute();

        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

        $result_arr = array();

        foreach ($nids as $nid) {

            $node = \Drupal\node\Entity\Node::load($nid);

            if (!$node) {
                continue;
            }

            $field_name1 = 'field_govt';
            $field_name2 = 'field_scheme_type';

            if ($node && $node->hasField($field_name1) && $node->hasField($field_name2)) {

                $res_data = new AnuFilteredData();

                $tids = $node->get($field_name1)->getValue();

                if ($tids == null) {
                    continue;
                }

                $res_data->nid = $nid;
                $res_data->title = $node->getTitle();
                $res_data->state_tid = $tids[0]['target_id'];
                $res_data->leader_url = $this->getLeaderUrl($res_data->state_tid);

                if (0 == strlen($res_data->state_tid)) {
                    continue;
                };

                $tids = $node->get($field_name2)->getValue();

                for ($i = 0; $i < count($tids); ++$i) {
                    $res_data->scheme_type_tid_arr[] = $tids[$i]['target_id'];
                }

                $result_arr[] = $res_data;
            }
        }
        //dpm($result_arr,'resultarr');
        return $result_arr;
    }

    private function getLeaderUrl($tid) {

        $term_obj = \Drupal\taxonomy\Entity\Term::load($tid);
        $image_uri = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid)->get('field_leader')->entity->uri->value;

        return $image_uri;
    }

    private function getStateTid() {
        $node = \Drupal::routeMatch()->getParameter('node');
        if (!($node instanceof \Drupal\node\NodeInterface)) {
            return null;
        }

        $field_name = 'field_govt';

	$tids_res = null;

        if ($node && $node->hasField($field_name)) {

            $tids = $node->get($field_name)->getValue();

            for ($i = 0; $i < count($tids); ++$i) {
                return $tids[$i]['target_id'];
            }

            return $tids_res;
        }
        return null;
    }

    public function getSchemeTids() {
        $node = \Drupal::routeMatch()->getParameter('node');
        if (!($node instanceof \Drupal\node\NodeInterface)) {
            return null;
        }

        $tids_res = array();

        $field_name = 'field_scheme_type';

        if ($node && $node->hasField($field_name)) {

            $tids = $node->get($field_name)->getValue();

            for ($i = 0; $i < count($tids); ++$i) {
                $tids_res [] = $tids[$i]['target_id'];
            }

            return $tids_res;
        }
        return null;
    }

    public function getCacheMaxAge() {
        return 0;
    }

}

class AnuFilteredData {

    public function __construct() {
        $scheme_type_tid_arr = array();
    }

    public $nid, $title,
            $state_tid, //whether center or state scheme
            $scheme_type_tid_arr;

}

