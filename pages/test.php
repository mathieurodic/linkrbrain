<?

	class PageTest extends Page{
	
		function __construct(){
			parent::__construct();
		}
		
		function test(){
			if (!($ssh = ssh2_connect("zebulon.iscpif.fr", 22))){
				return NULL;
			}
			if(!ssh2_auth_password($ssh, "carma", "afo7ahPh")) {
				return NULL;
			}
			//	Transfer file
			ssh2_scp_send($connection, '/local/filename', '/iscpif/users/carma/tmp/test.txt', 0644);
			//	Execute program
			if (!($stream = ssh2_exec($ssh, "/iscpif/users/carma/c/graphFromFile /iscpif/users/carma/tmp/test.txt"))){
				return NULL;
			}
			stream_set_blocking($stream, true);
            while ($buffer = fread($stream, 4096)) {
                $return .= $buffer;
            }
            fclose($stream);
			return $return;
		}
		
		function render(){
			//	Do the thing
			$t1 = microtime(true);
			$test = $this->test();
			$t2 = microtime(true);
			//	Display it all!
			$bidule = $this->contents->right;
			$bidule->prepend("h1")->text(($t2 - $t1) * 1000 . " ms");
			$bidule->append("pre")->text(print_r($test, true));
			return parent::render();
		}
	}