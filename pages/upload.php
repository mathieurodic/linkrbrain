<?

	class PageUpload extends Page{
	
		function __construct(){
			parent::__construct();
		}
		
		function render(){
			global $file;
			$this->body
				->clear()->class("upload")
				->appendHtml( $file->html("upload/") );
			if (isset($_FILES["file"])){
				if ($this->path = $file->upload("file")){
					$oldpath_json = json_encode($_FILES["file"]["name"]);
					$path_json = json_encode(preg_replace("@.+/([^/]+)$@", "$1", $this->path));
					$this->addScript("
						var $ = parent.$;
						$('iframe').each(function(i, iframe){
							if (iframe.contentWindow === window){
								iframe = $(iframe);
								var files = iframe.data('files') || [];
								files.push($path_json);
								iframe.data({files:files});
								var ul = iframe.next();
								if (ul.is('ul')){
									var li = $('<li>')
										.text($oldpath_json)
										.data('index', files.length - 1)
										.appendTo(ul);
									$('<button>').prependTo(li).text('x').click(function(){
										var li = $(this).parent();
										var iframe = li.parent().prev();
										var files = iframe.data('files');
										files.splice(li.data('index'), 1);
										li.remove();
									}).title('Remove this element');
								}
							}
						});
					", false);
				}
			}
			return parent::render();
		}
	
	};

?>