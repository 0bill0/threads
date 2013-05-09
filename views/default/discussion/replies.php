<?php
/**
 * List replies
 *
 * @uses $vars['entity']        ElggEntity
 * @uses $vars['id']
 */

/*  @Alteração 
  @Data:09-05-2013
	@Author:Guilherme Luiz Lessa
	@contato: http://resa.iff.edu.br/profile/guigui || http://github.com/0bill0
	Exibi os comentários feitos antes da ativação do plugin thread
	
	@Change
	@ Date :09-05-2013
	@ Author: Luiz Guilherme Lessa
	@ Contact: http://resa.iff.edu.br/profile/guigui | | http://github.com/0bill0
	View comments made before activating the plugin thread {
***																					***
*/
$show_add_form = elgg_extract('show_add_form', $vars, true);

echo '<div id="group-replies" class="mtl">';

$options = array(
	'guid' => $vars['entity']->getGUID(),
	'annotation_name' => 'group_topic_post',
);

$html = elgg_list_annotations($options);
if ($html) {
	echo '<h3>' . elgg_echo('group:replies') . '</h3>';
	echo $html;
}

echo '</div>';
/*  
	}
***																					***
*/

$id = $vars['id'] ? " id=\"{$vars['id']}\"" : "";
echo '<div'. $id .' class="mtl replies">';

$options = array(
	'relationship_guid' => $vars['entity']->getGUID(),
	'relationship' => 'parent',
	'inverse_relationship' => true,
	'order_by' => 'e.time_created asc'
);

echo elgg_list_entities_from_relationship($options);

echo '</div>';
