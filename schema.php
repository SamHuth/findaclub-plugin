<?php

/*
 * JSON-LD for single clubs — schema.org/SportsClub, plus a BreadcrumbList.
 *
 * Ported from the v2 theme's custom-templates/club-schema.php and extended. It
 * lives in the plugin because structured data is SEO, and the findaclub-v4
 * theme keeps SEO out of the theme (see its context/SITE.md). Printed in
 * wp_head, so it does not depend on which theme is active.
 *
 * Output is one @graph with two nodes:
 *
 * - SportsClub: name, url, description, logo, image, email, telephone, sameAs,
 *   sport, a full PostalAddress, geo coordinates, a contactPoint, the league it
 *   plays in (memberOf, with the league archive's URL) and, for premium clubs,
 *   its coach (member). The coach is premium-only because the page only shows
 *   the coach to premium clubs, and structured data should match the page.
 * - BreadcrumbList: Home › sport page › sport-state page › club, the same trail
 *   the theme shows.
 *
 * Fields are read under their new names first (coach_name) and the old ones as
 * fallbacks (coachname, coach name), until every site has run the "Normalise
 * club field names" tool (tools.php).
 */

defined('ABSPATH') || exit;

/*
 * First non-empty value among a club's meta keys, preferred spelling first.
 */
function fac_schema_meta($post_id, $keys)
{
	foreach ((array) $keys as $key) {
		$value = get_post_meta($post_id, $key, true);

		if (is_scalar($value) && '' !== trim((string) $value)) {
			return trim((string) $value);
		}
	}

	return '';
}

/*
 * A club's first term in a taxonomy, or null.
 */
function fac_schema_first_term($post_id, $taxonomy)
{
	$terms = get_the_terms($post_id, $taxonomy);

	return (is_array($terms) && !empty($terms)) ? $terms[0] : null;
}

/*
 * Australian state and territory names, keyed by the abbreviation schema.org
 * examples use for addressRegion.
 */
function fac_schema_states()
{
	return array(
		'NSW' => 'New South Wales',
		'VIC' => 'Victoria',
		'QLD' => 'Queensland',
		'WA'  => 'Western Australia',
		'SA'  => 'South Australia',
		'TAS' => 'Tasmania',
		'ACT' => 'Australian Capital Territory',
		'NT'  => 'Northern Territory',
	);
}

/*
 * Splits a free-text club address into PostalAddress parts.
 *
 * Addresses are typed by hand, so they vary: "201 Boundary St, Spring Hill QLD
 * 4000, Australia", "40 Ward Street, Eudunda, South Australia, 5374" and
 * "Kurri Kurri NSW 2327" all occur. This finds the postcode (the last four
 * digits) and the state (abbreviation or full name), takes the suburb from the
 * same comma-separated part or the one before it, and treats whatever comes
 * before that as the street. Anything it cannot place is left out rather than
 * guessed.
 *
 * Returns streetAddress, addressLocality, addressRegion and postalCode, each
 * possibly empty.
 */
