<?

	require_once dirname(__FILE__) . "/../modules/query.php";
	

	class AjaxQuery extends Ajax{
	
		function __construct(){
			parent::__construct();
			$this->query = $this->group = $this->pointset = NULL;
			if (isset($this->data->queryId)){
				if (preg_match("@\d+@", $this->data->queryId, $match)){
					$this->query = new Query($match[0]);
					if (isset($this->data->groupSubid)){
						if (preg_match("@\d+@", $this->data->groupSubid, $match)){
							$this->group = $this->query->groups[$match[0]];
						}
					}
				}
			}
			if (isset($this->data->pointsetId)){
				if (preg_match("@\d+@", $this->data->pointsetId, $match)){
					$this->pointset = new Pointset($match[0]);
				}
			}
		}
		
		function render(){
			if (!empty($this->pointset)){
			
				if (!empty($this->data->delete)){
					$this->pointset->delete();
				}
				
			} elseif (!empty($this->group)){
			
				if (!empty($this->data->add)){
					$pointset = $this->group->addPointsetFromSource($this->data->type, $this->data->data);
					$groupSubid = $this->group->subid;
					$this->output = array
					(	"pointset"	=>	$pointset->data()
					,	"group"		=>	$this->group->data()
					);
				}
				if (!empty($this->data->update)){
					foreach ($this->data->update as $key=>$value){
						$this->group->$key($value);
					}
					$this->output = $this->data->update;
				}
				
			} elseif (!empty($this->query)){
			
				if (!empty($this->data->groups)){
					if (preg_match("@[1-9]@", $this->data->groups, $match)){
						$this->query->groups($match[0]);
						$this->addCallback(".frame.correlations .title button.selected", "click");
						$this->addCallback(".frame.view .container", "data", array("view-groups" => $this->query->viewGroups()));
						$this->addCallback(".frame.view .title button.selected", "click");
					}
				}
				if (!empty($this->data->title)){
					$this->query->title($this->data->title);
				}
				if (isset($this->data->correlate, $this->data->type)){
					$correlations = $this->query->correlate($this->data->type);
					$this->addCallback(".frame.correlations", "data", array("correlations"=>$correlations));
				}
				if (isset($this->data->graph, $this->data->type)){
					$graph = $this->query->graph($this->data->type);
					$this->addCallback(".frame.graph>.container", "data", $graph);
				}
				
			}
			return parent::render();
		}
	
	};

?>