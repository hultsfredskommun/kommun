<?php

/* do the tele search */
function hk_get_tele_search($host, $user, $pwd, $db, $search, $num_hits = -1) {
	// error check
	if ($host != "" && $user != "" && $db != "" && $pwd != "" && $select != "")
		return array("name" => 'Kan inte kontakta teledatabasen utan r&auml;tt uppgifter.');
	if (!function_exists("mssql_connect"))
		return array("name" => 'Det m&aring;ste finnas mssql i PHP f&ouml;r s&ouml;ka i teledatabasen.');
	
	// try to connect
	$link = mssql_connect($host, $user, $pwd);
	if (!$link) {
		return array('Kunde inte kontakta teledatabasen.', mysql_error(),"","");
	}

	mssql_select_db($db);
	
	// do the search
	$select = "SELECT * FROM entire_directory WHERE " .
	"name LIKE '%$search%' OR " .
	"title LIKE '%$search%' OR " .
	"workplace LIKE '%$search%' OR " .
	"mail LIKE '%$search%' OR " .
	"phone LIKE '%$search%'";
	
	$result = mssql_query($select);
	$count = 1;
	// make array to return
	while ($row = mssql_fetch_assoc($result)) {
		if ($num_hits > 0 && $num_hits < $count++)
			break;
		$items[] = array("name" => $row["name"], "title" => $row["title"], "workplace" => $row["workplace"], "phone" => $row["phone"], "mail" => $row["mail"], "phone" => $row["phone"], "phonetime" => $row["phonetime"], "postaddress" => $row["postaddress"], "visitaddress" => $row["visitaddress"]);  
	}
	if ($num_hits > 0 && $num_hits < $count-1)
		$items[] = array("name" => 'more');
	
	mssql_close($link);
	
	return $items;
}

/* add tele search in ajax dropdown */
function hk_pre_ajax_search_function($search) {
	$options = get_option("hk_theme");
	
	$hits = hk_get_tele_search($options["tele_db_host"], $options["tele_db_user"], $options["tele_db_pwd"], $options["tele_db_db"], $search, 3);
	
	// echo if hits found
	if (count($hits) > 0) :
		echo "<ul class='search-tele'>";
		echo "<li class='search-title'>Kontakter</li>";
		foreach($hits as $hit) {
			// echo link if more is found
			if ($hit["name"] == "more") {
				echo "<li><a href='/?s=$search'>S&ouml;k efter fler kontakter</a></li>";
			}
			// echo the hit
			else {
				echo "<li><span class='name'>" . htmlentities($hit["name"]) . "</span> ";
				echo ($hit["workplace"] != "")?"<span class='workplace'>" . htmlentities($hit["workplace"]) . "</span> ":"";
				echo ($hit["phone"] != "")?"<span class='phone'>" . htmlentities($hit["phone"]) . "</span>":"";
				echo ($hit["mail"] != "")?"<span class='mail'>" . htmlentities($hit["mail"]) . "</span>":"";
				echo "</li>";
			}
		}
		echo "</ul>";
	endif;
}
add_action('hk_pre_ajax_search','hk_pre_ajax_search_function',1);

/* add tele search in search */
function hk_pre_search_function($search) {
	$options = get_option("hk_theme");
	
	$hits = hk_get_tele_search($options["tele_db_host"], $options["tele_db_user"], $options["tele_db_pwd"], $options["tele_db_db"], $search);
	
	// echo if hits found
	if (count($hits) > 0) :
		echo "<ul class='search-tele'>";
		echo "<li class='search-title'><h1 class='entry-title'>Kontakter</h1></li>";
		foreach($hits as $hit) {
			echo "<li><span class='name'>" . htmlentities($hit["name"]) . "</span> ";
			foreach(array("workplace","phone","phonetime","mail", "postaddress", "visitaddress") as $item) {
				echo ($hit[$item] != "")?"<span class='$item'>" . htmlentities($hit[$item]) . "</span> ":"";
			}
			echo "</li>";
		}
		echo "</ul>";
	endif;
}
add_action('hk_pre_search','hk_pre_search_function',1);

/* add tele search database options */
function hk_option_function($options) { ?>
	<h3>Inst&auml;llningar fr&aring;n barntema</h3>
	<p><label for="hk_theme[tele_db_host]">Tele server</label><br/><input size="80" type="text" name="hk_theme[tele_db_host]" value="<?php echo $options['tele_db_host']; ?>" /></p>
	<p><label for="hk_theme[tele_db_user]">Tele anv&auml;ndare</label><br/><input size="80" type="text" name="hk_theme[tele_db_user]" value="<?php echo $options['tele_db_user']; ?>" /></p>
	<p><label for="hk_theme[tele_db_pwd]">Tele l&ouml;senord</label><br/><input size="80" type="text" name="hk_theme[tele_db_pwd]" value="<?php echo $options['tele_db_pwd']; ?>" /></p>
	<p><label for="hk_theme[tele_db_db]">Tele databas</label><br/><input size="80" type="text" name="hk_theme[tele_db_db]" value="<?php echo $options['tele_db_db']; ?>" /></p>
<?php }
add_action('hk_options_hook','hk_option_function', 1);

?>
