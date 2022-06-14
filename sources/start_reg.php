<?php
function PageMain() {
	global $TMPL, $LNG, $CONF, $db, $user, $settings, $plugins;

	if(isset($user['username'])) {
		header("Location: ".permalink($CONF['url']."/index.php?a=feed"));
	}

	// Start displaying the home-page users
	$result = $db->query("SELECT * FROM `users` WHERE `image` != 'default.png' ORDER BY `idu` DESC LIMIT 10 ");
	$users = [];
	while($row = $result->fetch_assoc()) {
		$users[] = $row;
	}
	
	$TMPL['rows'] = showUsers($users, $CONF['url']);
	
	$TMPL['url'] = $CONF['url'];
	$TMPL['title'] = $LNG['welcome'].' - '.$settings['title'];
	$TMPL['site_title'] = $settings['title'];
	$TMPL['agreement'] = sprintf($LNG['register_agreement'], permalink($settings['tos_url']), permalink($settings['privacy_url']));
	
	$TMPL['ad'] = $settings['ad1'];
	
	// Load the welcome plugins

    $TMPL['plugins'] = '';
	foreach($plugins as $plugin) {
		if(array_intersect(array("4"), str_split($plugin['type']))) {
			$data['site_url'] = $CONF['url']; $data['site_title'] = $settings['title']; $data['site_email'] = $CONF['email'];
			$TMPL['plugins'] .= plugin($plugin['name'], $data, 0);
		}
	}

	$TMPL['recover_url'] = permalink($CONF['url'].'/index.php?a=recover');
	$TMPL['register_url'] = permalink($CONF['url'].'/index.php?a=register');
	$TMPL['start_reg_url'] = permalink($CONF['url'].'/index.php?a=start_reg');
	$TMPL['questions_url'] = permalink($CONF['url'].'/index.php?a=questions');
	$TMPL['welcome_url'] = permalink($CONF['url'].'/index.php?a=welcome');
	$skin = new skin('register/start_reg');
	return $skin->make();
}
?>