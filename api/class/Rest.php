<?php
session_start();
require_once '../../includes/vendor/autoload.php';

class Users
{
	private $host  = 'localhost';
	private $user  = 'root';
	private $password   = "";
	private $database  = "wno";

	// temp values
	private $is_admin;

	private $per_page = 100;
	// private $userTable = 'users';
	private $db = false;
	public function __construct()
	{
		if (!$this->db) {
			$conn = new mysqli($this->host, $this->user, $this->password, $this->database);
			if ($conn->connect_error) {
				die("Error failed to connect to MySQL: " . $conn->connect_error);
			} else {
				$this->db = $conn;
			}
		}

		$channelName = 'news';
		$recipient = 'ExponentPushToken[c6TPq4H4RKbo0HUxmGY9jh]';

		// You can quickly bootup an expo instance
		$expo = \ExponentPhpSDK\Expo::normalSetup();

		// Subscribe the recipient to the server
		$expo->subscribe($channelName, $recipient);

		// Build the notification data
		$notification = ['body' => 'Hello World!'];

		// Notify an interest with a notification
		$expo->notify([$channelName], $notification);

		$notification = ['body' => 'Hello World!', 'data'=> json_encode(array('someData' => 'goes here'))];
	}


	function login($userData)
	{

		// $username = $userData['username'];
		// $password = $userData['password'];
		$username = $userData->username;
		$password = $userData->password;

		$password_hash = null;

		$userQuery = "
			SELECT * 
			FROM users WHERE username = '$username'
			";
		$resultData = mysqli_query($this->db, $userQuery) or die(mysqli_error($this->db));
		$userData = array();

		while ($userRecord = mysqli_fetch_assoc($resultData)) {
			$userData = $userRecord;
			$password_hash = $userRecord['password'];
			$_SESSION['wn_mobile_idu'] = $userRecord['idu'];
		}

		if (password_verify($password, $password_hash)) {
			// return
			header('Content-Type: application/json');
			echo json_encode($userData);
		} else {
			header('Content-Type: application/json');
			echo json_encode("Incorrect Data");
		}
	}

	function loginAdmin($userData)
	{
		$username = $userData["username"];
		$password = $userData["password"];

		$userQuery = "
			SELECT * 
			FROM admin WHERE username = '$username'
			";
		$resultData = mysqli_query($this->db, $userQuery) or die(mysqli_error($this->db));
		$userData = array();
		while ($userRecord = mysqli_fetch_assoc($resultData)) {
			$userData = $userRecord;
			$password_hash = $userRecord['password'];
			// $_SESSION['wn_mobile_idu'] = $userRecord['is_admin'];
		}

		if (password_verify($password, $password_hash)) {
			// return
			header('Content-Type: application/json');
			// echo json_encode($userData);
			return json_encode($userData);
		} else {
			header('Content-Type: application/json');
			// echo json_encode("Incorrect Data");
			return json_encode("Incorrect Data");
		}
	}

	function id()
	{
		return $_SESSION['wn_mobile_idu'];
	}

	function logedInUser()
	{
		$id = $this->id();
		$userQuery = "SELECT * FROM users WHERE idu = '$id'";
		$resultData = mysqli_query($this->db, $userQuery) or die(mysqli_error($this->db));
		header('Content-Type: application/json');
		// echo json_encode($resultData->fetch_assoc());
		return $resultData->fetch_assoc();
	}

