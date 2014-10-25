<?

	class Session {

		function __construct(){
			$userId = preg_replace("@[^\d]+@", "", $this->cookie("u")) ? : 0;
			$sessionId = preg_replace("@[^\d]+@", "", $this->cookie("s")) ? : 0;
			if (!$sessionId){
				// echo "Bad session\n";
				$this->logout();
				return;
			}
			$session = mysql_single_assoc("
				SELECT
					random
				,	HEX(ip) AS ip
				FROM
					session
				WHERE
					id = $sessionId
				AND
					user_id = $userId
			");
			if ($this->cookie("r") != $session["random"]){
				// echo "Bad random\n";
				$this->logout();
				return;
			}
			if (strtoupper($this->ip()) != strtoupper($session["ip"])){
				// echo "Bad ip\n";
				$this->logout();
				return;
			}
		}
		
		function id(){
			return $this->cookie("s") ? : 0;
		}
		function userId(){
			return $this->cookie("u") ? : 0;
		}
		
		function ip(){
			return bin2hex(inet_pton($_SERVER["REMOTE_ADDR"]));
		}
		function ip_localize(){
			$ip = $_SERVER["REMOTE_ADDR"];
			try {
				$localization = json_decode(file_get_contents("http://freegeoip.net/json/$ip"));
			} catch (Exception $e){
				return;
			}
			if ($localization){
				$localization->ip = bin2hex(inet_pton($localization->ip));
				$localization->country_code = str_replace("'", "''", $localization->country_code);
				$localization->country_name = str_replace("'", "''", $localization->country_name);
				$localization->region_code = str_replace("'", "''", $localization->region_code);
				$localization->region_name = str_replace("'", "''", $localization->region_name);
				$localization->city = str_replace("'", "''", $localization->city);
				$localization->latitude = str_replace("'", "''", $localization->latitude);
				$localization->longitude = str_replace("'", "''", $localization->longitude);
				mysql_query("
					REPLACE INTO
						ip
						(	ip
						,	country_code
						,	country_name
						,	region_code
						,	region_name
						,	city
						,	latitude
						,	longitude
						)
					VALUES
						(	UNHEX('{$localization->ip}')
						,	'{$localization->country_code}'
						,	'{$localization->country_name}'
						,	'{$localization->region_code}'
						,	'{$localization->region_name}'
						,	'{$localization->city}'
						,	'{$localization->latitude}'
						,	'{$localization->longitude}'
						)
				");
			}
		}
		
		function dateCookie($time){
			return date("D, d-M-Y H:i:s", $time) . " GMT";
		}
		
		function clearCookies(){
			foreach ($_COOKIE as $key=>$value){
				clearCookie($key);
			}
		}
		function clearCookie($key){
			$key = urlencode($key);
			$expires = $this->dateCookie(1);
			header("Set-Cookie: $key=; expires=$expires", false);
		}
		function cookie($key, $value=null, $time=31536000){
			if ($value === null){
				return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
			} else{
				$key = urlencode($key);
				$value = urlencode($value);
				$expires = $this->dateCookie(time() + $time);
				// echo("Set-Cookie: $key=$value; expires=$expires\n");
				header("Set-Cookie: $key=$value; expires=$expires", false);
			}
		}
		
		function activity($object1_type="", $action="", $object1_id=0, $object2_id=0){
			$session_id = str_replace("@[^\d]+@", "", $this->cookie("s")) ? : 0;
			$object1_type = str_replace("@[^\w]+@", "", $object1_type);
			$action = str_replace("@[^\w]+@", "", $action);
			$object1_id = str_replace("@[^\d]+@", "", $object1_id) ? : 0;
			$object2_id = str_replace("@[^\d]+@", "", $object2_id) ? : 0;
			mysql_query("
				INSERT INTO
					activity
					(	session_id
					,	object1_type
					,	action
					,	object1_id
					,	object2_id
					)
				VALUES
					(	$session_id
					,	'$object1_type'
					,	'$action'
					,	$object1_id
					,	$object2_id
					)
			");
		}
		
		function login($email, $password){
			$email = trim($email);
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
				return array
				(	"status"	=>	false
				,	"data"		=>	"Invalid email address."
				);
			}
			$emailDB = str_replace("'", "''", $email);
			$user = mysql_single_assoc("
				SELECT
					id
				,	password
				FROM
					user
				WHERE
					email = '$emailDB'
			");
			if (!$user){
				return array
				(	"status"	=>	false
				,	"data"		=>	"You don't have an account for this email address."
				);
			}
			if ($user["password"] != $password){
				return array
				(	"status"	=>	false
				,	"data"		=>	"Wrong password."
				);
			}
			//	Finally, if everything is alright:
			$userId = $user["id"];
			$sessionId = $this->cookie("s");
			mysql_query("
				UPDATE
					session
				SET
					user_id = {$userId}
				WHERE
					id = {$sessionId}
			");
			$this->cookie("u", $userId);
			$this->cookie("e", $email);
			$this->activity("user", "login", $userId);
			return array
			(	"status"	=>	true
			,	"data"		=>	(int)$userId
			);
		}
		function logout($message=""){
			$this->activity("user", "logout", $this->cookie("u"));
			$random = str_replace(".", "", mt_rand() * microtime(true) / mt_getrandmax());
			$ip = $this->ip();
			mysql_query("
				INSERT INTO
					session
					(	user_id
					,	random
					,	ip
					)
				VALUES
					(	0
					,	{$random}
					,	UNHEX('{$ip}')
					)
			");
			$this->clearCookie("u");
			$this->cookie("s", mysql_insert_id());
			$this->clearCookie("e");
			$this->cookie("r", $random);
			$this->cookie("t", time());
			$this->activity("session", "login");
			$this->ip_localize();
		}
		
		function create($email, $password1, $password2){
			$email = trim($email);
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
				$this->activity("user", "create");
				return array
				(	"status"	=>	false
				,	"data"		=>	"Invalid email address."
				);
			}
			if ($password1 != $password2){
				$this->activity("user", "create");
				return array
				(	"status"	=>	false
				,	"data"		=>	"The two passwords are not matching."
				);
			}
			if (strlen($password1) < 6){
				$this->activity("user", "create");
				return array
				(	"status"	=>	false
				,	"data"		=>	"Your password must contain at least 6 characters."
				);
			}
			if (strlen($password1) > 32){
				$this->activity("user", "create");
				return array
				(	"status"	=>	false
				,	"data"		=>	"Your password cannot contain more than 32 characters."
				);
			}
			$emailDB = str_replace("'", "''", $email);
			$passwordDB = str_replace("'", "''", $password1);
			mysql_query("
				INSERT INTO
					user
					(	email
					,	email_validity
					,	password
					)
				VALUES
					(	'$emailDB'
					,	'unknown'
					,	'$passwordDB'
					)
			");
			if ($userId = mysql_insert_id()){
				$this->activity("user", "create", $userId);
				$this->login($email, $password1);
				return array
				(	"status"	=>	true
				,	"data"		=>	(int)$userId
				);
			} else{
				$this->activity("user", "create");
				return array
				(	"status"	=>	false
				,	"data"		=>	"You already have an account for this email address."
				);
			}
		}
	
		function resetPassword($email){
			$this->activity("user", "reset");
			$email = trim($email);
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
				return array
				(	"status"	=>	false
				,	"data"		=>	"Invalid email address."
				);
			}
			$emailDB = str_replace("'", "''", $email);
			$userId = mysql_single_value("
				SELECT
					id
				FROM
					user
				WHERE
					email = '$emailDB'
			");
			if (!$userId){
				return array
				(	"status"	=>	false
				,	"data"		=>	"This email address has not yet been registered."
				);
			}
			$password = base64_encode(md5(mt_rand().$email, true));
			$password = preg_replace("@[^\w]+@", "", $password);			
			$password = substr($password, 0, 6);
			mysql_query("
				UPDATE
					user
				SET
					password = '$password'
				WHERE
					id = $userId
			");
			$mailsent = mail
			(	$email
			,	"Your new password"
			,	"<head><title>Your new password</title></head><html><body>Your password has been reset. You can now access your account with the following credentials:<br/><br/>Email: <b>$email</b><br/>Password: <b>$password</b></body></html>"
			,	"From: LinkRBrain <webmaster@linkrbrain.org>\r\nMIME-Version: 1.0\r\nContent-type: text/html; charset=utf-8\r\n"
			);
			if (!$mailsent){
				return array
				(	"status"	=>	false
				,	"data"		=>	'Error while sending an email.'
				);
			}
			return array
			(	"status"	=>	true
			,	"data"		=>	''
			);
		}
		function changePassword($oldPassword, $newPassword1, $newPassword2){
			$this->activity("user", "reset");
			$userId = $this->cookie("u");
			if (!$userId){
				return array
				(	"status"	=>	false
				,	"data"		=>	"You are not logged in."
				);
			}
			$password = mysql_single_value("
				SELECT
					password
				FROM
					user
				WHERE
					id = $userId
			");
			if ($oldPassword != $password){
				return array
				(	"status"	=>	false
				,	"data"		=>	"Wrong password."
				);
			}
			if ($newPassword1 != $newPassword2){
				$this->activity("user", "update");
				return array
				(	"status"	=>	false
				,	"data"		=>	"The new passwords are not matching."
				);
			}
			if (strlen($newPassword1) < 6){
				$this->activity("user", "update");
				return array
				(	"status"	=>	false
				,	"data"		=>	"Your new password must contain at least 6 characters."
				);
			}
			if (strlen($newPassword1) > 32){
				$this->activity("user", "update");
				return array
				(	"status"	=>	false
				,	"data"		=>	"Your new password cannot contain more than 32 characters."
				);
			}
			
			$newPasswordDB = str_replace("'", "''", $newPassword1);
			mysql_query("
				UPDATE
					user
				SET
					password = '$newPassword1'
				WHERE
					id = $userId
			");
			return array
			(	"status"	=>	true
			,	"data"		=>	''
			);
		}
		
	};
	
?>