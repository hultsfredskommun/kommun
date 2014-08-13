<?php

/* do the tele search */
function hk_get_tele_search($host, $user, $pwd, $db, $search, $num_hits = -1) {
	// error check
	if ($host != "" && $user != "" && $db != "" && $pwd != "" && $select != "")
		return array(array("error" => 'Kan inte kontakta teledatabasen utan r&auml;tt uppgifter.'));
	if (!function_exists("mssql_connect"))
		return array(array("error" => 'Det m&aring;ste finnas mssql i PHP f&ouml;r s&ouml;ka i teledatabasen.'));
	
	// try to connect
	$link = mssql_connect($host, $user, $pwd);
	if (!$link) {
		return array(array("error" => 'Kunde inte kontakta teledatabasen. Fel: ' . mysql_error()));
	}

	mssql_select_db($db);
	
	//fix encoding
	$search = mb_convert_encoding($search, "ISO-8859-1");
	$search = explode(" ",$search);
	
	// do the search with and
	$select = "SELECT * FROM telesok WHERE ";
	foreach ($search as $s) {
		if (trim($s) != "") {
			$select .= "( firstname LIKE '%$s%' OR " .
				"lastname LIKE '%$s%' OR " .
				"title LIKE '%$s%' OR " .
				"organisation LIKE '%$s%' OR " .
				"email LIKE '%$s%' OR " .
				"phone LIKE '%$s%' ) AND ";
		}
	}
	
	$select .= " 1 = 1 ORDER BY CAST([lastname] AS NVARCHAR(4000))";

	$count = 1;
	$result = mssql_query($select);

	// try search with or if no result found
	if (!$result || mssql_num_rows($result) == 0) {
		$select = "SELECT * FROM telesok WHERE ";
		foreach ($search as $s) {
			if (trim($s) != "") {
				$select .= "firstname LIKE '%$s%' OR " .
					"lastname LIKE '%$s%' OR " .
					"title LIKE '%$s%' OR " .
					"organisation LIKE '%$s%' OR " .
					"email LIKE '%$s%' OR " .
					"phone LIKE '%$s%' OR ";
			}
		}		
		$select .= " 1 = 0 ORDER BY CAST([lastname] AS NVARCHAR(4000))";
		$result = mssql_query($select);
	}
	
	// check result
	if (!$result || mssql_num_rows($result) == 0) {
		$items[] = array("name" => 'none');
	}
	else
    {	
		// make array to return
		while ($row = mssql_fetch_assoc($result)) {
			if ($num_hits > 0 && $num_hits < $count++)
				break;
			//$items[] = array("name" => $row["name"], "title" => $row["title"], "workplace" => $row["workplace"], "phone" => $row["phone"], "mail" => $row["mail"], "phonetime" => $row["phonetime"], "postaddress" => $row["postaddress"], "visitaddress" => $row["visitaddress"]);  
			$items[] = array("name" => $row["firstname"] . " " . $row["lastname"], "title" => $row["title"], "workplace" => $row["organisation"], "phone" => $row["phone"], "mail" => $row["email"], "postaddress" => $row["post_address"], "visitaddress" => $row["visit_address"]);  
		}
		if ($num_hits > 0 && $num_hits < $count-1)
			$items[] = array("name" => 'more');
	}	
	mssql_close($link);
	
	return $items;
}

