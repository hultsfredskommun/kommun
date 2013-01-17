<?php

function hk_pre_ajax_search_function($search) {
	echo "F&ouml;re ajax s&ouml;k! S&ouml;kte p&aring; " . $search;
}
add_action('hk_pre_ajax_search','hk_pre_ajax_search_function',1);

function hk_pre_search_function($search) {
	echo "F&ouml;re s&ouml;k! S&ouml;kte p&aring; " . $search;
}
add_action('hk_pre_search','hk_pre_search_function',1);

?>