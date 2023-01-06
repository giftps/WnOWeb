<?php
error_reporting(0);

$CONF = $TMPL = array();

// The MySQL credentials
$CONF['host'] = 'localhost';
$CONF['user'] = 'root';
$CONF['pass'] = '';
$CONF['name'] = 'wno';

// The Installation URL
$CONF['url'] = 'http://localhost/widenout/web'; // Dev


// Remote The MySQL credentials
// $CONF['host'] = 'sql6.freemysqlhosting.net';
// $CONF['user'] = 'sql6499833';
// $CONF['pass'] = 'dq63BdB3Lh';
// $CONF['name'] = 'sql6499833';

// // The Installation URL
// $CONF['url'] = 'https://widenout.herokuapp.com'; // Prod - Heroku

// The Notifications e-mail
$CONF['email'] = 'email@widenout.com';

// The themes directory
$CONF['theme_path'] = 'themes';

// The plugins directory
$CONF['plugin_path'] = 'plugins';

$action = array('admin'			=> 'admin',
				'feed'			=> 'feed',
				'tcs'			=> 'tcs',
				'settings'		=> 'settings',
				'messages'		=> 'messages',
				'post'			=> 'post',
				'recover'		=> 'recover',
				'profile'		=> 'profile',
				'notifications'	=> 'notifications',
				'search'		=> 'search',
				'group'			=> 'group',
				'page'			=> 'page',
				'start_reg'		=> 'start_reg',
				'questions'		=> 'questions',
				'register'		=> 'register',
				'info'			=> 'info'
				);

define('COOKIE_PATH', preg_replace('|https?://[^/]+|i', '', $CONF['url']).'/');
?>