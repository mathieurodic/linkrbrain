<?

	function to_url($text){
		return trim(preg_replace("@[^\w\-\+\/]+@", "-", remove_lower_accents(strtolower($text))), "-");
	}
	
	// error_reporting(0);
	error_reporting(E_ALL);
	set_time_limit(30);
	require_once "db.php";
	

	$url = explode("/", ltrim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/") . "//");
	
	require_once "file.php";
	$file = new File(__DIR__);
	
	$session = $file->core("session");
	$http = $file->core("http");
	
	header("Content-Type: $http->contentType");
	echo $http->render();
	

?>