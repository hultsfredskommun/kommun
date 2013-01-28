<?php

/* get tele search hits */
function hk_get_tele_search($host, $user, $pwd, $search, $num_hits = -1) {
	return "";
	if ($host != "" && $user != "" && $pwd != "" && $select != "")
		return array('Kan inte kontakta teledatabasen utan r&auml;tt uppgifter.');
	if (!function_exists("mssql_connect"))
		return array('<ul class="error-message"><li>Det m&aring;ste finnas mssql i PHP f&ouml;r s&ouml;ka i teledatabasen.</li></ul>');
	$link = mssql_connect($host, $user, $pwd);
	if (!$link) {
		return array('<ul class="error-message"><li>Kunde inte kontakta teledatabasen.</li></ul>' . mysql_error());
	}

	/* get hits */
	$select = "SELECT * FROM entire_directory WHERE " .
	"name LIKE '%$search%' " .
	"title LIKE '%$search%' " .
	"workplace LIKE '%$search%' " .
	"mail LIKE '%$search%' " .
	"phone LIKE '%$search%' " .
	"other LIKE '%$search%'";
	
	$result = mysql_query($select);
	$items = array();
	$count = 0;
	while ($row = mssql_fetch_assoc($result, MYSQL_NUM)) {
		if ($num_hits > 0 && $num_hits > $count++)
			break;
		$items[] = array($row["title"], $row["workplace"], $row["phone"]);  
	}
	
	mssql_close($link);
	
	return $items;
}

/* add tele search in ajax dropdown */
function hk_pre_ajax_search_function($search) {
	$options = get_option("hk_theme");
	$hits = hk_get_tele_search($options["tele_db_host"], $options["tele_db_user"], $options["tele_db_pwd"], $search);
	//print_r($hits);
	//echo "F&ouml;re ajax s&ouml;k! S&ouml;kte p&aring; " . $search;
}
add_action('hk_pre_ajax_search','hk_pre_ajax_search_function',1);

/* add tele search in search */
function hk_pre_search_function($search) {
	$options = get_option("hk_theme");
	$hits = hk_get_tele_search($options["tele_db_host"], $options["tele_db_user"], $options["tele_db_pwd"], $search);
	//print_r($hits);
	//echo "F&ouml;re s&ouml;k! S&ouml;kte p&aring; " . $search;
}
add_action('hk_pre_search','hk_pre_search_function',1);

/* add tele search database options */
function hk_option_function($options) { ?>
	<h3>Inst&auml;llningar fr&aring;n barntema</h3>
	<p><label for="hk_theme[tele_db_host]">Tele databas server</label><br/><input size="80" type="text" name="hk_theme[tele_db_host]" value="<?php echo $options['tele_db_host']; ?>" /></p>
	<p><label for="hk_theme[tele_db_user]">Tele databas anv&auml;ndare</label><br/><input size="80" type="text" name="hk_theme[tele_db_user]" value="<?php echo $options['tele_db_user']; ?>" /></p>
	<p><label for="hk_theme[tele_db_pwd]">Tele databas l&ouml;senord</label><br/><input size="80" type="text" name="hk_theme[tele_db_pwd]" value="<?php echo $options['tele_db_pwd']; ?>" /></p>
<?php }
add_action('hk_options_hook','hk_option_function', 1);

?>