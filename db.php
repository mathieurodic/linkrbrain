<?

	$attempts = 1;
	do {
		$db = mysql_connect("polux.iscpif.fr", "carma", "afo7ahPh");
	} while (!$db && $attempts--);
	
	mysql_query("SET NAMES 'utf8'");
	mysql_select_db("carma_test");

	
	function mysql_fetch_value($rs){
		$r = mysql_fetch_row($rs);
		// echo mysql_error();
		return $r ? $r[0] : $r;
	}
	
	function mysql_single_array($sql){
        return
			($rs = mysql_query($sql))
		?	mysql_fetch_array($rs)
		:	false
		;
    }
	function mysql_single_row($sql){
		return
			($rs = mysql_query($sql))
		?	mysql_fetch_row($rs)
		:	false
		;
    }
    function mysql_single_assoc($sql){
        return  
			($rs = mysql_query($sql))
		?	mysql_fetch_assoc($rs)
		:	false
		;
    }
    function mysql_single_value($sql){
        return
			($r = mysql_single_row($sql))
		?	$r[0]
		:	false
		;
    }

	function mysql_array($sql){
		if ($rs = mysql_query($sql)){
			$array = array();
			while ($r = mysql_fetch_array($rs)){
				$array[] = $r;
			}
			return $array;
		}
		return false;
	}
	function mysql_row($sql){
		if ($rs = mysql_query($sql)){
			$array = array();
			while ($r = mysql_fetch_row($rs)){
				$array[] = $r;
			}
			return $array;
		}
		return false;
	}
	function mysql_assoc($sql){
		if ($rs = mysql_query($sql)){
			$array = array();
			while ($r = mysql_fetch_assoc($rs)){
				$array[] = $r;
			}
			return $array;
		}
		return false;
	}
	
?>
