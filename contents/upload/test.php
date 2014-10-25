<style type="text/css">
	.upload p {background:url(/upload.png) center no-repeat;position:fixed;display:table-cell;left:0;top:0;width:100%;height:100%;margin:0}
	html:hover p,body:hover p	{background-color:#EEE}
	.upload form, .upload form *		{opacity:0;background:transparent}
	.upload *	{cursor:pointer}
</style><?

	for ($x=0; $x<100; $x++){
			echo '<form style="right:';
			echo $x;
			echo '%;top:';
			echo $y;
			echo '%" method="post" enctype="multipart/form-data"><fieldset><input name="file" type="file" /></fieldset></form>';
		}
	}
	
?>