<?

	require_once dirname(__FILE__) . "/../modules/query.php";
	

	class AjaxQuery extends Ajax{
	
		function __construct(){
			//	Action is required
			if (!isset($this->data->action)){
				return;
			}
			//	Are we dealing with a pointset?
			if (isset($this->data->pointsetId)){
				$this->pointset($this->data->pointsetId);
				return;
			}
			if (isset($this->data->queryId)){
				//	With a group?
				if (isset($this->data->groupSubid)){
					$this->group($this->data->queryId, $this->data->subId);
					return;
				}
				//	Or a whole query?
				$this->query($this->data->queryId);
				return;
			}
		}
		
		function pointset($pointsetId){
			$pointset = new Pointset($pointsetId);
			switch ($this->data->action){
				case "delete":
					$pointset->delete();
					break;
			}
		}
		function group($queryId, $subId){
			$group = new Group($queryId, $subId);
			switch ($this->data->action){
				case "delete":
					$group->delete();
					break;
				case "update":
					if (isset($this->data->values)){						
						foreach ($this->data->values as $key=>$value){
							$group->$key($value);
						}
						$this->output = $this->data->values;
					}
					break;
				case "insert":					
					if (isset($this->data->type, $this->data->type)){
						$pointset = $group->addPointsetFromSource($this->data->type, $this->data->data);
						$this->output = array
						(	"pointset"	=>	$pointset->data()
						,	"group"		=>	$group->data()
						);
					}
					break;
			}
		}
		function query($queryId){
			$query = new Query($queryId);
			
			switch ($this->data->action){
				case "new":
					$query = new Query();
					$this->output = query->data();
					break;
				case "update":
					if (isset($this->data->values)){						
						foreach ($this->data->values as $key=>$value){
							$query->$key($value);
						}
						$this->output = $this->data->values;
					}					
					break;
				case "load":
					$this->output = $query->data();
					break;
				case "correlate":
					$query->correlate();
					$this->output = query->data()->correlations;
					break;
				case "graph":
					$query->graph();
					$this->output = query->data()->graph;
					break;
				case "settings":
					if (isset($this->data->value){
						$query->settings(
							$this->data->value
						);
					}
					$this->output = query->data()->graph;
					break;
			}
		}
		
		function render(){
			return parent::render();
		}
	
	};


?>