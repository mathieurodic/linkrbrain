<?

	$fontname = "Ubuntu-LI";
	
	
	
	require "makefont/makefont.php";
	MakeFont("./font/ubuntu/$fontname.ttf", "./font/ubuntu/$fontname.afm", "ISO-8859-15");
	
	rename("./$fontname.php", "./font/$fontname.php");
	rename("./$fontname.z", "./font/$fontname.z");

?>