	function generateSalt($length = 10)
	{
		$str = '';
		$salt_chars = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9));
		for ($i = 0; $i < $length; $i++) {
			$str .= $salt_chars[array_rand($salt_chars)];
		}
		return password_hash($str . time(), PASSWORD_DEFAULT);
	}

	function logOut()
	{
		$this->db->query(sprintf("UPDATE `users` SET `login_token` = '%s' WHERE `idu` = '%s'", $this->generateSalt(), $this->db->real_escape_string($this->id())));
		// session_destroy();
	}

	function getFriendsListReq($type = null, $user_id = null)
	{

		if ($type) {
			$status = "";
		} else {
			$status = "AND `status` = '1'";
		}
		// The query to select the friends list
		$query = sprintf(
			"SELECT `user2` as `friends` FROM `friendships` 
			WHERE `user1` = '%s' %s
			
			UNION ALL SELECT `user1` as `friends` FROM `friendships` 
            WHERE `user2` = '%s' %s
			ORDER BY `friends` ASC",
			$this->db->real_escape_string($user_id),
			$status,
			$this->db->real_escape_string($user_id),
			$status
		);

		// Run the query
		$result = $this->db->query($query) or die(mysqli_error($this->db));

		// The array to store the subscribed users
		$friends = array();
		while ($row = $result->fetch_assoc()) {
			$friends[] = $row['friends'];
		}

		// include self on the list
		// $friends = array_push($friends, "user_id");

		// Close the query
		$result->close();

		// Return the friends list (e.g: 13,22,19)
		// return implode(',', array_slice($friends, 0, 2000));

		// header('Content-Type: application/json');
		echo json_encode($friends);
	}

	function getUsers($user_id = null)
	{

		$friendslist = $this->getFriendsList_(1, $user_id);

		// If there are friends available, exclude them
		if ($friendslist) {
			$friendslist = $user_id . ',' . $friendslist;
		} else {
			$friendslist = $user_id;
		}
		// // The array to store the subscribed users
		$rows = array();
		$query = $this->db->query(sprintf("SELECT `idu`, `username`, `first_name`, `last_name`, `location`, `email`, `image`, `cover` FROM `users` WHERE `idu` NOT IN (%s) AND `suspended` = 0 ORDER BY `idu` DESC LIMIT 100", $friendslist)) or die(mysqli_error($this->db));

		// Store the array results
		while ($row = $query->fetch_assoc()) {
			$rows[] = $row;
		}

		echo json_encode($rows);
	}

	function getPendingFriends($user_id = null)
	{
		// // The array to store the subscribed users
		$rows = array();
		$query = $this->db->query(sprintf("SELECT `users`.`idu`, `users`.`username`, `users`.`first_name`, `users`.`last_name`, `users`.`location`, `users`.`email`, `users`.`image`, `users`.`cover`, `user1`, `user2` FROM `friendships` INNER JOIN `users` on `users`.`idu` = `user2` WHERE `user1` = '%s' AND `status` = 0 ORDER BY `idu` DESC LIMIT 100", $user_id)) or die(mysqli_error($this->db));

		// Store the array results
		while ($row = $query->fetch_assoc()) {
			$rows[] = $row;
		}

		echo json_encode($rows);
	}

	function countFriends($id, $status)
	{
		if (isset($status)) {
			if ($status == 1) {
				$status = 'AND `status` = 1';
			} else {
				$status = 'AND `status` = 0';
			}
		} else {
			$status = '';
		}
		$query = $this->db->query(sprintf("SELECT (SELECT COUNT(*) as number FROM `friendships` WHERE `user1` = '%s' %s) as `user1`, (SELECT COUNT(*) as number FROM `friendships` WHERE `user2` = '%s' %s) as `user2`", $this->db->real_escape_string($id), $status, $this->db->real_escape_string($id), $status));

		$result = $query->fetch_assoc();

		return ($result['user1'] + $result['user2']);
	}

	public function profileData($username = null, $id = null)
	{
		// The query to select the profile
		// If the $id is set (used in Add Friend function for profiles) then search for the ID
		if ($id) {
			$query = sprintf("SELECT `idu`, `username`, `email`, `first_name`, `last_name`, `country`, `location`, `address`, `school`, `work`, `website`, `bio`, `date`, `facebook`, `twitter`, `image`, `private`, `suspended`, `privacy`, `born`, `cover`, `verified`, `gender`, `interests`, `email_new_friend`, `offline`, `online` FROM `users` WHERE `idu` = '%s'", $this->db->real_escape_string($id));
		} else {
			$query = sprintf("SELECT `idu`, `username`, `email`, `first_name`, `last_name`, `country`, `location`, `address`, `school`, `work`, `website`, `bio`, `date`, `facebook`, `twitter`, `image`, `private`, `suspended`, `privacy`, `born`, `cover`, `verified`, `gender`, `interests`, `email_new_friend`, `offline`, `online` FROM `users` WHERE `username` = '%s'", $this->db->real_escape_string($username));
		}

		// Run the query
		$result = $this->db->query($query);

		return $result->fetch_assoc();
	}


	function getBlocked($id, $type = null, $extra = null, $user_id = null)
	{
		// Type 0: Output the button state
		// Type 1: Block/Unblock a user
		// Type 2: Returns 1 if blocked
		// Extra: Returns output for the profile [...] menu

		$profile = $this->profileData(null, $id);

		// If the user is not a confirmed one
		if ($profile['suspended'] == 2) {
			return false;
		}

		// If the username does not exist, return nothing
		if (empty($profile)) {
			return false;
		} else {
			// Verify if there is any block issued for this username
			if ($type == 2) {
				$checkBlocked = $this->db->query(sprintf("SELECT * FROM `blocked` WHERE ((`uid` = '%s' AND `by` = '%s') OR (`uid` = '%s' AND `by` = '%s'))", $this->db->real_escape_string($id), $this->db->real_escape_string($user_id), $this->db->real_escape_string($user_id), $this->db->real_escape_string($id)));
			} else {
				$checkBlocked = $this->db->query(sprintf("SELECT * FROM `blocked` WHERE `uid` = '%s' AND `by` = '%s'", $this->db->real_escape_string($id), $this->db->real_escape_string($user_id)));
			}

			// If the Message/Comment exists
			$state = $checkBlocked->num_rows;

			if ($type == 2) {
				return $state;
			}

			// If type 1: Add/Remove
			if ($type) {
				// If there is a block issued, remove the block
				if ($state) {
					// Remove the block
					$this->db->query(sprintf("DELETE FROM `blocked` WHERE `uid` = '%s' AND `by` = '%s'", $this->db->real_escape_string($id), $this->db->real_escape_string($user_id)));

					// Block variable
					$y = 0;
				} else {
					// Insert the block
					$this->db->query(sprintf("INSERT INTO `blocked` (`uid`, `by`) VALUES ('%s', '%s')", $this->db->real_escape_string($id), $this->db->real_escape_string($user_id)));

					// Delete any friendships
					$this->db->query(sprintf("DELETE FROM `friendships` WHERE (`user1` = '%s' AND `user2` = '%s') OR (`user1` = '%s' AND `user2` = '%s')", $this->db->real_escape_string($user_id), $this->db->real_escape_string($id), $this->db->real_escape_string($id), $this->db->real_escape_string($user_id)));

					$this->db->query(sprintf("DELETE FROM `notifications` WHERE ((`from` = '%s' AND `to` = '%s') OR (`from` = '%s' AND `to` = '%s')) AND `type` IN (4,5)", $this->db->real_escape_string($user_id), $this->db->real_escape_string($id), $this->db->real_escape_string($id), $this->db->real_escape_string($user_id)));

					// Unblock variable
					$y = 1;
				}
				return [$id, $profile, $y, $extra];
			} else {
				return [$id, $profile, $state, $extra];
			}
		}
	}

	// function outputBlocked($id, $profile, $state, $extra) {
	// 	global $LNG;

	// 	if($extra) {
	// 		$x = '<div class="message-menu-row" onclick="doBlock('.$id.', 1)" id="block'.$id.'">'.($state ? $LNG['unblock'] : $LNG['block']).'</div>';
	// 	} else {
	// 		$x = '<span class="unblock-link"><a onclick="doBlock('.$id.', 1)">'.($state ? $LNG['unblock'] : $LNG['block']).'</a></span>';
	// 	}

	// 	return $x;
	// }


	function setFriend($to_be_friend = null, $user_id = null)
	{
		$currFriends = $this->countFriends($to_be_friend, 1);
		$targetFriends = $this->countFriends($user_id, 1);

		// If the user & the target has less than the maximum amount
		if ($currFriends < 3000 || $targetFriends < 3000) {
			// If the user is not blocked
			if (!$this->getBlocked($user_id, 2)) {
				$result = $this->db->query(sprintf("INSERT INTO `friendships` (`user1`, `user2`, `time`) VALUES ('%s', '%s', CURRENT_TIMESTAMP)", $this->db->real_escape_string($user_id), $this->db->real_escape_string($to_be_friend)));

				$insertNotification = $this->db->query(sprintf("INSERT INTO `notifications` (`from`, `to`, `type`, `read`) VALUES ('%s', '%s', '4', '0')", $this->db->real_escape_string($user_id), $to_be_friend));

				// if ($this->email_new_friend) {
				// If user has emails on new friendships enabled
				// if ($profile['email_new_friend']) {
				// 	// Send e-mail
				// 	sendMail($profile['email'], sprintf($LNG['ttl_new_friend_email'], $this->username), sprintf($LNG['new_friend_email'], realName($user_id->username, $profile['first_name'], $profile['last_name']), permalink($this->url . '/index.php?a=profile&u=' . $this->username), $this->username, $this->title, $this->title, permalink($this->url . '/index.php?a=settings&b=notifications')), $this->email);
				// }
				// }

				echo json_encode($result);
			}
		}
	}

	function getFriendsList_($type = null, $user_id = null)
	{
		// Type 0: Returns both confirmed and pending friendships
		// Type 1: Returns only confirmed friendships

		if ($type) {
			$status = "";
		} else {
			$status = "AND `status` = '1'";
		}

		// The query to select the friends list
		$query = sprintf("SELECT `user2` as `friends` FROM `friendships` WHERE `user1` = '%s' %s UNION ALL SELECT `user1` as `friends` FROM `friendships` WHERE `user2` = '%s' %s ORDER BY `friends` ASC", $this->db->real_escape_string($user_id), $status, $this->db->real_escape_string($user_id), $status);

		// Run the query
		$result = $this->db->query($query);

		// The array to store the subscribed users
		$friends = array();
		while ($row = $result->fetch_assoc()) {
			$friends[] = $row['friends'];
		}

		// Close the query
		$result->close();

		// Return the friends list (e.g: 13,22,19)
		// return implode(',', array_slice($friends, 0, 2000));
		return implode(',', $friends);
	}

	function getFriendsList($type = null, $user_id = null)
	{
		// Type 0: Returns both confirmed and pending friendships
		// Type 1: Returns only confirmed friendships

		if ($type) {
			$status = "";
		} else {
			$status = "AND `status` = '1'";
		}
		// The query to select the friends list
		$query = sprintf(
			"SELECT `user2` as `friends` FROM `friendships` 
			WHERE `user1` = '%s' %s
			
			UNION ALL SELECT `user1` as `friends` FROM `friendships` 
            WHERE `user2` = '%s' %s
			ORDER BY `friends` ASC",
			$this->db->real_escape_string($user_id),
			$status,
			$this->db->real_escape_string($user_id),
			$status
		);

		// Run the query
		$result = $this->db->query($query) or die(mysqli_error($this->db));

		// The array to store the subscribed users
		$friends = array();
		while ($row = $result->fetch_assoc()) {
			$friends[] = $row['friends'];
		}

		// include self on the list
		// $friends = array_push($friends, "user_id");

		// Close the query
		$result->close();

		// Return the friends list (e.g: 13,22,19)
		// return implode(',', array_slice($friends, 0, 2000));

		// header('Content-Type: application/json');
		// echo json_encode($friends);
		return implode(',', $friends);
	}

	function getFeeds($user_id, $start, $value = null, $from = null)
	{
		// From: Load posts starting with a certain ID
		$this->friends = $this->getFriendsList(null, $user_id->user_id);
		// $this->groups = $this->getGroupsList();

		if (!empty($this->friends)) {
			$this->friendsList = $user_id->user_id . ',' . $this->friends;
		} else {
			$this->friendsList = $user_id->user_id;
		}

		// Disable the per_page limit if $from is set
		if (is_numeric($from)) {
			$this->per_page = 9999;
			$from = 'AND `messages`.`id` > \'' . $this->db->real_escape_string($from) . '\'';
		} else {
			$from = '';
		}

		// If the $start value is 0, empty the query;
		if ($start == 0) {
			$start = '';
		} else {
			// Else, build up the query
			$start = 'AND `messages`.`id` < \'' . $this->db->real_escape_string($start) . '\'';
		}

		// Get the user feed
		if (empty($this->pages)) {
			$query = sprintf(
				"SELECT * FROM `messages` USE INDEX(`news_feed`) 
			LEFT JOIN `users` ON `users`.`idu` = `messages`.`uid` 
			AND `users`.`suspended` = 0 
			WHERE (`messages`.`uid` IN (%s) 
			AND `messages`.`page` = 0 
			AND `messages`.`group` = 0 
			AND `messages`.`public` != 0 %s%s) 
			ORDER BY `messages`.`id` DESC LIMIT %s",
				$this->friendsList,
				$start,
				$from,
				($this->per_page + 1)
			);
		}
		// Get the user feed and pages feed
		else {
			$query = sprintf(
				"(SELECT * FROM `messages` USE INDEX(`news_feed`) 
			LEFT JOIN `users` ON `users`.`idu` = `messages`.`uid` 
			AND `users`.`suspended` = 0 
			WHERE (`messages`.`uid` IN (%s) 
			AND `messages`.`group` = 0 
			AND `messages`.`page` = 0 
			AND `messages`.`public` != 0 %s%s) 
			ORDER BY `messages`.`id` DESC LIMIT %s)
			UNION (SELECT * FROM `messages` 
			LEFT JOIN `users` ON `users`.`idu` = `messages`.`uid` 
			AND `users`.`suspended` = 0 
			WHERE (`messages`.`page` IN (%s)
			AND `messages`.`public` != 0 %s%s)
			ORDER BY `messages`.`id` DESC LIMIT %s) 
			ORDER BY `id` DESC LIMIT %s",
				$this->friendsList,
				$start,
				$from,
				($this->per_page + 1),
				$this->pages,
				$start,
				$from,
				($this->per_page + 1),
				($this->per_page + 1)
			);
		}

		// Run the query
		$result = $this->db->query($query);

		// Set the result into an array
		$rows = array();
		while ($row = $result->fetch_assoc()) {
			$rows[] = $row;
		}

		header('Content-Type: application/json');
		// print_r($rows);
		// echo $this->friendsList;
		echo json_encode($rows);

		// return $this->getMessages($query, 'loadFeed', '\'' . saniscape($value) . '\'');
	}

	function getUserFeeds($id, $start = 0, $value = null, $from = null)
	{
		$profile = $this->getSingleUser($id);
		$logedInUser = $this->logedInUser();
		$this->profile_id = $profile['idu'];

		$index = '`uid`';
		// print_r($logedInUser['is_admin']);
		// return;
		// If the username exist
		if (!empty($profile['idu'])) {
			$private = '';
			if ($profile['suspended'] == 2) {
				$private = 'profile_not_exists';
			} elseif ($profile['suspended'] == 1) {
				$private = 'profile_suspended';
			} else {
				if ($this->is_admin) {
					$private = 0;
				} elseif ($this->id() == $this->profile_id) {
					$private = 0;
				} else {
					$friendship = $this->verifyFriendship($this->id(), $this->profile_id);

					// If the profile is set to friends only and there is no friendship
					if ($profile['private'] == 2 && $friendship['status'] !== '1') {
						$private = 'profile_semi_private';
					}

					// If the profile is fully private
					elseif ($profile['private'] == 1) {
						$private = 'profile_private';
					}
					// If the profile is blocked
					// elseif($this->getBlocked($this->profile_id, 2)) {
					// 	$private = 'profile_blocked';
					// }
				}
			}
			if ($private) {
				$error_msg = "Profile is private";
				return $error_msg;
			}
			// Allowed types
			$this->listTypes = $this->listTypes('profile');
			$this->listDates = $this->listDates($this->profile_id, 'profile');

			// Disable the per_page limit if $from is set
			if (is_numeric($from)) {
				$this->per_page = 99;
				$from = 'AND messages.id > \'' . $this->db->real_escape_string($from) . '\'';
				$index = '`uid`, PRIMARY';
			} else {
				$this->per_page = 99;
				$from = '';
			}

			// If the $start value is 0, empty the query;
			if ($start == 0) {
				$start = '';
			} else {
				// Else, build up the query
				$start = 'AND `messages`.`id` < \'' . $this->db->real_escape_string($start) . '\'';
			}

			// Decide if the query will include only public messages or not
			// if the user that views the profile is not the owner
			$public = '';
			if ($this->id() !== $this->profile_id) {
				// Check if is admin or not
				if ($this->is_admin) {
					$public = '';
				} else {
					// Check if there is any friendship relation
					$friendship = $this->verifyFriendship($this->id(), $this->profile_id);

					if ($friendship['status'] == '1') {
						$public = "AND `messages`.`public` <> 0";
					} else {
						$public = "AND `messages`.`public` = 1";
					}
				}
			}

			$type = $date = '';

			// Check for active filters

			if (in_array($value, $this->listTypes)) {
				$type = sprintf("AND `messages`.`type` = '%s'", $this->db->real_escape_string($value));
				$index = '`uid`, `type`';
			} elseif (in_array($value, $this->listDates)) {
				$date = sprintf("AND `time` >= '%s' AND `time` < '%s'", $this->db->real_escape_string($value) . '-01-01 00:00:00', ($this->db->real_escape_string($value) + 1) . '-01-01 00:00:00');
				$index = '`uid`, `time`';
			}

			// Set results to get / pahe
			$per_page = 100;

			$query = sprintf(
				"SELECT * FROM `messages` USE INDEX(%s), `users` 
				WHERE `messages`.`uid` = '%s' %s
				AND `messages`.`group` = 0 AND `messages`.`page` = 0
				AND `messages`.`uid` = `users`.`idu` %s %s %s 
				ORDER BY `messages`.`id` DESC LIMIT %s",
				$index,
				$this->db->real_escape_string($this->profile_id),
				$type . $date,
				$public,
				$start,
				$from,
				($this->per_page + 1)
			);
			$query_ = sprintf(
				"SELECT * FROM `messages` USE INDEX(%s), `users` 
				WHERE `messages`.`uid` = '%s' %s
				AND `messages`.`group` = 0 AND `messages`.`page` = 0
				AND `messages`.`uid` = `users`.`idu` %s %s %s 
				ORDER BY `messages`.`id` DESC LIMIT %s",
				$index,
				$this->db->real_escape_string($this->profile_id),
				$type . $date,
				$public,
				$start,
				$from,
				$per_page
			);


			// Run the query
			$result = $this->db->query($query);

			// Set the result into an array
			$rows = array();
			while ($row = $result->fetch_assoc()) {
				$rows[] = $row;
			}

			header('Content-Type: application/json');
			// print_r($rows);
			// echo $this->friendsList;
			echo json_encode($rows);
		} else {
			echo json_encode("Profile Non Existent");
		}
	}

	function postFeeds($data, $files = null)
	{
		// $file     = $data->file;
		// $extension = $file->getClientOriginalExtension();
		// $target_dir = "../";
		// $file_name = 'films_' . $extension . '';
		// $target_file = $target_dir . $file_name;
		// if (move_uploaded_file($file_name, $target_file)) {
		// 	return "Done";
		// }

		$message = $data->message;
		$hashtag = $data->tag;
		$img = $data->img;
		$group_id = $data->group_id;
		$uuid = $data->uuid;
		$type = '';

		// $message = $data['message'];
		// $hashtag = $data['tag'];
		// $group_id = $data['group_id'];

		$image_uniq = uniqid();
		$image_name = "";

		if ($img != '') {
			$image_name = "../../uploads/media/" . $image_uniq . ".png";

			file_put_contents($image_name, base64_decode($img));
			$type = 'picture';
		}
		$public = 1;

		$query = sprintf(
			"INSERT INTO `messages`
				(`uid`, `message`, `tag`, `type`, `value`, `group`, `time`, `public`)
			VALUES ('%s', '%s', '%s', '%s', '%s', '%s', CURRENT_TIMESTAMP, '%s')",
			$uuid,
			$message,
			$hashtag,
			$type,
			$image_name,
			$group_id,
			$public
		);

		mysqli_query($this->db, $query);

		echo json_encode('Posted');
	}

	function listTypes($friends = null)
	{
		// Removed any verification queries for performance purposes
		if ($friends == false) {
			return false;
		} elseif ($friends == 'profile') {
			$list = array('food', 'game', 'map', 'music', 'picture', 'shared', 'video');
		} elseif ($friends) {
			$list = array('food', 'game', 'map', 'music', 'picture', 'shared', 'video');
		}
		return $list;
	}

	function listDates($id, $friends = null)
	{
		$profile_data = $this->getSingleUser($id);
		if ($friends == false) {
			return false;
		} elseif ($friends == 'profile') {
			$start_date = ($profile_data['date'] ? $profile_data['date'] : $this->registration_date);
		} elseif ($friends) {
			if ($friends == 'hashtag') {
				$query = $this->db->query(sprintf("SELECT extract(YEAR from `messages`.`time`) AS `year` FROM `messages` WHERE `messages`.`tag` LIKE '%s' ORDER BY `messages`.`id` ASC LIMIT 1", '%' . $this->db->real_escape_string($_GET['tag']) . '%'));
			} else {
				$query = $this->db->query(sprintf("SELECT extract(YEAR from `users`.`date`) AS `year` FROM `users` WHERE (`users`.`idu` IN (%s) AND `users`.`suspended` = 0) ORDER BY `users`.`date` ASC LIMIT 1", $friends));
			}

			$result = $query->fetch_assoc();

			$start_date = $result['year'] . '-01-01';
		}

		$date = date("Y", strtotime($start_date));
		while ($date <= date("Y", strtotime(date('Y-m-d')))) {
			$list[] = $date;
			$date++;
		}

		return array_reverse($list);
	}

	//? Gets single group info
	function getAllGroups()
	{
		$query = $this->db->query(sprintf("SELECT * FROM `groups` "));

		// return $result->fetch_assoc();
		$rows = array();
		while ($row = $query->fetch_assoc()) {
			$rows[] = $row;
		}

		header('Content-Type: application/json');
		echo json_encode($rows);
	}

	//? Gets group posts info
	function getGroupPosts($id)
	{
		$query = sprintf(
			"SELECT * FROM `messages` INNER JOIN `groups` ON `messages`.`group` INNER JOIN `users` ON `messages`.`uid` WHERE `messages`.`group` = '%s' ",
			$this->db->real_escape_string($id->group_id)
		);


		$query = sprintf(
			"SELECT * FROM `messages` USE INDEX(`news_feed`) 
		LEFT JOIN `users` ON `users`.`idu` = `messages`.`uid` 
		AND `users`.`suspended` = 0 
		WHERE (`messages`.`group` IN (%s) 
		AND `messages`.`page` = 0 
		AND `messages`.`public` != 0) 
		ORDER BY `messages`.`id` ",
			$this->db->real_escape_string($id->group_id)
		);

		$result = $this->db->query($query);

		$rows = array();
		while ($row = $result->fetch_assoc()) {
			$rows[] = $row;
		}

		header('Content-Type: application/json');
		echo json_encode($rows);
	}

	//? Gets single group info
	function getGroupData($id)
	{
		$query = sprintf(
			"SELECT * FROM `groups` WHERE `id` = '%s' ",
			$this->db->real_escape_string($id->group_id)
		);

		$result = $this->db->query($query);

		// return $result->fetch_assoc();
		header('Content-Type: application/json');
		echo json_encode($result->fetch_assoc());
	}

	//? gets group admin/creator
	function getGroupOwner($id)
	{
		// Return the group owner ID (Admin panel)
		$query = sprintf(
			"SELECT * FROM `groups_users` WHERE `group` = '%s' AND `permissions` = 2",
			$this->db->real_escape_string($id)
		);

		// Run the query
		$result = $this->db->query($query);

		// return $result->fetch_assoc();
		header('Content-Type: application/json');
		echo json_encode($result->fetch_assoc());
	}

	function getGroupMemberData($group = null, $user_id)
	{
		if ($group && $user_id) {
			$query = $this->db->query(sprintf(
				"SELECT `groups_users`.`status`,`groups_users`.`permissions`
				FROM `groups_users` 
				WHERE `groups_users`.`group` = '%s' 
				AND `groups_users`.`user` = '%s'",
				$this->db->real_escape_string($group),
				$this->db->real_escape_string($user_id)
			));
			// return $query->fetch_assoc();
			header('Content-Type: application/json');
			echo json_encode($query->fetch_assoc());
		}
	}

	function getGroupMembers($group = null)
	{
		if ($group) {
			$query = $this->db->query(sprintf(
				"SELECT users.*, groups_users.status,groups_users.permissions, groups_users.user
				FROM `groups_users`, `users` 
				WHERE `groups_users`.`group` = '%s'
				AND `users`.`idu` = `groups_users`.`user` ",
				$this->db->real_escape_string($group)
			));
			// return $query->fetch_assoc();

			$rows = array();
			while ($row = $query->fetch_assoc()) {
				$rows[] = $row;
			}

			header('Content-Type: application/json');
			echo json_encode($rows);
		}
	}

	function verifyFriendship($user_id, $profile_id)
	{
		if ($user_id == $profile_id) {
			$result = array();
			$result['status'] = 'owner';
			$result['user1'] = null;
			$result['user2'] = null;
		} else {
			$query = $this->db->query(sprintf("SELECT * FROM `friendships` WHERE ((`user1` = '%s' AND `user2` = '%s') OR (`user1` = '%s' AND `user2` = '%s'))", $this->db->real_escape_string($user_id), $this->db->real_escape_string($profile_id), $this->db->real_escape_string($profile_id), $this->db->real_escape_string($user_id)));

			$result = $query->fetch_assoc();
		}
		// Returns the friendship status
		// Status: 	0 Pending
		//			1 Confirmed

		return array(
			'status'	=> $result['status'] ?? null,
			'from'		=> $result['user1'] ?? null,
			'to'		=> $result['user2'] ?? null
		);
	}

	//! LIKES
	//* checks if something is liked
	//* return true if liked and false if not
	function verifyLike($user_id, $id)
	{

		$result = $this->db->query(sprintf(
			"SELECT * FROM `likes` WHERE `post` = '%s' AND `by` = '%s' ",
			$this->db->real_escape_string($id),
			$this->db->real_escape_string($user_id)
		));

		return ($result->num_rows) ? "true" : "false";
		// return ($result->num_rows) ? 1 : 0;
	}

	//* Like and dislike
	function like($user_id, $id, $type, $action = null)
	{
		global $LNG;
		// Type 0: Like Message
		// Type 1: Like Comment

		if ($type == 1) {
			$special = ', `comments`';
			$table = 'comments';
			$extra = 'WHERE `comments`.`mid` = `messages`.`id` AND';
		} else {
			$special = '';
			$table = 'messages';
			$extra = 'WHERE ';
		}

		// Select the comment's likes (if the comments exists)
		$query = $this->db->query(sprintf(
			"SELECT * FROM `users`, `messages` %s %s `%s`.`id` = '%s' AND `%s`.`uid` = `users`.`idu`",
			$special,
			$extra,
			$table,
			$this->db->real_escape_string($id),
			$table
		));
		$post = $query->fetch_assoc();

		// If the comment does not exists
		if (empty($post['id'])) {
			echo json_encode(false);
		}

		// Select the likes, if any
		$query = $this->db->query(sprintf(
			"SELECT * FROM `likes`, `users` WHERE `likes`.`post` = '%s' 
					AND `likes`.`type` = '%s' AND `likes`.`by` = '%s' AND `likes`.`by` = `users`.`idu`",
			$this->db->real_escape_string($id),
			$this->db->real_escape_string($type),
			$this->db->real_escape_string($user_id)
		));

		//* If a like already exists, dislike
		if ($query->num_rows > 0) {
			$this->db->query(sprintf(
				"DELETE FROM `likes` WHERE `type` = '%s' AND `post` = '%s' AND `by` = '%s'",
				$this->db->real_escape_string($type),
				$this->db->real_escape_string($id),
				$this->db->real_escape_string($user_id)
			));
			$this->db->query(sprintf(
				"UPDATE `%s` SET `likes` = `likes` -1, `time` = `time` WHERE id = '%s'",
				$table,
				$this->db->real_escape_string($id)
			));
			// $value = $LNG['like'];
			$action = 0;
		} else {
			$this->db->query(sprintf(
				"INSERT INTO `likes` (`post`, `by`, `type`) VALUES ('%s', '%s', '%s')",
				$this->db->real_escape_string($id),
				$this->db->real_escape_string($user_id),
				$this->db->real_escape_string($type)
			));
			$this->db->query(sprintf(
				"UPDATE `%s` SET `likes` = `likes` + 1, `time` = `time` WHERE id = '%s'",
				$table,
				$this->db->real_escape_string($id)
			));
			// $value = $LNG['dislike'];
			$action = 1;
		}

		if ($type == 1) {
			$parent = $post['mid'];
			$child = $post['id'];
			// $email_url = $this->url . '/index.php?a=post&m=' . $parent . '#comment' . $child;
			// $email_content = $LNG['like_c_email'];
			// $email_title = $LNG['ttl_like_c_email'];
			// $actions = $this->getCommentActions($id, $post['time'], null, true);
		} else {
			$parent = $post['id'];
			$child = 0;
			// $email_url = $this->url . '/index.php?a=post&m=' . $parent;
			// $email_content = $LNG['like_email'];
			// $email_title = $LNG['ttl_like_email'];
			// $actions = $this->getActions($id, null, null, null, true);
		}

		// If the action is "Like" and the post is not being made for a page
		if ($action > 0 && empty($post['page'])) {
			$this->db->query(sprintf(
				"INSERT INTO `notifications` (`from`, `to`, `parent`, `child`, `type`, `read`)
				VALUES ('%s', '%s', '%s', '%s', '2', '0')",
				$this->db->real_escape_string($user_id),
				$post['uid'],
				$parent,
				$child
			));
			// ? If email on likes is enabled in admin settings
			// if ($this->email_like) {
			// 	// If user has emails on like enabled and it\'s not liking his own post
			// 	if ($post['email_like'] && ($user_id !== $post['idu'])) {
			// 		// Send e-mail
			// 		sendMail($post['email'], sprintf($email_title, $this->username), sprintf($email_content, realName($post['username'], $post['first_name'], $post['last_name']), permalink($this->url . '/index.php?a=profile&u=' . $this->username), $this->username, $email_url, $this->title, permalink($this->url . '/index.php?a=settings&b=notifications')), $this->email);
			// 	}
			// }
		} else {
			$this->db->query(sprintf(
				"DELETE FROM `notifications` WHERE `parent` = '%s' AND `child` = '%s'
					AND `type` = '2' AND `from` = '%s'",
				$parent,
				$child,
				$this->db->real_escape_string($user_id)
			));
		}

		// Return the output
		echo json_encode($this->verifyLike($user_id, $id));
		// return json_encode(array('value' => $value, 'type' => $action, 'actions' => $actions));
	}

	//! COMMENTS
	//* Post comment
	function addComment($data, $files = null)
	{
		// $user_id, $id, $comment, $type = null, $value = null
		$type = null;

		$user_id = $data->user_id;
		$id = $data->id;
		$comment = $data->comment;

		// echo json_encode($comment);
		// return;

		$query = sprintf(
			"SELECT * FROM `messages`,`users` WHERE `id` = '%s' AND `messages`.`uid` = `users`.`idu`",
			$id
		);
		$result = $this->db->query($query);

		$row = $result->fetch_assoc();

		// If the message is shared to friends only
		// if ($row['public'] == 2) {
		// 	// If the user is also the owner
		// 	if ($user_id == $row['uid']) {
		// 		$row['public'] = 1;
		// 	} else {
		// 		// Check if there is any friendship relation
		// 		$friendship = $this->verifyFriendship($user_id, $row['uid']);

		// 		// Set the message to appear as public
		// 		if ($friendship['status'] == 1) {
		// 			$row['public'] = 1;
		// 		}
		// 	}
		// }

		// If the POST is public
		if ($comment) {
			// if ($type == 'picture' && (!empty($_FILES['value']['size']) || !empty($message))) {
			// 	// Define the array which holds the value names
			// 	$allowedExt = explode(',', 'png', 'jpg');
			// 	$ext = pathinfo($_FILES['value']['name'], PATHINFO_EXTENSION);
			// 	if (isset($_FILES['value']['name']) && $_FILES['value']['name'] !== '' && $_FILES['value']['size'] > 0) {
			// 		$tmp_name = $_FILES['value']['tmp_name'];
			// 		$name = pathinfo($_FILES['value']['name'], PATHINFO_FILENAME);
			// 		$fullname = $_FILES['value']['name'];
			// 		$ext = pathinfo($_FILES['value']['name'], PATHINFO_EXTENSION);
			// 		$finalName = uniqid(null, true) . '.' . $this->db->real_escape_string($ext);

			// 		// Define the type for picture
			// 		$type = 'picture';

			// 		// Store the values into arrays
			// 		$value = $finalName;

			// 		// Fix the image orientation if possible
			// 		// imageOrientation($tmp_name);

			// 		move_uploaded_file($tmp_name, _DIR_ . '/../uploads/media/' . $finalName);
			// 	}
			// } else {
			// 	$type = '';
			// 	$value = '';

			// 	// If the comment is empty
			// 	if (empty($comment)) {
			// 		return array(0);
			// 	}
			// }

			$type = '';
			$value = '';

			// Add the insert message
			$stmt = $this->db->prepare("INSERT INTO `comments` (`uid`, `mid`, `message`, `type`, `value`) VALUES (?, ?, ?, ?, ?)");

			$comment = htmlspecialchars($comment);

			$stmt->bind_param('iisss', $user_id, $id, $comment, $type, $value);

			// Execute the statement
			$stmt->execute();

			// Save the affected rows
			$affected = $stmt->affected_rows;

			// Close the statement
			$stmt->close();

			// Select the last inserted message
			$getId = $this->db->query(sprintf(
				"SELECT `id`,`uid`,`mid`,`message` FROM `comments` WHERE `uid` = '%s' AND `mid` = '%s' ORDER BY `id` DESC",
				$this->db->real_escape_string($user_id),
				$row['id']
			));
			$lastComment = $getId->fetch_assoc();

			// If the comment is not being posted on a page
			if (empty($row['page'])) {
				// Do the INSERT notification
				$insertNotification = $this->db->query(sprintf(
					"INSERT INTO `notifications` (`from`, `to`, `parent`, `child`, `type`, `read`) VALUES ('%s', '%s', '%s', '%s', '1', '0')",
					$this->db->real_escape_string($user_id),
					$row['uid'],
					$row['id'],
					$lastComment['id']
				));

				if ($affected) {
					// If email on comments is enabled in admin settings
					//*no emails for now
					// if ($this->email_comment) {
					// 	// If user has emails on like enabled and it\'s not liking his own post
					// 	if ($row['email_comment'] && ($user_id !== $row['idu'])) {
					// 		// Send e-mail
					// 		sendMail($row['email'], sprintf($LNG['ttl_comment_email'], $this->username), sprintf($LNG['comment_email'], realName($row['username'], $row['first_name'], $row['last_name']), permalink($this->url . '/index.php?a=profile&u=' . $this->username), $this->username, permalink($this->url . '/index.php?a=post&m=' . $id), $this->title, permalink($this->url . '/index.php?a=settings&b=notifications')), $this->email);
					// 	}
					// }
				}
			}

			if ($affected) {
				// Update the comments counter
				$this->db->query(sprintf("UPDATE `messages` SET `comments` = `comments` + 1, `time` = `time`
						WHERE `id` = '%s'", $this->db->real_escape_string($row['id'])));

				preg_match_all('/(^|[^a-z0-9_\/])@([a-z0-9_]+)/i', $lastComment['message'], $matchedMentions);

				$i = 0;
				$prevent = array();
				foreach ($matchedMentions[2] as $mention) {
					if ($i == 30) break;

					if (!in_array($mention, $prevent)) {
						// Validate the user
						$getUser = $this->db->query(sprintf("SELECT `idu`, `username`, `first_name`, `last_name`, `email`, `email_mention` FROM `users` WHERE `username` = '%s'", $this->db->real_escape_string($mention)));
						$mUser = $getUser->fetch_assoc();

						$getBlocked = $this->db->query(sprintf("SELECT * FROM `blocked` WHERE `by` = '%s' AND `uid` = '%s'", $this->db->real_escape_string($mUser['idu']), $this->db->real_escape_string($user_id)));

						// If the user exists and is not the message owner
						if ($getUser->num_rows > 0 && $getBlocked->num_rows == 0 && $mUser['idu'] != $user_id) {
							// If the user has email on mention enabled and the email is enabled in the Admin Panel
							//*also no emails yet
							// if ($mUser['email_mention'] == 1 && $this->email_mention == 1) {
							// 	sendMail($mUser['email'], sprintf($LNG['ttl_mention_c_email'], $mUser['username']), sprintf($LNG['mention_c_email'], realName($mUser['username'], $mUser['first_name'], $mUser['last_name']), permalink($this->url . '/index.php?a=profile&u=' . $this->username), $this->username, permalink($this->url . '/index.php?a=post&m=' . $lastComment['mid'] . '#comment' . $lastComment['id']), $this->title, permalink($this->url . '/index.php?a=settings&b=notifications')), $this->site_email);
							// }

							$this->db->query(sprintf("INSERT INTO `notifications` (`from`, `to`, `parent`, `child`, `type`, `read`)
									VALUES ('%s', '%s', '%s', '%s', 11, 0)", $user_id, $mUser['idu'], $lastComment['mid'], $lastComment['id']));
						}
					}
					$prevent[] = $mention;
					$i++;
				}
			}

			// If the comment was added, return 1
			return ($affected) ? 1 : 0;
		} else {
			return 0;
		}
	}


	//* Edits a comment
	function postEdit($user_id, $message, $id)
	{

		if (empty($message)) {
			echo json_encode(false);
		} else {
			// Update the message
			$result = $this->db->query(sprintf(
				"UPDATE `messages` SET `message` = '%s', `time` = `time` WHERE `id` = '%s' AND `uid` = '%s'",
				$this->db->real_escape_string(htmlspecialchars($message)),
				$this->db->real_escape_string($id),
				$this->db->real_escape_string($user_id)
			));

			if ($result)
				echo json_encode(true);
			else
				echo json_encode(false);
		}
	}

	function commentEdit($user_id, $message, $id)
	{

		if (strlen($message) > $this->message_length || empty($message)) {
			return false;
		} else {
			// Update the message
			$result = $this->db->query(sprintf("UPDATE `comments` SET `message` = '%s', `time` = `time` WHERE `id` = '%s' AND `uid` = '%s'", $this->db->real_escape_string(htmlspecialchars($message)), $this->db->real_escape_string($id), $this->db->real_escape_string($user_id)));

			// $select = $this->db->query(sprintf("SELECT `uid`, `message` FROM `comments` WHERE `id` = '%s' AND `uid` = '%s'", $this->db->real_escape_string($id), $this->db->real_escape_string($user_id)));
			// $result = $select->fetch_assoc();
			if ($result)
				echo json_encode(true);
			else
				echo json_encode(false);
			// Verify if is the message owner (prevents obtaining message's content from private posts for example)
			// if($result['uid'] == $user_id) {
			// 	return trim(nl2br($this->parseMessage($result['message'])));
			// }
		}
	}

	function getMessagesIds($id = null, $group = null, $extra = null, $share = null, $page = null, $user_id)
	{
		// Extra: get all the ids posted in a group/page
		if ($extra) {
			if ($page) {
				$query = $this->db->query(sprintf("SELECT `id` FROM `messages` WHERE `page` = '%s'%s ORDER BY `id` ASC", $this->db->real_escape_string($extra), $share));
			} else {
				$query = $this->db->query(sprintf("SELECT `id` FROM `messages` WHERE `group` = '%s'%s ORDER BY `id` ASC", $this->db->real_escape_string($extra), $share));
			}
		} elseif ($share) {
			$query = $this->db->query(sprintf("SELECT `id` FROM `messages` WHERE `type` = 'shared' AND `value` IN (%s) ORDER BY `id` ASC", $this->db->real_escape_string($share)));
		} else {
			$x = '';
			if ($group) {
				$x = " AND `group` = '" . $group . "'";
			} elseif ($page) {
				$x = " AND `page` = '" . $page . "'";
			}
			$query = $this->db->query(sprintf("SELECT `id` FROM `messages` WHERE `uid` = '%s'%s ORDER BY `id` ASC", ($id ? $this->db->real_escape_string($id) : $this->db->real_escape_string($user_id)), $x));
		}
		$output = [];
		while ($row = $query->fetch_assoc()) {
			$output[] = $row['id'];
		}

		return implode(',', $output);
	}

	function deleteShared($id)
	{
		$this->db->query(sprintf("DELETE FROM `comments` WHERE `mid` IN (%s)", $id));
		$this->db->query(sprintf("DELETE FROM `likes` WHERE `post` IN (%s) AND `type` = 0", $id));
		$this->db->query(sprintf("DELETE FROM `reports` WHERE `post` IN (%s) AND `parent` = '0'", $id));
		$this->db->query(sprintf("DELETE FROM `notifications` WHERE `parent` IN (%s)", $id));
	}

	function deletePhotos($type, $value)
	{
		// If the message type is picture
		if ($type == 'picture') {
			// Explode the images string value
			$images = explode(',', $value);

			// Remove any empty array elements
			$images = array_filter($images);

			// Delete each image
			foreach ($images as $image) {
				unlink(__DIR__ . '/../uploads/media/' . $image);
			}
		}
	}

	// Delete 
	function delete($user_id, $id, $type)
	{
		// Type 0: Delete Comment
		// Type 1: Delete Message
		// Type 2: Delete Chat Message

		// Prepare the statement
		if ($type == 0) {
			// Check if the user is the owner of the message
			$ownership = $this->db->query(sprintf("SELECT `comments`.`uid` as `cuid`, `messages`.`uid` as `muid`, `comments`.`mid` as `mid`, `comments`.`type` as `type`, `comments`.`value` as `value` FROM `comments`, `messages` WHERE `comments`.`id` = '%s' AND `comments`.`mid` = `messages`.`id`", $this->db->real_escape_string($id)));
			$message = $ownership->fetch_assoc();

			// If the logged-in user is the message owner
			if ($user_id == $message['muid']) {
				// Take the ownership of the comment
				$user_id = $message['cuid'];
			}

			$stmt = $this->db->prepare("DELETE FROM `comments` WHERE `id` = '{$this->db->real_escape_string($id)}' AND `uid` = '{$this->db->real_escape_string($user_id)}'");

			$x = 0;
		} elseif ($type == 1) {
			// Get the current type (for images deletion)
			$query = $this->db->query(sprintf("SELECT `id`, `type`, `value` FROM `messages` WHERE `id` = '%s' AND `uid` = '%s'", $this->db->real_escape_string($id), $this->db->real_escape_string($user_id)));
			$message = $query->fetch_assoc();

			$stmt = $this->db->prepare("DELETE FROM `messages` WHERE `id` = '{$this->db->real_escape_string($id)}' AND `uid` = '{$this->db->real_escape_string($user_id)}'");

			$x = 1;
		} elseif ($type == 2) {
			// Get the current type (for images deletion)
			$query = $this->db->query(sprintf("SELECT `id`, `type`, `value` FROM `chat` WHERE `id` = '%s' AND `from` = '%s'", $this->db->real_escape_string($id), $this->db->real_escape_string($user_id)));
			$message = $query->fetch_assoc();

			// Check if there's any other unread messages
			$query_cid = $this->db->query(sprintf("SELECT `id`, `to`, `from` FROM `chat` WHERE `id` != '%s' AND `to` = (SELECT `to` FROM `chat` WHERE `id` = '%s') AND `from` = '%s' AND `read` = 0 ORDER BY `id` DESC LIMIT 1", $this->db->real_escape_string($id), $this->db->real_escape_string($id), $this->db->real_escape_string($user_id)));
			$result_cid = $query_cid->fetch_assoc();

			$stmt = $this->db->prepare("DELETE FROM `chat` WHERE `id` = '{$this->db->real_escape_string($id)}' AND `from` = '{$this->db->real_escape_string($user_id)}'");

			$x = 2;
		}

		// Execute the statement
		$stmt->execute();

		// Save the affected rows
		$affected = $stmt->affected_rows;

		// Close the statement
		$stmt->close();

		// If the messages/comments table was affected
		if ($affected) {
			// Deletes the Comments/Likes/Reports if the Message was deleted
			if ($x == 1) {
				$sids = $this->getMessagesIds(null, null, null, $id, null, $user_id);

				// If there are any messages shared
				if ($sids) {
					$this->deleteShared($sids);
				}

				// Delete all images from comments
				$query = $this->db->query(sprintf("SELECT `type`, `value` FROM `comments` WHERE `mid` = '%s' AND `type` = 'picture'", $this->db->real_escape_string($id)));

				$output = '';
				while ($row = $query->fetch_assoc()) {
					$output .= $row['value'] . ',';
				}

				$this->deletePhotos('picture', $output);

				$this->db->query(sprintf("DELETE FROM `comments` WHERE `mid` = '%s'", $this->db->real_escape_string($id)));
				$this->db->query(sprintf("DELETE FROM `likes` WHERE `post` = '%s' AND `type` = 0", $this->db->real_escape_string($id)));
				$this->db->query(sprintf("DELETE FROM `reports` WHERE `post` = '%s' AND `parent` = '0'", $this->db->real_escape_string($id)));
				$this->db->query(sprintf("DELETE FROM `notifications` WHERE `parent` = '%s'", $this->db->real_escape_string($id)));

				// If the message was a shared one, delete it from notifications as well
				if ($message['type'] == 'shared') {
					$this->db->query("DELETE FROM `notifications` WHERE `child` = '{$this->db->real_escape_string($id)}' AND `parent` = '{$message['value']}' AND `type` = 3");

					// Update the main post shares counter
					$this->db->query(sprintf("UPDATE `messages` SET `shares` = `shares` - 1, `time` = `time` WHERE `id` = '%s'", $this->db->real_escape_string($message['value'])));
				} else {
					$this->db->query("DELETE FROM `messages` WHERE `type` = 'shared' AND `value` = '{$this->db->real_escape_string($id)}'");
				}
			} elseif ($x == 0) {
				$this->db->query(sprintf("DELETE FROM `likes` WHERE `post` = '%s' AND `type` = 1", $this->db->real_escape_string($id)));
				$this->db->query("DELETE FROM `reports` WHERE `post` = '{$this->db->real_escape_string($id)}' AND `parent` <> '0'");
				$this->db->query("DELETE FROM `notifications` WHERE `child` = '{$this->db->real_escape_string($id)}' AND `type` = '2'");
				$this->db->query("DELETE FROM `notifications` WHERE `child` = '{$this->db->real_escape_string($id)}' AND `type` = '1'");
				$this->db->query("DELETE FROM `notifications` WHERE `child` = '{$this->db->real_escape_string($id)}' AND `type` = '11'");
				$this->db->query(sprintf("UPDATE `messages` SET `comments` = `comments` - 1, `time` = `time` WHERE `id` = '%s'", $this->db->real_escape_string($message['mid'])));
			} elseif ($x == 2) {
				// If there's another chat message available to be made as last id notification
				if ($result_cid['id']) {
					$this->db->query(sprintf("UPDATE `conversations` SET `cid` = '%s' WHERE `from` = '%s' AND `to` = '%s'", $result_cid['id'], $this->db->real_escape_string($user_id), $this->db->real_escape_string($result_cid['to'])));
				} else {
					$this->db->query(sprintf("DELETE FROM `conversations` WHERE `cid` = '%s'", $this->db->real_escape_string($id)));
				}
			}

			// Execute the deletePhotos function
			$this->deletePhotos($message['type'], $message['value']);
		}

		echo json_encode(true);
	}


	//* Fetch comments
	function getComments($post_id, $cid = null, $start = null, $owner = null)
	{
		// The query to select the subscribed users

		// If the $start value is 0, empty the query;
		// if ($start == 0) {
		// 	$start = '';
		// } else {
		// 	// Else, build up the query
		// 	$start = 'AND comments.id < \'' . $this->db->real_escape_string($cid) . '\'';
		// }
		$start = '';
		$c_per_page = 20;
		$commentList = array();

		$query = $this->db->query(sprintf(
			"SELECT * FROM comments, users WHERE comments.mid = '%s' AND comments.uid = users.idu %s
			ORDER BY comments.id DESC LIMIT %s",
			$this->db->real_escape_string($post_id),
			$start,
			// ($this->c_per_page + 1)
			($c_per_page + 1)
		));

		while ($row = $query->fetch_assoc()) {
			$commentList[] = $row;
		}

		header('Content-Type: application/json');
		echo json_encode($commentList);
		// return $this->comments($query, array('id' => $post_id, 'start' => $start, 'owner' => $owner));
	}

	//! Notifications start here

	function getNotifications($user_id)
	{
		$notifyList = array();

		$notifications = $this->db->query(
			sprintf("SELECT notifications.*, users.*
			         FROM `notifications`, `users`
					 WHERE `notifications`.`from` = `users`.`idu` 
					 AND `notifications`.`child` != '1'
					 AND `notifications`.`to` = '%s' ORDER BY `notifications`.`id`
					 DESC LIMIT 50", $this->db->real_escape_string($user_id->id))
		);

		while ($row = $notifications->fetch_assoc()) {
			$notifyList[] = $row;
		}

		header('Content-Type: application/json');
		echo json_encode($notifyList);
	}

	// Chats
	function getChatMessages($uid = null, $cid = null, $start = null, $type = null)
	{
		// uid = user id (from which user the message was sent)
		// cid = where the pagination will start
		// start = on/off
		// Type 0: Get all the messages from a conversation
		// Type 1: Get the last posted message from a conversation
		// Type 2: Get the latest unread messages from a conversation

		// If the $start value is 0, empty the query;
		$messages = array();
		$query = '';

		// if ($start == 0) {
		// 	$start = '';
		// } else {
		// 	$start = '';
		// 	// Else, build up the query
		// 	// $start = 'AND `chat`.`id` < \'' . $this->db->real_escape_string($cid) . '\'';
		// }

		if ($type == 1) {
			$query = $this->db->query(sprintf("SELECT * FROM `chat`, `users` WHERE (`chat`.`to` = '%s' AND `chat`.`from` = `users`.`idu`) ORDER BY `chat`.`id` DESC LIMIT 1", $this->db->real_escape_string($uid)));
		} elseif ($type == 2) {
			$query = $this->db->query(sprintf("SELECT * FROM `chat`,`users` WHERE `to` = '%s' AND `read` = '0' AND `chat`.`from` = `users`.`idu` ORDER BY `chat`.`id` DESC", $this->db->real_escape_string($uid)));
		} else {
			$query = $this->db->query(sprintf("SELECT * FROM `chat` LEFT JOIN `users` ON `users`.`idu` = `chat`.`from` WHERE (`chat`.`from` = '%s' AND `chat`.`to` = '%s') OR (`chat`.`from` = '%s' AND `chat`.`to` = '%s') ORDER BY `chat`.`id` ", $this->db->real_escape_string($uid), $this->db->real_escape_string($cid), $this->db->real_escape_string($cid), $this->db->real_escape_string($uid)));
		}

		while ($row = $query->fetch_assoc()) {
			$messages[] = $row;
		}

		header('Content-Type: application/json');
		echo json_encode($messages);
	}

	function setNewMessage($uuid, $ToId, $msg)
	{

		$query = sprintf(
			"INSERT INTO `chat`
				(`from`, `to`, `message`, `type`, `value`, `read`, `time`)
			VALUES ('%s', '%s', '%s', '', '', '0', CURRENT_TIMESTAMP)",
			$uuid,
			$ToId,
			$msg,
		);

		mysqli_query($this->db, $query) or die($this->db);

		echo json_encode('sent');
	}

	//! FRIENDS
	//? Gets all user
	function getAllUsers()
	{
		$query = $this->db->query(sprintf("SELECT * FROM `users` "));

		// return $result->fetch_assoc();
		$rows = array();
		while ($row = $query->fetch_assoc()) {
			$rows[] = $row;
		}

		header('Content-Type: application/json');
		echo json_encode($rows);
	}


	function removePendingFriend($user_id = null, $friend_id = null)
	{
		$this->db->query(sprintf("DELETE FROM `friendships` WHERE `user1` = '%s' AND `user2` = '%s'", $this->db->real_escape_string($user_id), $this->db->real_escape_string($friend_id)));

		header('Content-Type: application/json');
		// echo json_encode($query->fetch_assoc());
		echo json_encode('done');
	}

	function getSingleUser($id)
	{
		$userQuery = "SELECT * FROM users WHERE idu = '$id' ";
		$query = mysqli_query($this->db, $userQuery) or die(mysqli_error($this->db));

		header('Content-Type: application/json');
		// echo json_encode($query->fetch_assoc());
		return $query->fetch_assoc();
	}

	// function checkFriendship($type = null, $list = null, $z = null)
	// {
	// 	global $LNG;
	// 	// Type 0: Show the button
	// 	// Type 1: Go trough the add friend query
	// 	// List: Array (for the dedicated profile page list)
	// 	// $z 1: A switcher for the sublist CSS class
	// 	// $z 2: Request from the notifications widget to confirm the friendship
	// 	// $z 3: Request from the notifications widget to decline the friendship

	// 	// Return if the user is not logged in
	// 	if (!$this->id) {
	// 		return false;
	// 	}
	// 	if ($list) {
	// 		$profile = $list;
	// 	} else {
	// 		$profile = $this->profile_data;
	// 	}
	// 	// If the user is not a confirmed one
	// 	if (isset($profile['suspended']) && $profile['suspended'] == 2) {
	// 		return false;
	// 	}
	// 	$style = '';
	// 	// Verify if the username is logged in, and it's not the same with the viewed profile
	// 	if (!empty($this->username) && $this->username !== $profile['username']) {
	// 		if ($z == 1) {
	// 			$style = ' subslist';
	// 		}

	// 		if ($type) {
	// 			$friendship = $this->verifyFriendship($this->id, $this->db->real_escape_string($profile['idu']));
	// 			// If the friendship status is confirmed OR if the friendship status is pending and the sender is the owner OR the request is to delete the friendship request then cancel the friendship
	// 			if ($friendship['status'] == '1' || ($friendship['status'] == '0' && $friendship['from'] == $this->id) || ($friendship['to'] == $this->id && $type == 3)) {
	// 				$result = $this->db->query(sprintf("DELETE FROM `friendships` WHERE (`user1` = '%s' AND `user2` = '%s') OR (`user1` = '%s' AND `user2` = '%s')", $this->db->real_escape_string($this->id), $this->db->real_escape_string($profile['idu']), $this->db->real_escape_string($profile['idu']), $this->db->real_escape_string($this->id)));

	// 				$deleteNotification = $this->db->query(sprintf("DELETE FROM `notifications` WHERE ((`from` = '%s' AND `to` = '%s') OR (`from` = '%s' AND `to` = '%s')) AND `type` IN (4,5)", $this->db->real_escape_string($this->id), $this->db->real_escape_string($profile['idu']), $this->db->real_escape_string($profile['idu']), $this->db->real_escape_string($this->id)));

	// 				// If the decline was done from the notifications widget
	// 				if ($type == 3) {
	// 					return '<div class="notification-button button-normal"><a href="' . permalink($this->url . '/index.php?a=profile&u=' . $profile['username']) . '" target="_blank">' . $LNG['declined'] . '</a></div>';
	// 				}
	// 			}
	// 			// If there is a pending invitation
	// 			elseif ($friendship['status'] == '0' && $friendship['to'] == $this->id && ($type == 1 || $type == 2)) {
	// 				// Verify the current amount of friends
	// 				$currFriends = $this->countFriends($this->id, 1);
	// 				$targetFriends = $this->countFriends($profile['idu'], 1);

	// 				// Show the maximum limit exceeded when on the notifications widget
	// 				if ($currFriends >= $this->friends_limit || $targetFriends >= $this->friends_limit) {
	// 					if ($type == 2) {
	// 						if ($currFriends >= $this->friends_limit) {
	// 							return sprintf($LNG['friends_limit']);
	// 						} else {
	// 							return sprintf($LNG['user_friends_limit']);
	// 						}
	// 					}
	// 				} else {
	// 					$result = $this->db->query(sprintf("UPDATE `friendships` SET `status` = '1' WHERE (`user1` = '%s' AND `user2` = '%s') OR (`user1` = '%s' AND `user2` = '%s')", $this->db->real_escape_string($this->id), $this->db->real_escape_string($profile['idu']), $this->db->real_escape_string($profile['idu']), $this->db->real_escape_string($this->id)));

	// 					// If user has emails on new friendships enabled
	// 					if ($profile['email_new_friend']) {
	// 						// Send e-mail
	// 						sendMail($profile['email'], sprintf($LNG['ttl_friendship_confirmed_email'], $this->username), sprintf($LNG['friendship_confirmed_email'], realName($profile['username'], $profile['first_name'], $profile['last_name']), permalink($this->url . '/index.php?a=profile&u=' . $this->username), $this->username, $this->title, $this->title, permalink($this->url . '/index.php?a=settings&b=notifications')), $this->email);
	// 					}

	// 					$updateNotification = $this->db->query(sprintf("UPDATE `notifications` SET `type` = '5', `read` = '0', `to` = '%s', `from` = '%s' WHERE `from` = '%s' AND `to` = '%s' AND `type` = 4", $this->db->real_escape_string($profile['idu']), $this->db->real_escape_string($this->id), $this->db->real_escape_string($profile['idu']), $this->db->real_escape_string($this->id)));

	// 					// If the approve was done from the notifications widget
	// 					if ($type == 2) {
	// 						return '<div class="notification-button button-normal"><a href="' . permalink($this->url . '/index.php?a=profile&u=' . $profile['username']) . '" target="_blank">' . $LNG['confirmed'] . '</a></div>';
	// 					}
	// 				}
	// 			}
	// 			// If there are no friendship relations
	// 			else {
	// 				$currFriends = $this->countFriends($this->id, 1);
	// 				$targetFriends = $this->countFriends($profile['idu'], 1);

	// 				// If the user & the target has less than the maximum amount
	// 				if ($currFriends < $this->friends_limit || $targetFriends < $this->friends_limit) {
	// 					// If the user is not blocked
	// 					if (!$this->getBlocked($profile['idu'], 2)) {
	// 						$result = $this->db->query(sprintf("INSERT INTO `friendships` (`user1`, `user2`, `time`) VALUES ('%s', '%s', CURRENT_TIMESTAMP)", $this->db->real_escape_string($this->id), $this->db->real_escape_string($profile['idu'])));

	// 						$insertNotification = $this->db->query(sprintf("INSERT INTO `notifications` (`from`, `to`, `type`, `read`) VALUES ('%s', '%s', '4', '0')", $this->db->real_escape_string($this->id), $profile['idu']));

	// 						if ($this->email_new_friend) {
	// 							// If user has emails on new friendships enabled
	// 							if ($profile['email_new_friend']) {
	// 								// Send e-mail
	// 								sendMail($profile['email'], sprintf($LNG['ttl_new_friend_email'], $this->username), sprintf($LNG['new_friend_email'], realName($profile['username'], $profile['first_name'], $profile['last_name']), permalink($this->url . '/index.php?a=profile&u=' . $this->username), $this->username, $this->title, $this->title, permalink($this->url . '/index.php?a=settings&b=notifications')), $this->email);
	// 							}
	// 						}
	// 					}
	// 				}
	// 			}
	// 		}
	// 	} else {
	// 		return false;
	// 	}

	// 	$friendship = $this->verifyFriendship($this->id, $this->db->real_escape_string($profile['idu']));

	// 	if ($friendship['status'] == '1') {
	// 		return '<div class="friend-button friend-remove' . $style . '" title="' . $LNG['remove_friend'] . '" onclick="friend(' . $profile['idu'] . ', 1' . (($z == 1) ? ', 1' : '') . ')"></div>';
	// 	} elseif ($friendship['status'] == '0') {
	// 		return '<div class="friend-button friend-pending' . $style . '" title="' . (($this->id == $friendship['from']) ? $LNG['friend_request_sent'] : $LNG['friend_request_accept']) . '" onclick="friend(' . $profile['idu'] . ', 1' . (($z == 1) ? ', 1' : '') . ')"></div>';
	// 	} else {
	// 		return '<div class="friend-button' . $style . '" title="' . $LNG['add_friend'] . '" onclick="friend(' . $profile['idu'] . ', 1' . (($z == 1) ? ', 1' : '') . ')"></div>';
	// 	}
	// }
}
