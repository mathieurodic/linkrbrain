<?

	class AjaxSession extends Ajax{
	
		function __construct(){
			global $session;
			parent::__construct();
			switch ($this->data->action){
				case "create":
					$this->output = $session->create($this->data->email, $this->data->password1, $this->data->password2);
					break;
				case "login":
					$this->output = $session->login($this->data->email, $this->data->password);
					break;
				case "logout":
					$result = $session->logout();
					$this->output = array();
					break;
				case "resetPassword":
					$this->output = $session->resetPassword($this->data->email);
					break;
				case "changePassword":
					$this->output = $session->changePassword($this->data->oldPassword, $this->data->newPassword1, $this->data->newPassword2);
					break;
			}
		}
		
		function render(){
			return parent::render();
		}
		
	};
			
?>