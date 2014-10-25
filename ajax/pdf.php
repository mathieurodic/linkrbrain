<?

	require_once dirname(__FILE__) . "/../modules/query.php";
	require_once dirname(__FILE__) . "/../modules/colorNames.php";
	require_once dirname(__FILE__) . "/../fpdf/fpdf.php";
	
	
	function hsv2rgb($iH, $iS, $iV) {
 
		if($iH < 0)   $iH = 0;   // Hue:
		if($iH > 360) $iH = 360; //   0-360
		if($iS < 0)   $iS = 0;   // Saturation:
		if($iS > 100) $iS = 100; //   0-100
		if($iV < 0)   $iV = 0;   // Lightness:
		if($iV > 100) $iV = 100; //   0-100
 
		$dS = $iS/100.0; // Saturation: 0.0-1.0
		$dV = $iV/100.0; // Lightness:  0.0-1.0
		$dC = $dV*$dS;   // Chroma:	 0.0-1.0
		$dH = $iH/60.0;  // H-Prime:	0.0-6.0
		$dT = $dH;	   // Temp variable
 
		while($dT >= 2.0) $dT -= 2.0; // php modulus does not work with float
		$dX = $dC*(1-abs($dT-1));	 // as used in the Wikipedia link
 
		switch($dH) {
			case($dH >= 0.0 && $dH < 1.0):
				$dR = $dC; $dG = $dX; $dB = 0.0; break;
			case($dH >= 1.0 && $dH < 2.0):
				$dR = $dX; $dG = $dC; $dB = 0.0; break;
			case($dH >= 2.0 && $dH < 3.0):
				$dR = 0.0; $dG = $dC; $dB = $dX; break;
			case($dH >= 3.0 && $dH < 4.0):
				$dR = 0.0; $dG = $dX; $dB = $dC; break;
			case($dH >= 4.0 && $dH < 5.0):
				$dR = $dX; $dG = 0.0; $dB = $dC; break;
			case($dH >= 5.0 && $dH < 6.0):
				$dR = $dC; $dG = 0.0; $dB = $dX; break;
			default:
				$dR = 0.0; $dG = 0.0; $dB = 0.0; break;
		}
 
		$dM  = $dV - $dC;
		$dR += $dM; $dG += $dM; $dB += $dM;
		$dR *= 255; $dG *= 255; $dB *= 255;
 
		return array(round($dR), round($dG), round($dB));
	}
	
	
	
	class PDF extends FPDF {
		
		function __construct(){
			parent::__construct("P", "mm", "A4");
			$this->AliasNbPages();
			$this->Margin = 16;
			$this->SetMargins($this->Margin, 2*$this->Margin, $this->Margin);
			$this->SetAutoPageBreak(true, 1.5 * $this->Margin);
			$this->AddFont("Ubuntu", "", "Ubuntu-L.php");
			$this->AddFont("Ubuntu", "I", "Ubuntu-LI.php");
			$this->AddFont("Ubuntu", "B", "Ubuntu-M.php");
			$this->SetDrawColor(192, 192, 192);
			$this->SetTextColor(0, 0, 0);
		}
		
		function Header(){
			// $this->SetFont('Arial','B',15);
			// $this->Cell(80);
			// $this->Cell(30,10,'Title',1,0,'C');
			// $this->Ln(20);
		}
		function Footer(){
			$pageNo = $this->PageNo();
			$this->SetTextColor(128, 128, 128);
			$this->SetY(-$this->Margin);
			$this->SetFont("Ubuntu", "", 8);
			$this->Cell(0, 8, "Page $pageNo / {nb}", 0, 0, "C");
			$this->Ln(16);
			$this->Cell(0, 8, "Results generated with LinkRBrain - http://linkrbrain.org", 0, 0, "C");
		}
		
		function AddSection($title = "", $description = ""){
			$this->AddPage();
			if (isset($this->index)){
				$this->index[$title] = $this->PageNo();
			}
			$this->SetTextColor(0, 0, 0);
			
			$this->SetFont("Ubuntu", "B", 16);
			$this->SetY($this->Margin);
			$this->Write(8, $title);
			
			$this->Ln(0);
			$this->SetY(2 * $this->Margin);
			
			$re = "@\[c (\d{1,3}),(\d{1,3}),(\d{1,3})](.*?)\[\/c\]@s";
			$regular = preg_split($re, $description);
			preg_match_all($re, $description, $colored, PREG_SET_ORDER);
			foreach ($regular as $i=>$regular_text){
				$this->SetTextColor(0, 0, 0);
				$this->SetFont("Ubuntu", "", 8);
				$this->Write(4, $regular_text);
				if (isset($colored[$i])){
					$colored_element = $colored[$i];
					$this->SetTextColor($colored_element[1], $colored_element[2], $colored_element[3]);
					$this->SetFont("Ubuntu", "B", 8);
					$this->Write(4, $colored_element[4]);
				}
			}
			
			$this->Ln(16);
		}
		function AddTable($header, $data){
			//	Header
			$this->SetFont("Ubuntu", "B", 8);
			$this->SetTextColor(255, 255, 255);
			$this->SetFillColor(128, 128, 128);
			foreach($header as $x=>$column){
				$width = $x ? 40 : 60;
				$align = $x ? "C" : "L";
				$this->Cell($width, 8, $column, 0, 0, $align, true);
			}
			$this->Ln();
			// Data
			$this->SetFont("Ubuntu", "", 8);
			$this->SetTextColor(0, 0, 0);
			foreach($data as $y=>$row){
				$g = $y%2 ? 240 : 255;
				$this->SetFillColor($g, $g, $g);
				foreach($row as $x=>$column){
					$width = $x ? 40 : 60;
					$align = $x ? "C" : "L";
					$this->Cell($width, 8, $column, 0, 0, $align, true);
				}
				$this->Ln();
			}
		}
		
		function IndexInit(){
			$this->AddSection("Index");
			$this->index = array();
		}
		function IndexAddEntry($title, $key=false){
			if (!isset($this->index)){
				return;
			}
			$w1 = $this->w - 2.5 * $this->Margin;
			$w2 = 0.5 * $this->Margin;
			if ($key){
				$this->SetFont("Ubuntu", "", 8);
			} else{
				$this->SetFont("Ubuntu", "B", 8);
				$key = $title;
			}
			$link = $this->AddLink();
			$this->SetLink($link, 0, "{".$key."}");
			$this->Cell($w1, 8, $title, "B", 0, "L", false, $link);
			$this->Cell($w2, 8, "p. {".$key."}", "B", 0, "L", false, $link);
			$this->Ln();
		}
		function IndexReplace(){
			if (!isset($this->index)){
				return;
			}
			foreach ($this->index as $key=>$value){
				$this->pages[1] = str_replace("{".$key."}", $value, $this->pages[1]);
				foreach ($this->links as $l=>$link){
					$this->links[$l][0] = str_replace("{".$key."}", $value, $this->links[$l][0]);
				}
			}
		}
	
		function Output($name = "", $dest = ""){
			$this->IndexReplace();
			parent::Output($name, $dest);
		}
	};
	
	
	class QueryPDF extends PDF {
		
		function __construct($query, $settings){
			$this->referenceCount = 1;
			$this->colorNames = new ColorNames();
			//
			//	Most importantly...
			//
			global $file;
			parent::__construct();
			$this->query = $query;
			$this->queryData = $query->data();
			$this->settings = $settings;
			$this->folder = "$file->rootdir/data/cache";
			//
			//	Index
			//
			$this->IndexInit();
			if ($settings->view){
				$this->IndexAddEntry("3D representation");
			}
			if ($settings->graph){
				$this->IndexAddEntry("Graph");
			}
			if ($settings->correlations){
				$this->IndexAddEntry("Correlations");
			}
			if ($settings->groups){
				$this->IndexAddEntry("Groups");
				foreach ($this->queryData["groups"] as $g=>$group){
					$this->IndexAddEntry($group["title"], "group-$g");
				}
			}
			$this->IndexAddEntry("References");
			$this->IndexAddEntry("Disclaimer");
			//
			//	Pages
			//
			if ($settings->view){
				$this->PageView();
			}
			if ($settings->graph){
				$this->PageGraph();
			}
			if ($settings->correlations){
				$this->PageCorrelations();
			}
			if ($settings->groups){
				$this->PageGroups();
			}
			$this->PageReferences();
			$this->PageDisclaimer();
		}
		
		function ElementReference($authors, $title, $publisher, $link=""){
			//	Encoding conversion
			$authors = mb_convert_encoding($authors, "ISO-8859-15", "UTF-8");
			$title = mb_convert_encoding($title, "ISO-8859-15", "UTF-8");
			$publisher = mb_convert_encoding($publisher, "ISO-8859-15", "UTF-8");
			//	First line
			$this->SetFont("Ubuntu", "B", 8);
			$this->Cell($this->Margin, 0, $this->referenceCount++, 0, 0, "L", false, $link);
			$this->SetFont("Ubuntu", "", 8);
			$this->Cell($this->w - 2 * $this->Margin, 0, $authors, 0, 0, "L", false, $link);
			$this->Ln(4);
			//	Second line
			$this->setX(2 * $this->Margin);
			$this->SetFont("Ubuntu", "I", 8);
			$this->Cell($this->w - 2 * $this->Margin, 0, $title, 0, 0, "L", false, $link);
			$this->Ln(4);
			//	Third line
			$this->setX(2 * $this->Margin);
			$this->SetFont("Ubuntu", "", 8);
			$this->Cell($this->w - 2 * $this->Margin, 0, $publisher, 0, 0, "L", false, $link);
			$this->Ln(8);
		}
		
		function PageGroups(){
			
			$groups = $this->queryData["groups"];
			$last_g = count($groups) - 1;
			
			//
			//	Create corresponding section
			//
			
			$description = "The query consists of ";
			$description .= $last_g ? (count($groups) . "groups") : " one group";
			$description .= ": ";
			foreach ($groups as $g=>$group){
				switch ($g){
					case 0:
						break;
					case $last_g:
						$description .= " and ";
						break;
					default:
						$description .= ", ";
						break;
				}
				$rgb = hsv2rgb(360*$group["hue"], 80, 80);
				$description .= "[c " . implode(",", $rgb) . "]";
				$description .= $group["title"];
				$description .= "[/c]";
			}
			$description .= ".\n\n";
			$this->AddSection("Groups", $description);
			
			//
			//	One section per group
			//
			
			foreach ($groups as $g=>$group){
				//	Title
				if ($g){
					$this->AddPage();
				}
				$this->index["group-$g"] = $this->PageNo();
				$rgb = hsv2rgb(360 * $group["hue"], 80, 80);
				$this->SetFont("Ubuntu", "B", 12);
				$this->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
				$this->Write(8, $group["title"]);
				$this->SetTextColor(0, 0, 0);
				//	Description
				$this->Ln(12);
				$this->SetFont("Ubuntu", "", 8);
				$this->Write(4, "This groups contains the following data:");
				//	Pointsets
				$this->Ln(8);
				$hasTasks = false;
				$hasGenes = false;
				foreach ($group["pointsets"] as $pointset){
					$pointset = new Pointset($pointset["id"]);
					$pointset_text = "- ";
					$data = $pointset->__call("data", array());
					$data = explode(":", $data);
					switch ($data[0]){
						case "presets,task":
							$hasTasks = true;
							preg_match("@^Presets \((.+)\)$@", $pointset->title(), $match);
							$presets_names = $match[1];
							$plural = (strpos($presets_names, ",") !== false);
							$pointset_text .= "Activation peaks of cognitive concept" . ($plural ? "s" : "");
							$pointset_text .= " ";
							$pointset_text .= $presets_names;
							break;
						case "presets,gene":
							$hasGenes = true;
							preg_match("@^Presets \((.+)\)$@", $pointset->title(), $match);
							$presets_names = $match[1];
							$pointset_text .= "Localizations of gene surexpression for ";
							$pointset_text .= $presets_names;
							break;
						case "nifti":
							$pointset_text .= "Activation centers extracted from NIfTI file";
							break;
						case "text":
							$pointset_text .= "Coordinates imported from text file";
							break;
						case "input":
							$pointset_text .= "Coordinates";
							break;
						default:
							break;
					}
					$pointset_text .= " (";
					$pointset_text .= count($pointset->points);
					$pointset_text .= " point" . (count($pointset->points)>1 ? "s" : ""). ")\n";
					$this->Write(8, $pointset_text);
				}
				$this->Write(8, "\n");
				//	Sources
				if ($hasTasks){
					$this->Write(4, "The activation peaks associated with cognitive tasks have been reconstructed from activations picks published in the papers parsed by ACE (https://github.com/neurosynth/ACE/tree/master/ace) and CorTexT (http://www.cortext.net/).");
					$this->Write(8, "\n\n");
				}
				if ($hasGenes){
					$this->Write(4, "The genetic surexpression localization have been extracted from the Allen Foundation human brain atlas (http://www.alleninstitute.org/science/public_resources/atlases/human_atlas.html).");
					$this->Write(8, "\n\n");
				}
				//	Points
				$this->SetFont("Ubuntu", "", 8);
				$this->Ln(8);
				$this->SetFont("Ubuntu", "B", 8);
				$this->SetTextColor(255, 255, 255);
				$this->SetFillColor(128, 128, 128);
				
				$same_value = true;
				foreach ($group["points"] as $p=>$point){
					if ($p > 0  &&  $previous_value != $point["value"]){
						$same_value = false;
						break;
					}
					$previous_value = $point["value"];
				}
				
				$columns = $same_value ? array("x","y","z") : array("x","y","z","weight");
				foreach ($columns as $column){
					$this->Cell(40, 8, $column, 0, 0, "C", true);
				}
				$this->Ln();
				$this->SetFont("Ubuntu", "", 8);
				$this->SetTextColor(0, 0, 0);
				
				$columns = $same_value ? array("x","y","z") : array("x","y","z","value");
				foreach ($group["points"] as $p=>$point){
					$g = $p%2 ? 240 : 255;
					$this->SetFillColor($g, $g, $g);
					foreach ($columns as $key){
						$this->Cell(40, 8, $point[$key], 0, 0, "C", true);
					}
					$this->Ln();
				}
			}
		}
		function PageCorrelations(){
			
			//
			//	Create corresponding section
			//
			
			$types = array();
			foreach ($this->query->pointsets() as $pointset){
				$data = $pointset->__call("data", array());
				$data = explode(":", $data);
				switch ($data[0]){
					case "presets,task":
					case "nifti":
						$types[] = "activated zones";
						break;
					case "presets,gene":
						$types[] = "localizations of gene surexpression";
						break;
					case "text":
					case "input":
						$types[] = "given coordinates";
						break;
					default:
						break;
				}
			}
			switch ($this->queryData["settings"]["correlation"]["type"]){
				case "task":
					$types[] = "activated zones";
					break;
				case "gene":
					$types[] = "localizations of gene surexpression";
					break;
				default:
					break;
			}
			$types = array_unique($types);
			$last_t = count($types) - 1;
			
			$description = "We based the correlations calculations on the overlap between the different ";
			foreach ($types as $t=>$type){
				switch ($t){
					case 0:
						break;
					case $last_t:
						$description .= " and ";
						break;
					default:
						$description .= ", ";
						break;
				}
				$description .= $type;
			}
			$description .= ". In this view, groups can be considered a set of weighted points.\n\n";
			$description .= "Given two groups A and B, we can represent them as such:\n";
			// $description .= mb_convert_encoding("A = { (M[i], Î¼[i]) | i âˆˆ [0, 1] }\n", "ISO-8859-15", "UTF-8");
			$description .= mb_convert_encoding("A = { (M[i], µ[i]) | i in [0, 1] }\n", "ISO-8859-15", "UTF-8");
			$description .= "B = { (N[i], v[i]) | j in [0, 1] }\n\n";
			$description .= "The correlation score between A and B is calculated as such:\n";
			$description .= mb_convert_encoding("s(A, B) = Sum(µ[i] . v[i] . d(M[i], N[i]) / r,  d(M[i], N[j]) < r\n\n", "ISO-8859-15", "UTF-8");
			$description .= "r being the reference radius (10 mm in our case), and d(M[i], N[j]) the euclidian distance between the points M[i] and N[j].\n\n";
			$description .= "The final score is obtained after normalizing by the autocorrelation scores:\n";
			$description .= "S(A, B) = s(A, B) / Sqrt( s(A, A) . s(B, B) )\n\n";
			$description .= "For readability purposes, this result is displayed as multiplied by 100, then rounded to the nearest integer.";
			
			$this->AddSection("Correlations", $description);
			
			//
			//	Table headers
			//
			
			switch ($this->queryData["settings"]["correlation"]["type"]){
				case "task":
					$fullType = "Cognitive task";
					break;
				case "gene":
					$fullType = "Gene";
					break;
				default:
					$fullType = "";
					break;
			}
			$headers = array($fullType, "Overall score");
			foreach ($this->queryData["groups"] as $group){
				$headers[] = $group["title"];
			}
			//	Table data
			$data = array();
			foreach ($this->queryData["correlations"] as $c=>$correlation){
				$row = array(ucfirst($correlation["title"]));
				foreach ($correlation["scores"] as $score){
					$row[] = round(100 * $score);
				}
				$data[] = $row;
			}
			//	Generate table
			$this->AddTable($headers, $data);
		}
		function PageGraph(){
		
			//
			//	Add corresponding section
			//
			
			$type = $this->queryData["settings"]["correlation"]["type"] . "s";
			$groups = $this->queryData["groups"];
			
			$description = "The graph shows the topographical similarities (overlap) between ";
			$last_g = count($groups) - 1;
			foreach ($groups as $g=>$group){
				switch ($g){
					case 0:
						break;
					default:
						$description .= ", ";
						break;
				}
				$rgb = hsv2rgb(360*$group["hue"], 80, 80);
				$description .= "[c " . implode(",", $rgb) . "]";
				$description .= $group["title"];
				$description .= "[/c]";
			}
			$description .= " and $type.\n\n";
			
			$description .= "There are " . (count($groups) + 1) . " kinds of link between nodes:\n";
			foreach ($groups as $g=>$group){
				$description .= "- ";
				$rgb = hsv2rgb(360*$group["hue"], 80, 80);
				$description .= "[c " . implode(",", $rgb) . "]";
				$description .= $this->colorNames->rgb($rgb[0], $rgb[1], $rgb[2]);
				$description .= " links[/c] connect [c " . implode(",", $rgb) . "]";
				$description .= $group["title"];
				$description .= "[/c] with [c 128,128,128]";
				$description .= $type;
				$description .= "[/c]\n";
			}
			$description .= "- ";
			$description .= "[c 128,128,128]Gray[/c] ";
			$description .= "links connect [c 128,128,128]";
			$description .= $type;
			$description .= "[/c] with [c 128,128,128]each other[/c]";
			$this->AddSection("Graph", $description);
			
			//
			//	Add picture
			//
			
			$snapshot = $this->settings->graphSnapshot;
			$fileName = "$this->folder/$snapshot.png";
			$this->Image
			(	$fileName
			,	$this->Margin
			,	$this->GetY()
			,	$this->w - 2 * $this->Margin
			,	$this->w - 2 * $this->Margin
			,	"PNG"
			);
		}
		function PageView(){
			
			//
			//	Add corresponding section
			//
			
			$description = "Representative points of ";
			$groups = $this->queryData["groups"];
			$last_g = count($groups) - 1;
			foreach ($groups as $g=>$group){
				switch ($g){
					case 0:
						break;
					case $last_g:
						$description .= " and ";
						break;
					default:
						$description .= ", ";
						break;
				}
				$rgb = hsv2rgb(360*$group["hue"], 80, 80);
				$description .= "[c " . implode(",", $rgb) . "]";
				$description .= $group["title"];
				$description .= "[/c]";
			}
			$description .= " are mapped on a 3D representation of the human brain cortical surface. The six standard views are displayed below:";
			$this->AddSection("3D representation", $description);
			
			//
			//	Add all pictures
			//
			
			$columns = 2;
			$lineHeight = 8;
			$size = ($this->w - ($columns + 1) * $this->Margin) / $columns;
			$outerSizeX = $size + $this->Margin;
			$outerSizeY = $outerSizeX + $lineHeight;
			$x0 = $this->Margin;
			$y0 = $this->GetY();
			foreach ($this->settings->viewSnapshots as $s=>$snapshot){
				$fileName = "$this->folder/$snapshot->path";
				$title = preg_replace("@^(.+?)((?:\s\(.+)?)$@", "$1 view$2", $snapshot->title);
				$X = $s % $columns;
				$Y = floor($s / $columns);
				$x = $x0 + $X * $outerSizeX;
				$y = $y0 + $Y * $outerSizeY;
				if ($y + $outerSizeY > $this->h){
					$this->AddPage();
					$y = $this->GetY();
					$y0 = $y - $Y * $outerSizeY;
				}
				$this->Image($fileName, $x, $y, $size, $size, "PNG");
				$this->SetXY($x, $y+$size);
				$this->Cell($size, $lineHeight, $title, 0, 0, "C", false);
			}
		}
		function PageReferences(){
			
			$this->AddSection("References", "References below are the papers from the database used in this platform.");
			
			//
			//	Find which articles should be quoted
			//
			
			$publication_genes = false;
			$publication_ids = array();
			foreach ($this->query->pointsets() as $pointset){
				$data = $pointset->__call("data", array());
				$data = explode(":", $data);
				if (preg_match("@^presets,(\w+)$@", $data[0], $match)){
					$preset_type = $match[1];
					switch ($preset_type){
						case "task":
							$rs = mysql_query("
								SELECT
									p.id
								FROM
									preset_publication AS pp
								INNER JOIN
									publication AS p ON p.id = pp.publication_id
								WHERE
									pp.preset_type = 'task'
								AND
									pp.preset_id IN ({$data[1]})
							");
							while ($publication_id = mysql_fetch_value($rs)){
								$publication_ids[] = (int)$publication_id;
							}
							break;
						case "gene":
							$publication_genes = true;
							break;
					}
				}
			}			
			//
			//	Print references
			//
			if ($publication_genes){
				$this->ElementReference
				(	"Hawrylycz MJ, Lein E.S, Guillozet-Bongaarts A.L., Shen E.H, et al."
				,	"An anatomically comprehensive atlas of the adult human brain transcriptome."
				,	"Nature 2012; 489:391-399"
				);
			}
			if ($publication_ids){
				$publication_ids = implode(",", array_unique($publication_ids));
				$rs = mysql_query("
					SELECT
						title
					,	reference
					,	link
					,	authors
					,	reference
					FROM
						publication
					WHERE
						id IN ($publication_ids)
				");
				while ($publication = mysql_fetch_assoc($rs)){
					$this->ElementReference
					(	$publication["authors"]
					,	$publication["title"]
					,	$publication["reference"]
					,	$publication["link"]
					);
				}
			}
		}
		function PageDisclaimer(){
			$this->AddSection("Disclaimer", "The site owner can not be held responsible for non accuracy of the data or results presented in this site. The results from this site are only indicative and can not be considered as a basis for diagnosis.");
		}
		
		function Save($fileName){
			$path = "$this->folder/$fileName";
			parent::Output($path, "F");
		}
	
	};

	

	class AjaxPdf extends Ajax {
	
		function __construct(){
			parent::__construct();
			global $file;
			if (isset($this->data->queryId)){
				$time = microtime(true);
				switch ($this->data->action){
					case "snapshot":
						if (isset($this->data->index, $this->data->png)){
							$dataArray = explode(",", $this->data->png);
							$fileName = "snapshot-{$this->data->queryId}-{$this->data->index}-{$time}.png";
							$file->cache($fileName, base64_decode(str_replace(" ", "+", $dataArray[1])));
							$this->output = array("path" => $fileName);
						}
						break;
					case "graph":
						if (isset($this->data->svg)){
							$svg = new SimpleXMLElement('<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg viewbox="0 0 1024 1024"></svg>');
							$svg->addAttribute("version", "1.1");
							$svg->addAttribute("xmlns", "http://www.w3.org/2000/svg");
							//	Style
							// $style = $defs->addChild("style");
							// $style[0] = '
								// @font-face { 
									// font-family: Ubuntu;
									// font-style: normal;
									// font-weight: 400;
									// src: url(http://carma.dev.rodic.fr/data/fonts/sketches_svg/UbuntuTitle35_light_sketch.svg) format("svg");
								// }
							// ';
							//	Nodes
							foreach ($this->data->svg as $node){
								$svgNode = $svg->addChild($node->type);
								foreach ($node->attrs as $key=>$value){
									if (!$value  ||  $key == "data"){
										continue;
									}
									switch ($key){
										case "path":
											$valueString = "";
											foreach ($value as $element){
												$valueString .= array_shift($element);
												$valueString .= implode(",", $element);
												$valueString .= " ";
											}
											$svgNode->addAttribute("d", $valueString);
											break;
										case "title":
											break;
										case "font":
											$svgNode->addAttribute("style", "font-family:Ubuntu,Arial;font-size:14px");
											break;
										case "text":
											$tspan = $svgNode->addChild("tspan");
											$tspan[0] = preg_replace("@\s+@", " ", $value);
											break;
										default:
											$svgNode->addAttribute($key, $value);
											break;
									}
								}
							}
							//	Save the files
							$fileName = "snapshot-{$this->data->queryId}-{$time}.svg";
							$file->cache($fileName, $svg->asXML());
							$this->output = array("path" => $fileName);
							$fullPath = dirname(__FILE__) . "/../data/cache/$fileName";
							exec("inkscape $fullPath --export-png=$fullPath.png --export-area=0:0:1024:1024");
						}
						break;
					case "pdf":
						if (isset($this->data->settings)){
							global $session;
							$session->activity("query", "export", $this->data->queryId);
							$fileName = "query-{$this->data->queryId}-{$time}.pdf";
							$query = new Query($this->data->queryId);
							$pdf = new QueryPDF($query, $this->data->settings);
							$pdf->Save($fileName);
							$this->output = array("path" => $fileName);
						}
						break;
				}
			}
		}
		
		function render(){
			return parent::render();
		}
		
	};

?>