/* add tele search in ajax dropdown */
function hk_ajax_search_function($search) {
	$options = get_option("hk_theme");
	$hits = hk_get_tele_search($options["tele_db_host"], $options["tele_db_user"], $options["tele_db_pwd"], $options["tele_db_db"], $search, 15);
	
	$count = 10;
	if (!empty($_REQUEST["numtele"]))
		$count = $_REQUEST["numtele"];
		
	// echo if hits found
	if (count($hits) > 0 && $hits[0]["name"] != "none") :
		echo "<div class='js-toggle-search-wrapper'>";
		$tele_title = "Telefonnummer";
		if ($options["tele_title"] != "") {
			$tele_title = $options["tele_title"];
		}
		if ($hits[0]["error"] != "") {
			$num_text = " (fel)";
		}
		else if ($hits[count($hits)-1]["name"] == "more") {
			$num_text = " ( &gt; " . (count($hits) - 1) . ")";
		}
		else {
			$num_text = " (" . count($hits) . ")";
		}
		echo "<div class='search-title js-toggle-search-hook'>$tele_title$num_text</div>";
		foreach($hits as $hit) {
			echo "<div class='contact-area'>";
			if (!empty($hit["error"])) {
				echo "<div class='search-item'><span class='error'>" . $hit["error"] . "</span></div>";
			}
			if (!empty($hit["name"])) {
				// echo link if more is found
				if ($hit["name"] == "more") {
					//echo "<li><a href='/?s=$search'>S&ouml;k efter fler kontakter</a></li>";
					//echo "<div class='search-item'>Det finns fler tr&auml;ffar. F&ouml;rfina din s&ouml;kning om du inte hittar r&auml;tt.</div>";
					echo "<div class='search-item'><a href='/?s=$search&numtele=1000'>Visa fler tr&auml;ffar...</a></div>";
				}
				if ($hit["name"] == "none") {
					//echo "<li><a href='/?s=$search'>S&ouml;k efter fler kontakter</a></li>";
					echo "<div>Hittade inga telefonnummer.</div>";
				}
				// echo the hit
				else {
					$name = htmlentities($hit["name"]);
					$title = htmlentities($hit["title"]);
					$workplace = htmlentities($hit["workplace"]);
					$phone = htmlentities($hit["phone"]);
					$mail = htmlentities($hit["mail"]);
					echo "<div class='entry-wrapper contact-wrapper search-item'><div class='entry-content'>";
					echo "<h3 class='entry-title visible'><span class='contactlink'>$name</span></h3>";
					echo "<div class='type-hk_kontakter status-publish hentry'>";
					echo (!empty($title))?"<div class='hk_contact_titel visible'>$title</div>":"";
					echo (!empty($workplace))?"<div class='hk_contact_workplaces visible'>$workplace</div>":"";
					echo "<div class='topspace'>";
					echo (!empty($mail))?"<div class='hk_contact_emails visible'><a href='mailto:$mail'>$mail</a></div>":"";
					echo (!empty($phone))?"<div class='hk_contact_phones visible'>$phone</div>":"";
					echo "</div></div></div></div>";
				}
			}
			echo "</div>";
		}
		echo "</div>";
	elseif (false) : //OLD LOOK
		echo "<ul class='search-tele js-toggle-search-wrapper'>";
		echo "<li class='search-title js-toggle-search-hook'>Telefonnummer</li>";
		foreach($hits as $hit) {
			if (!empty($hit["error"])) {
				echo "<li class='search-item'><span class='error'>" . $hit["error"] . "</span></li>";
			}
			if (!empty($hit["name"])) {
				// echo link if more is found
				if ($hit["name"] == "more") {
					//echo "<li><a href='/?s=$search'>S&ouml;k efter fler kontakter</a></li>";
					echo "<li>Det finns fler tr&auml;ffar. F&ouml;rfina din s&ouml;kning om du inte hittar r&auml;tt.</li>";
				}
				if ($hit["name"] == "none") {
					//echo "<li><a href='/?s=$search'>S&ouml;k efter fler kontakter</a></li>";
					echo "<li>Hittade inga telefonnummer.</li>";
				}
				// echo the hit
				else {
					echo "<li class='search-item'><span class='name'>" . htmlentities($hit["name"]) . "</span> ";
					echo (!empty($hit["title"]))?"<span class='title'>" . htmlentities($hit["title"]) . "</span> ":"";
					echo (!empty($hit["workplace"]))?"<span class='workplace'>" . htmlentities($hit["workplace"]) . "</span> ":"";
					echo (!empty($hit["phone"]))?"<span class='phone'><a href='tel:" . htmlentities($hit["phone"]) . "'>" . htmlentities($hit["phone"]) . "</a></span>":"";
					echo (!empty($hit["mail"]))?"<span class='mail'><a href='mailto:" . htmlentities($hit["mail"]) . "'>" . htmlentities($hit["mail"]) . "</a></span>":"";
					echo "</li>";
				}
			}
		}
		echo "</ul>";
	else:
		echo "Inga tr&auml;ffar i teles&ouml;ning.";
	endif;
}
add_action('hk_post_ajax_search','hk_ajax_search_function',1);
add_action('hk_pre_search','hk_ajax_search_function',1);
/* add tele search in search */
/*
function hk_pre_search_function($search) {
	$options = get_option("hk_theme");
	$count = 10;
	if (!empty($_REQUEST["numtele"]))
		$count = $_REQUEST["numtele"];
	$hits = hk_get_tele_search($options["tele_db_host"], $options["tele_db_user"], $options["tele_db_pwd"], $options["tele_db_db"], $search, $count);
	
	// echo if hits found
	if (count($hits) > 0) :
		echo "<div class='js-toggle-search-wrapper'>";
		echo "<ul class='search-tele'>";
		if ($hits[0]["name"] == "none" || $hits[0]["error"] != "") {
			$num_text = " (0)";
		}
		else if ($hits[count($hits)-1]["name"] == "more") {
			$num_text = " ( &gt; " . (count($hits) - 1) . ")";
		}
		else {
			$num_text = " (" . count($hits) . ")";
		}
		echo "<li class='search-title'><h3 class='entry-title js-toggle-search-hook'>Telefonnummer$num_text</h3></li>";
		foreach($hits as $hit) {
			// echo link if more is found
			if ($hit["name"] == "more") {
				//echo "<li><span class='more'>Det finns fler kontakter, &auml;ndra din s&ouml;kning om du inte hittar kontakten du s&ouml;ker.</span></li>";
				echo "<li class='search-item'><a href='/?s=$search&numtele=1000'>S&ouml;k efter alla kontakter</a></li>";
			}
			else if ($hit["name"] == "none") {
				echo "<li>Hittade inga kontakter</li>";
			}
			else if ($hit["error"] != "") {
				echo "<li class='search-item'>" . $hit["error"] . "</li>";
			}
			// echo the hit
			else {

				echo "<li class='search-item'><span class='name'>" . htmlentities($hit["name"]) . "</span> ";
				foreach(array("title","workplace","phone","mail") as $item) {
					if ($hit[$item] != "") :
						echo "<span class='$item'>";
						$pre = '';
						if ($item == 'phone')
							$pre = 'tel:';
						elseif ($item == 'mail')
							$pre = 'mailto:';
						if ($pre != '')
							echo "<a href='$pre" . htmlentities($hit[$item]) . "'>";	
						echo htmlentities($hit[$item]);
						if ($pre != '')
							echo "</a>";

						echo "</span> ";
					endif;
					
				}
				echo "</li>";
			}
		}
		echo "</ul>";
		echo "</div>";
	endif;
	
}
add_action('hk_pre_search','hk_pre_search_function',1);
*/
/*
function hk_custom_js() { ?>
    <script type="text/javascript">
	
	jQuery(document).ready(function($) {
		
	});
</script>
<?php
}
// Add hook for front-end <head></head>
add_action('wp_head', 'hk_custom_js');
*/

