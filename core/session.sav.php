<?


	class Session{

		function __construct(){
			global $file;
			if (isset($_COOKIE["s"])){
				$this->session = preg_replace("@[^\w]+@", "", $_COOKIE["s"]);
				if ($object = $file->object("session/$this->session")){
					foreach ($object as $key=>$value){
						$this->$key = $value;
					}
					return;
				}
			} else{
				$this->logout();
			}
			$this->connectionTime = 0;
			$this->session = "";
			$this->role = "";
			$this->id = 0;
			return;
		}

		function login($email=false, $password=false){
			global $file;
			$email = dbl_slashes($email);
			if ($user = mssql_single_assoc(to_iso("SELECT numelevpot, [mot de passe], nom, prenom FROM eleves_tous WHERE email = '$email'"))){
				$user = array_map("to_utf", $user);
				if ($user["mot de passe"] == $password){
					$this->connectionTime = time();
					$this->role = "candidat";
					$this->id = $user["numelevpot"];
					$this->fullName = ucfirst($user["prenom"]) . " " . ucfirst($user["nom"]);
					$this->session = hash("sha512", "$this->role $this->id MotDePasseTest&12/09.1985");
					setcookie("s", $this->session, time()+60*60*24*365.25, "/");
					$file->object("session/$this->session", $this);
					return "";
				}
				return "password";
			}
			return "email";
		}

		function logout(){
			setcookie("s", "", time()+60*60*24*365.25, "/");
		}

		function addLoginboxTo(&$element){
			global $file;
			$div = $element->append("div")->class("session");
			if ($this->id){
				//
				$p = $div->append("p");
				$p->appendText("Vous êtes connecté en tant que ");
				$p->append("strong")->text($this->fullName);
				$p->appendText(".");
				//
				$div->append("form")->name("connexion")->append("input")
					->type("submit")->class("submit")->value("Déconnexion");
				//
				$div->append("a")->href("/$this->role")->text("Accès à mon profil");
			} else{
				$form = $div->append("form")->name("connexion")->method("post");
				$form->append("input")->type("text")->name("email")->title("Adresse email");
				$form->append("input")->type("password")->name("password")->title("Mot de passe");
				$form->append("input")->type("hidden")->name("role")->value("candidat");
				$form->append("input")->type("submit")->class("submit")->value("Connexion");
			}
		}
	};


?>