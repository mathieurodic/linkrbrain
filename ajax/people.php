<?

	class AjaxPeople extends Ajax {
	
		function __construct(){
			parent::__construct();
		}
		
		function render(){
            $expediteur = $this->data->email;
            $result = mail(
                "mathieu@rodic.fr",
                "{linkRbrain} Message sent from the website",
                nl2br(htmlentities($this->data->message)),
                implode("\r\n", [
                    "From: $expediteur",
                    "CC: salma.mesmoudi@gmail.com"
                    "Reply-To: $expediteur"
                ]),
                "-f$expediteur"
            );
            $this->output = [
                ["selector"=>"form[name=people]", "method"=>"html", "argument"=>$result?"Your message has been sent.":"Your message could not be sent. Please try again later."]
            ];
            return parent::render();
        }
        
    };
        