function fac_schema_parse_address($raw)
{
	$parts = array(
		'streetAddress'   => '',
		'addressLocality' => '',
		'addressRegion'   => '',
		'postalCode'      => '',
	);

	$raw = trim(preg_replace('/,?\s*Australia\s*$/i', '', (string) $raw));

	if ('' === $raw) {
		return $parts;
	}

	if (preg_match('/\b(\d{4})\b(?!.*\b\d{4}\b)/', $raw, $match)) {
		$parts['postalCode'] = $match[1];
	}

	/*
	 * State: abbreviations first, in capitals, anywhere. Full names only in the
	 * last two comma-separated parts, where a state goes — otherwise "12
	 * Victoria St, Brisbane QLD 4000" would read as Victoria.
	 */
	foreach (fac_schema_states() as $abbr => $name) {
		if (preg_match('/\b' . $abbr . '\b/', $raw)) {
			$parts['addressRegion'] = $abbr;
			break;
		}
	}

	if ('' === $parts['addressRegion']) {
		$tail = implode(',', array_slice(explode(',', $raw), -2));

		foreach (fac_schema_states() as $abbr => $name) {
			if (preg_match('/\b' . preg_quote($name, '/') . '\b/i', $tail)) {
				$parts['addressRegion'] = $abbr;
				break;
			}
		}
	}

	// Remove the state and postcode, then read the comma-separated remainder.
	$rest = $raw;

	if ($parts['postalCode']) {
		$rest = preg_replace('/\b' . $parts['postalCode'] . '\b/', '', $rest);
	}

	if ($parts['addressRegion']) {
		$states = fac_schema_states();
		$rest   = preg_replace('/\b(' . $parts['addressRegion'] . '|' . preg_quote($states[$parts['addressRegion']], '/') . ')\b/i', '', $rest);
	}

	$segments = array_values(array_filter(array_map('trim', explode(',', $rest)), 'strlen'));

	if (empty($segments)) {
		return $parts;
	}

	// The suburb is the last segment left; any before it make up the street.
	$parts['addressLocality'] = ucwords(strtolower(array_pop($segments)));

	if (!empty($segments)) {
		$parts['streetAddress'] = implode(', ', $segments);
	}

	return $parts;
}

/*
 * The page for a sport, or a sport in a state — published pages slugged after
 * the terms, as the theme pairs them. Returns array(name, url) or null.
 */
function fac_schema_page_crumb($path)
{
	$page = get_page_by_path($path);

	if (!$page || 'publish' !== $page->post_status) {
		return null;
	}

	return array(
		'name' => get_the_title($page),
		'url'  => get_permalink($page),
	);
}

/*
 * A description for every club.
 *
 * The bio field, else the excerpt — v2's order — else, since most clubs have
 * neither, a sentence built from what the listing does hold: "Kangaroo Point
 * Rovers is a football club in Kangaroo Point, QLD, playing in FQPL 4 Metro."
 */
function fac_schema_description($post_id, $sport, $locality, $state, $league)
{
	$bio = fac_schema_meta($post_id, array('bio'));

	if ('' !== $bio) {
		return wp_strip_all_tags($bio);
	}

	$excerpt = trim(wp_strip_all_tags(get_the_excerpt($post_id)));

	if ('' !== $excerpt) {
		return $excerpt;
	}

	$sentence = get_the_title($post_id) . ' is a ' . ($sport ? strtolower($sport) . ' ' : 'sports ') . 'club';

	if ($locality && $state) {
		$sentence .= ' in ' . $locality . ', ' . $state;
	} elseif ($locality || $state) {
		$sentence .= ' in ' . ($locality ?: $state);
	}

	if ($league) {
		$sentence .= ', playing in ' . $league;
	}

	return $sentence . '.';
}

