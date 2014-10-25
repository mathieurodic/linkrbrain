<?

	require_once "db.php";
	mysql_select_db("carma");
	$id = preg_replace("@[^\d]+@s", "", isset($_GET["id"]) ? $_GET["id"] : 0);
	$id = $id ? : 19;

?>
<html>
	<head>
		<style type="text/css">
			*	{margin:0;border:0}
			#paper,h1	{margin:16px;margin-top:32px}
			#paper	{box-shadow:inset 1px 1px 4px rgba(0,0,0,1)}
		</style>
	</head>
	<body>
		<h1><?
			echo "Query n°$id";
		?></h1>
		<div id="paper"></div>
		<script type="text/javascript" src="/lib.js"></script>
		<script type="text/javascript">var graph=<?
			echo mysql_single_value("SELECT graph FROM query WHERE id = $id");
		?></script>
		<script type="text/javascript" src="test.js"></script>
	</body>
</html>