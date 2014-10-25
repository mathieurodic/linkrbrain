<?

	class File{
		
		function __construct($rootdir){
			$this->rootdir = $rootdir;
			$this->tempFiles = array();
		}
		function __destruct(){
			foreach ($this->tempFiles as $tempFile){
				unlink($tempFile);
			}
		}
		
		function php($name){
			if (is_file("$this->rootdir/$name.php")){
				require_once "$this->rootdir/$name.php";
				return true;
			}
			return false;
		}
		
		function &ajax($name){
			$this->php("core/ajax");
			if ($this->php("ajax/$name")){
				$className = "Ajax" . ucfirst($name);
				if (class_exists($className)){
					$object = new $className();
					return $object;
				}
			}
			$object = new Ajax();
			return $object;
		}
		function &page($name){
			$this->php("core/page");
			if ($this->php("pages/$name")){
				$className = "Page" . ucfirst($name);
				if (class_exists($className)){
					$object = new $className();
					$object->name = $name;
					return $object;
				}
			}
			if ($name != 404){
				return $this->page("404");
			}
		}
		
		function &core($name){
			$fullpath = "$this->rootdir/core/$name.php";
			if (is_file($fullpath)){
				require_once $fullpath;
				if (class_exists($name)){
					$object = new $name();
					return $object;
				}
			}
			return NULL;
		}
		function &module($name){
			$fullpath = "$this->rootdir/modules/$name.php";
			if (is_file($fullpath)){
				require_once $fullpath;
				if (class_exists($name)){
					$object = new $name();
					return $object;
				}
			}
			return NULL;
		}

		function exec($cmd, $path = __DIR__){
			$process = proc_open
			(	$cmd
			,	array
				(	array("pipe", "r")
				,	array("pipe", "w")
				,	array("pipe", "w")
				)
			,	$pipes
			,	$path
			);
			$return = stream_get_contents($pipes[2]) . stream_get_contents($pipes[1]);
			for ($i=0; $i<3; $i++){
				fclose($pipes[$i]);
			}
			proc_close($process);
			return $return;
		}
		function ssh($host, $user, $password, $port, $command){
			if (!($ssh = ssh2_connect($host, $port))){
				return NULL;
			}
			if(!ssh2_auth_password($ssh, $user, $password)) {
				return NULL;
			}
			if (!($stream = ssh2_exec($ssh, $command))){
				return NULL;
			}
			stream_set_blocking($stream, true);
            while ($buffer = fread($stream, 4096)) {
                $return .= $buffer;
            }
            fclose($stream);
			return $return;
		}
		function post($path, $data=array()){
			// (	"http://zebulon.linkrbrain.org/$path.php"
			return file_get_contents
			(	"http://public.iscpif.fr/~carma/$path.php"
			,	false
			,	stream_context_create(
					array
					(	"http" => array
						(	"header"  => "Content-type: application/x-www-form-urlencoded\r\n"
						,	"method"  => "POST"
						,	"content" => http_build_query($data)
						)
					)
				)
			);
		}
		
		function c($name, $arguments=""){
			$directory = "$this->rootdir/c";
			$executable = "$directory/$name";
			$source = "$executable.cpp";
			if (is_file($source)){
				$recompile = true;
				if (is_file($executable)){
					if (filemtime($source) > filemtime($executable)){
						$recompile = false;
					}
				}
				if ($recompile){
					$this->exec("g++ -I/usr/include/mysql/ -L/usr/lib/mysql/ -lmysqlclient $name.cpp -o $name", $directory);
				}
				return $this->exec("./$name $arguments", $directory);
			}
		}
	
		function html($name){
			$fullpath = "$this->rootdir/contents/$name.html";
			if (is_file($fullpath)){
				return file_get_contents($fullpath);
			}
			return NULL;
		}
		
		function ini($name){
			$fullpath = "$this->rootdir/config/$name.ini";
			if (is_file($fullpath)){
				return parse_ini_file($fullpath, true);
			}
			return NULL;
		}
		function xml($name){
			$fullpath = "$this->rootdir/config/$name.xml";
			if (is_file($fullpath)){
				return simplexml_load_file($fullpath);
			}
			return NULL;
		}
	
		function cache($filename, $data=NULL){
			$filepath = "$this->rootdir/data/cache/$filename";
			if ($data === NULL){
				return is_file($filepath) ? file_get_contents($filepath) : NULL;
			} else{
				if (is_file($filepath)){
					unlink($filepath);
				}
				return file_put_contents($filepath, $data);
			}
		}
		function data($name, $string = NULL){
			$fullpath = "$this->rootdir/data/$name";
			if ($string === NULL){
				return is_file($fullpath) ? file_get_contents($fullpath) : NULL;
			} else{
				$dirpath = dirname($fullpath);
				if (!is_dir($dirpath)){
					mkdir($dirpath, 0755, true);
				}
				if (is_dir($dirpath)){
					return file_put_contents($fullpath, $string) !== NULL;					
				}
			}
			return NULL;
		}
		function object($name, $thing = NULL){
			if ($thing === NULL){
				$string = $this->data($name);
				if ($string !== NULL){
					return unserialize($string);
				} else{
					return NULL;
				}				
			} else{
				return $this->data($name, serialize($thing));
			}
		}
		
		function tmp($txt){
			$tempFile = tempnam("$this->rootdir/data/tmp");
			$this->tempFiles[] = $tempFile;
			return $tempFile;
		}
		
		function upload($name){
			if (is_array($name)){
				foreach ($name as $n){
					$this->upload($n);
				}
			} else{
				if (isset($_FILES[$name])){
					$directory = "$this->rootdir/data/upload/";
					$destination = tempnam($directory, microtime(true));
					unlink($destination);
					$destination .= preg_replace("@^[^\.]+@", "", $_FILES["file"]["name"]);
					return
						move_uploaded_file($_FILES[$name]["tmp_name"], $destination)
					?	$destination
					:	NULL;
				}
			}
		}
	};
	
?>
