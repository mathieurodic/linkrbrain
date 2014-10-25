<?

	require_once dirname(__FILE__) . "/../modules/query.php";
	

	class AjaxQuery extends Ajax{
	
		function __construct(){
			parent::__construct();
			//	Action is required
			if (!isset($this->data->action)){
				return;
			}
			
			if ($this->data->action == "new"){
				$query = new Query();
				$this->data->queryId = $query->id;
				// echo mysql_error();
				// print_r($this->data->queryId);
				// die();
			} elseif (isset($this->data->queryId)){
				$query = new Query($this->data->queryId);
			} else{
				return;
			}
			
			if (isset($this->data->groupSubid)){
				//	With a group?
				$group = new Group($this->data->queryId, $this->data->groupSubid);
				$this->group($group);
				$query->lastUpdateTime(true);
			} elseif (isset($this->data->pointsetId)){
				//	A pointset
				$pointset = new Pointset($this->data->pointsetId);
				$this->pointset($pointset);
				$query->lastUpdateTime(true);
			} else{
				//	Or a whole query?
				$this->query($query);
			}
			
			unset($query);
			$query = new Query($this->data->queryId);
			$this->output = $query->data();
		}
		
		function pointset($pointset){
			switch ($this->data->action){
				case "delete":
					$pointset->delete();
					break;
			}
		}
		function group($group){
			switch ($this->data->action){
				case "delete":
					$group->delete();
					break;
				case "update":
					if (isset($this->data->values)){						
						foreach ($this->data->values as $key=>$value){
							$group->$key($value);
						}
					}
					break;
				case "insert":					
					if (isset($this->data->type, $this->data->data)){
						$pointset = $group->addPointsetFromSource($this->data->type, $this->data->data);
					}
					break;
			}
			
		}
		function query($query){
			
			switch ($this->data->action){
				case "refresh":
					$query->lastUpdateTime(true);
					break;
				case "update":
					if (isset($this->data->values)){						
						foreach ($this->data->values as $key=>$value){
							$query->$key($value);
						}
					}
					break;
				case "settings":
					if (isset($this->data->values)){
						$query->settings(
							$this->data->values
						);
					}
					break;
				case "delete":
					$query->delete();
					break;
			}
		}
		
		function render(){
			return parent::render();
		}
	
	};
	


?>