<?

	class AjaxConnexion extends Ajax{
	
		function __construct(){
			parent::__construct();
		}
		
		function render(){
			global $session;
			$emailInput = "form[name=connexion] input[name=email]";
			$passwordInput = "form[name=connexion] input[name=password]";
			if (isset($this->data->email, $this->data->password)){
				$result = $session->login($this->data->email, $this->data->password);
				switch ($result){
					case "email":
						$this->addCallback("$emailInput,$passwordInput", "val", "");
						$this->addCallback("$emailInput", "addClass", "wrong");
						$this->addCallback("$emailInput", "focus");
						break;
					case "password":
						$this->addCallback("$passwordInput", "val", "");
						$this->addCallback("$passwordInput", "addClass", "wrong");
						$this->addCallback("$passwordInput", "focus");
						break;
					default:
						$this->addCallback(NULL, "redirect", $_SERVER["HTTP_REFERER"]);
						break;
				}
			} else{
				$session->logout();
				$this->addCallback(NULL, "redirect", $_SERVER["HTTP_REFERER"]);
			}
			return parent::render();
		}
	
	};

?>