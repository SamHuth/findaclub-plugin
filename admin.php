<?php


function custom_dashboard_help()
{
	echo '
	<h3><b>Latest</b></h3>
	<p><b>The new findaclub v4 theme is here</b> &mdash; a full redesign, built for more than one sport.</p>
	<ul>
		<li><b>Multi-sport:</b> football, rugby league and gymnastics, with each sport getting its own page, state pages and club listings. Publish a page with the same slug as a sport to add it to the site.</li>
		<li><b>Club pages:</b> a new profile with the club&rsquo;s banner, logo and key details, a map, training days and coach details for premium clubs, and a contact form that goes straight to the club.</li>
		<li><b>Finding clubs:</b> sport, state and region listings with filters for sport, state, open positions and club name.</li>
		<li><b>League pages:</b> every club in a league, with a map and an &ldquo;open positions only&rdquo; toggle.</li>
		<li><b>New page templates:</b> Sports in Region (choose the leagues to list on the page), Promote Your Club, Join the Player Database and Opportunities.</li>
		<li><b>Behind the scenes:</b> richer search engine data for every club, and a <a href="' . esc_url(admin_url('admin.php?page=fac-tools')) . '">findaclub tools page</a> for one-off maintenance jobs.</li>
	</ul>
	<hr />
	<br />
	<h3><b>Clubs</b></h3>
	<ul>
		<li><b>Clubs have the following custom fields</b>
		<p>Field names are lower case with underscores &mdash; type them exactly as shown.</p>
		<ul>
			<li><code>address</code> &mdash; full street address, e.g. <i>201 Boundary St, Spring Hill QLD 4000</i></li>
			<li><code>latitude</code>, <code>longitude</code> &mdash; the club&rsquo;s map pin</li>
			<li><code>bio</code> &mdash; a short intro shown above the club&rsquo;s content</li>
			<li><code>email</code> &mdash; where the club&rsquo;s contact form sends messages</li>
			<li><code>phone</code></li>
			<li><code>website</code></li>
			<li><code>is_premium</code> &mdash; set to <code>1</code> for a club with open positions</li>
		</ul>
		</li>
		<br />
		<b>Premium clubs only &mdash; shown on the club page when <code>is_premium</code> is 1</b>
		<ul>
			<li><code>coach_name</code></li>
			<li><code>coach_title</code></li>
			<li><code>coach_email</code></li>
			<li><code>coach_phone</code></li>
			<li><code>training_days</code> &mdash; comma separated, e.g. <i>Tuesday, Thursday</i></li>
		</ul>
		<p>The banner image and the Club Positions text are set in their own boxes on the club&rsquo;s edit screen.</p>
		<br />
		<b>Age/Group filter fields - add a 1 in the value</b>
		<ul>
			<li><code>play_mens</code></li>
			<li><code>play_mens_community_metro</code></li>
			<li><code>play_mens_masters</code></li>
			<li><code>play_womens</code></li>
			<li><code>play_womens_community_metro</code></li>
			<li><code>play_womens_masters</code></li>
			<li><code>play_juniors_academy</code></li>
			<li><code>play_juniors_community</code></li>
			<li><code>play_coach</code></li>
		</ul>
		<br />
		<b>Older field names</b>
		<p>Fields such as <code>coach name</code>, <code>coachname</code>, <code>training days</code> and <code>play_Mens</code> are the old spellings. Run <a href="' . esc_url(admin_url('admin.php?page=fac-tools')) . '"><b>findaclub &rsaquo; Normalise club field names</b></a> to convert them. <code>training</code> (yes/no) is only read by the old v2 theme.</p>
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

function my_custom_dashboard_widgets()
{
	global $wp_meta_boxes;
	// Register your custom WordPress admin dashboard widget
	wp_add_dashboard_widget('custom_help_widget', 'About findaclub.com.au', 'custom_dashboard_help');
}

add_action('wp_dashboard_setup', 'my_custom_dashboard_widgets');
