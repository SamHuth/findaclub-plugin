<?php


function custom_dashboard_help() {
	echo '
	<h3><b>Latest</b></h3>
    <p>Working on integrating new Sports and Clubs.</p>
	<hr />
	<br />
	<h3><b>Clubs</b></h3>
	<ul>
		<li><b>Clubs have the following custom fields</b>
		<ul>
			<li>address</li>
			<li>bio</li>
			<li>about</li>
			<li>coach email</li>
			<li>coach name</li>
			<li>coach phone</li>
			<li>coach title</li>
			<li>phone</li>
			<li>email</li>
			<li>website</li>
			<li>is_premium</li>
			<li>training</li>
			<li>training days</li>
		</ul>
		</li>
		<br />
		<b>Age/Group filter fields - add a 1 in the value</b>
		<ul>
			<li>play_Mens</li>
			<li>play_Mens_Community/Metro</li>
			<li>play_Mens_Masters</li>
			<li>play_Womens</li>
			<li>play_Womens_Community/Metro</li>
			<li>play_Womens_Masters</li>
			<li>play_Juniors_Academy</li>
			<li>play_Juniors_Community</li>
			<li>play_Coach</li>
		</ul>
	</li>
	<hr />
	<br />
		<li>
		<h3><b>Premium Clubs</b></h3>
	<ul>
		<li>Boosted to top of results</li>
		<li>Purple badges for info</li>
		<li>Positions and Coach info available</li>
	</ul>
	<hr />
	<br />
		<li>
		<h3><b>Top Division Clubs</b></h3>
	<ul>
		<li>Need to have top_league set to 1</li>
		<li>Prioritises them in results above lower leagues</li>
	</ul>
	</li>
	</ul>
	&nbsp;
	';
}

function my_custom_dashboard_widgets() {
	global $wp_meta_boxes;
	// Register your custom WordPress admin dashboard widget
	wp_add_dashboard_widget('custom_help_widget', 'WELCOME | findaclub.com.au', 'custom_dashboard_help');
}

add_action('wp_dashboard_setup', 'my_custom_dashboard_widgets');