/* add tele search database options */
function hk_option_function($options) { ?>
	<h3>Inst&auml;llningar fr&aring;n barntema</h3>
	<p><label for="hk_theme[tele_db_host]">Tele server</label><br/><input size="80" type="text" name="hk_theme[tele_db_host]" value="<?php echo $options['tele_db_host']; ?>" /></p>
	<p><label for="hk_theme[tele_db_user]">Tele anv&auml;ndare</label><br/><input size="80" type="text" name="hk_theme[tele_db_user]" value="<?php echo $options['tele_db_user']; ?>" /></p>
	<p><label for="hk_theme[tele_db_pwd]">Tele l&ouml;senord</label><br/><input size="80" type="text" name="hk_theme[tele_db_pwd]" value="<?php echo $options['tele_db_pwd']; ?>" /></p>
	<p><label for="hk_theme[tele_db_db]">Tele databas</label><br/><input size="80" type="text" name="hk_theme[tele_db_db]" value="<?php echo $options['tele_db_db']; ?>" /></p>
	<p><label for="hk_theme[tele_title]">Rubrik</label><br/><input size="80" type="text" name="hk_theme[tele_title]" value="<?php echo $options['tele_title']; ?>" /></p>
	
<?php }
add_action('hk_options_hook','hk_option_function', 1);

?>