function fac_club_schema()
{
	if (!is_singular('club')) {
		return;
	}

	$post_id = get_queried_object_id();
	$url     = get_permalink($post_id);

	$sport_term  = fac_schema_first_term($post_id, 'sport');
	$state_term  = fac_schema_first_term($post_id, 'state');
	$region_term = fac_schema_first_term($post_id, 'region');
	$league_term = fac_schema_first_term($post_id, 'league');

	// Address: parsed from the address field, gaps filled from the terms.
	$address = fac_schema_parse_address(fac_schema_meta($post_id, array('address')));

	if ('' === $address['addressLocality'] && $region_term) {
		$address['addressLocality'] = $region_term->name;
	}

	// The state term exists both as "QLD" and "Queensland"; either way, the abbreviation.
	if ('' === $address['addressRegion'] && $state_term) {
		$abbr = array_search(strtolower($state_term->name), array_map('strtolower', fac_schema_states()), true);
		$address['addressRegion'] = false !== $abbr ? $abbr : strtoupper($state_term->name);
	}

	$club = array(
		'@type' => 'SportsClub',
		'@id'   => $url . '#club',
		'name'  => get_the_title($post_id),
		'url'   => $url,
	);

	$club['description'] = fac_schema_description(
		$post_id,
		$sport_term ? $sport_term->name : '',
		$address['addressLocality'],
		$address['addressRegion'],
		$league_term ? $league_term->name : ''
	);

	// Logo is the featured image; image is the banner.
	$logo = get_the_post_thumbnail_url($post_id, 'large');

	if ($logo) {
		$club['logo'] = $logo;
	}

	$banner_id = (int) get_post_meta($post_id, '_clubs_attached_image', true);
	$image     = $banner_id ? wp_get_attachment_image_url($banner_id, 'full') : '';

	if ($image) {
		$club['image'] = $image;
	}

	$email = fac_schema_meta($post_id, array('email'));
	$phone = fac_schema_meta($post_id, array('phone'));

	if ($email) {
		$club['email'] = $email;
	}

	if ($phone) {
		$club['telephone'] = $phone;
	}

	$website = fac_schema_meta($post_id, array('website'));

	if ($website) {
		$club['sameAs'] = array(preg_match('#^https?://#i', $website) ? $website : 'https://' . $website);
	}

	if ($sport_term) {
		$club['sport'] = $sport_term->name;
	}

	$postal = array('@type' => 'PostalAddress');

	foreach ($address as $property => $value) {
		if ('' !== $value) {
			$postal[$property] = $value;
		}
	}

	$postal['addressCountry'] = 'AU';
	$club['address']          = $postal;

	$latitude  = fac_schema_meta($post_id, array('latitude'));
	$longitude = fac_schema_meta($post_id, array('longitude'));

	if (is_numeric($latitude) && is_numeric($longitude)) {
		$club['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $latitude,
			'longitude' => (float) $longitude,
		);
	}

	if ($email || $phone) {
		$contact = array(
			'@type'       => 'ContactPoint',
			'contactType' => 'general enquiries',
			'areaServed'  => 'AU',
		);

		if ($email) {
			$contact['email'] = $email;
		}

		if ($phone) {
			$contact['telephone'] = $phone;
		}

		$club['contactPoint'] = $contact;
	}

	if ($league_term) {
		$league = array(
			'@type' => 'SportsOrganization',
			'name'  => $league_term->name,
		);

		$league_url = get_term_link($league_term);

		if (!is_wp_error($league_url)) {
			$league['url'] = $league_url;
		}

		$club['memberOf'] = $league;
	}

	// Coach — premium clubs only, matching what the page shows.
	$premium    = '1' === (string) get_post_meta($post_id, 'is_premium', true);
	$coach_name = $premium ? fac_schema_meta($post_id, array('coach_name', 'coachname', 'coach name')) : '';

	if ($coach_name) {
		$coach = array(
			'@type' => 'Person',
			'name'  => $coach_name,
		);

		$fields = array(
			'jobTitle'  => array('coach_title', 'coachtitle', 'coach title'),
			'email'     => array('coach_email', 'coachemail', 'coach email'),
			'telephone' => array('coach_phone', 'coachphone', 'coach phone'),
		);

		foreach ($fields as $property => $keys) {
			$value = fac_schema_meta($post_id, $keys);

			if ($value) {
				$coach[$property] = $value;
			}
		}

		$club['member'] = $coach;
	}

	// Breadcrumbs: Home › sport page › sport-state page › club.
	$crumbs = array(
		array(
			'name' => __('Home', 'findaclub'),
			'url'  => home_url('/'),
		),
	);

	if ($sport_term) {
		$sport_page = fac_schema_page_crumb($sport_term->slug);

		if ($sport_page) {
			$crumbs[] = $sport_page;
		}

		if ($state_term) {
			$state_page = fac_schema_page_crumb($sport_term->slug . '/' . $state_term->slug);

			if ($state_page) {
				$crumbs[] = $state_page;
			}
		}
	}

	$crumbs[] = array(
		'name' => get_the_title($post_id),
		'url'  => $url,
	);

	$items = array();

	foreach ($crumbs as $position => $crumb) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position + 1,
			'name'     => $crumb['name'],
			'item'     => $crumb['url'],
		);
	}

	$graph = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			$club,
			array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $url . '#breadcrumb',
				'itemListElement' => $items,
			),
		),
	);

	echo "\n<script type=\"application/ld+json\">" . wp_json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}
add_action('wp_head', 'fac_club_schema', 20);
