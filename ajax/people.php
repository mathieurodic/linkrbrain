<?

	class AjaxPeople extends Ajax {
	
		function __construct(){
			parent::__construct();
		}
		
		function render(){
            $expediteur = $this->data->email;
            $headers = "From: $expediteur\r\nReply-To: $expediteur\r\n";
            $result = 0;
            foreach (["mathieu@rodic.fr"] as $destinataire) {
                $result += mail(
                    $destinataire,
                    "{linkRbrain} Message sent from the website",
                    $this->data->message,
                    $headers,
                    "-f$expediteur"
                ) ? 1 : 0;
            }
            $this->output = [
                ["selector"=>"form[name=people]", "method"=>"html", "argument"=>"Your message has " . ($result ? "" : "not ") . "been sent."]
            ];
            return parent::render();
        }
        
    };
        
