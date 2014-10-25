<?

	class ColorNames {

		function __construct(){
			$f = fopen(dirname(__FILE__) . "/colorNames.txt", "r");
			$this->colors = array();
			while ($line = fgets($f)){
				if (preg_match("@^(\w+.+)\s+\#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})@", $line, $match)){
					$this->colors[] = array
					(	"name"	=>	$match[1]
					,	"r"		=>	(int)hexdec($match[2])
					,	"g"		=>	(int)hexdec($match[3])
					,	"b"		=>	(int)hexdec($match[4])
					);
				}
			}
		}
			
		function rgb($r, $g, $b){
			$distance2 = 3 * (255 * 255);
			$name = "";
			foreach ($this->colors as $color){
				$dr = $r - $color["r"];
				$dg = $g - $color["g"];
				$db = $b - $color["b"];
				$d2 = $dr * $dr + $dg * $dg + $db * $db;
				if ($distance2 > $d2){
					$distance2 = $d2;
					$name = $color["name"];
				}
			}
			return $name;
		}

	};

?>