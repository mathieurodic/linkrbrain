<?

	class AjaxFeedback extends Ajax{
	
		function __construct(){
			parent::__construct();
			$data = $this->data;
			
			if (isset($data->types, $data->subject, $data->message, $data->rating)){
				
				global $session;
				$session_id = $session->id();
				
				$types = array();
				foreach ($data->types as $type){
					if (in_array($type, array("bug","feature","other"))){
						$types[] = $type;
					}
				}
				$types = implode(",", $types);
				
				$subject = str_replace("'", "''", $data->subject);
				$message = str_replace("'", "''", $data->message);
				$rating = (int)$data->rating;
				
				mysql_query("
					INSERT INTO
						feedback
						(	types
						,	session_id
						,	subject
						,	message
						,	rating
						)
					VALUES
						(	('$types')
						,	$session_id
						,	'$subject'
						,	'$message'
						,	$rating
						)
				");
				
			}
			
		}
		
		function render(){
			return parent::render();
		}
		
	};