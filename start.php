<?php
/**
 * Elgg threaded discussions plugin
 *
 * @package ElggThreads
 */

elgg_register_event_handler('init', 'system', 'threads_init');

/**
 * Initialize the threads plugin
 */
function threads_init() {

	elgg_register_library('elgg:discussion', elgg_get_plugins_path() . 'threads/lib/discussion.php');
	elgg_register_library('elgg:threads', elgg_get_plugins_path() . 'threads/lib/threads.php');

	elgg_register_page_handler('discussion', 'threads_page_handler');
	
	$action_base = elgg_get_plugins_path() . 'threads/actions/discussion';
	elgg_register_action('discussion/reply/save', "$action_base/reply/save.php");
	elgg_register_action('discussion/reply/delete', "$action_base/reply/delete.php");

	// add links to reply, edit or delete replies.
	elgg_register_plugin_hook_handler('register', 'menu:reply', 'threads_reply_menu_setup');
	
	// add link to reply topics
	elgg_register_plugin_hook_handler('register', 'menu:entity', 'threads_topic_menu_setup');
	elgg_register_plugin_hook_handler('register', 'menu:reply', 'likes_entity_menu_setup');
	elgg_register_plugin_hook_handler('register', 'menu:river', 'threads_add_to_river_menu');
	
	elgg_extend_view('css/elgg', 'discussion/css');
	
	$threads_js = elgg_get_simplecache_url('js', 'threads');
	elgg_register_js('elgg.threads', $threads_js);
	
	$parsequery_js = 'mod/threads/vendors/jquery-plugins/parsequery.js';
	elgg_register_js('jquery.plugins.parsequery', $parsequery_js);
}

/**
 * Discussion page handler
 *
 * URLs take the form of
 *  All topics in site:    discussion/all
 *  List topics in forum:  discussion/owner/<guid>
 *  View discussion topic: discussion/view/<guid>
 *  Add discussion topic:  discussion/add/<guid>
 *  Edit discussion topic: discussion/edit/<guid>
 *
 * @param array $page Array of url segments for routing
 */
function threads_page_handler($page) {

	elgg_load_library('elgg:discussion');

	elgg_push_breadcrumb(elgg_echo('discussion'), 'discussion/all');

	switch ($page[0]) {
		case 'all':
			discussion_handle_all_page();
			break;
		case 'owner':
			discussion_handle_list_page($page[1]);
			break;
		case 'add':
			discussion_handle_edit_page('add', $page[1]);
			break;
		case 'edit':
			discussion_handle_edit_page('edit', $page[1]);
			break;
		case 'view':
			discussion_handle_view_page($page[1]);
			break;
	}
}

/**
 * Add the reply button for the river
 */
function threads_add_to_river_menu($hook, $type, $return, $params) {
	if (elgg_is_logged_in() && !elgg_in_context('widgets')) {
		$item = $params['item'];
		$object = $item->getObjectEntity();
		$reply = get_entity($item->annotation_id);
		if (elgg_instanceof($object, 'object', 'groupforumtopic')) {
			if ($reply) {
				$group = $object->getContainerEntity();
				if ($group->canWriteToContainer() || elgg_is_admin_logged_in()) {
					$options = array(
						'name' => 'reply',
						'href' => "#groups-reply-$reply->guid",
						'text' => elgg_view_icon('speech-bubble'),
						'title' => elgg_echo('reply:this'),
						'rel' => 'toggle',
						'priority' => 50,
					);
					$return[] = ElggMenuItem::factory($options);
				}
			}
		}
	}

	return $return;
}

function threads_topic_menu_setup($hook, $type, $return, $params){
	
	$entity = $params['entity'];
	
	elgg_load_library('elgg:threads');
	
	$group = $entity->getContainerEntity();
	$topic = threads_top($entity->guid);
	
	if(($group && $group->canWriteToContainer() ||
		elgg_is_admin_logged_in()) && $topic->status != 'closed'){
		$url = elgg_http_add_url_query_elements($topic->getURL(), array(
			'box' => 'reply',
			'guid' => $entity->guid,
		));

		$options = array(
			'name' => 'reply',
			'href' => $url,
			'text' => elgg_echo('reply'),
			'text_encode' => false,
			'priority' => 200
		);
		$return[] = ElggMenuItem::factory($options);
	}
	return $return;
}

/**
 * Add edit and delete links for forum replies
 */
function threads_reply_menu_setup($hook, $type, $return, $params) {
	if (elgg_in_context('widgets')) {
		return $return;
	}
	
	$entity = $params['entity'];

	if ($entity->type != 'object' || $entity->getSubtype() != 'topicreply') {
		return $return;
	}
	
	elgg_load_library('elgg:threads');

	if ($entity->canEdit()) {
		$url = elgg_http_add_url_query_elements('action/discussion/reply/delete', array(
			'entity_guid' => $entity->guid,
		));

		$options = array(
			'name' => 'delete',
			'href' => $url,
			'text' => "<span class=\"elgg-icon elgg-icon-delete\"></span>",
			'confirm' => elgg_echo('deleteconfirm'),
			'text_encode' => false,
			'priority' => 500
		);
		$return[] = ElggMenuItem::factory($options);

		$url = elgg_http_add_url_query_elements('', array(
			'box' => 'edit',
			'guid' => $entity->guid,
		));

		$options = array(
			'name' => 'edit',
			'href' => $url,
			'text' => elgg_echo('edit'),
			'text_encode' => false,
			'priority' => 100,
		);
		$return[] = ElggMenuItem::factory($options);
	}
	
	$group = $entity->getContainerEntity();
	$topic = threads_top($entity);
	
	if(($group && $group->canWriteToContainer() ||
		elgg_is_admin_logged_in()) && $topic->status != 'closed'){

		$url = elgg_http_add_url_query_elements('', array(
			'box' => 'reply',
			'guid' => $entity->guid,
		));

		$options = array(
			'name' => 'reply',
			'href' => $url,
			'text' => elgg_echo('reply'),
			'text_encode' => false,
			'priority' => 50,
		);
		$return[] = ElggMenuItem::factory($options);
	}

	return $return;
}
