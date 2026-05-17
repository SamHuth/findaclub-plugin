<?php


// Modify Custom Club Permalink
function findaclub_club_post_link( $post_link, $id = 0 ){

	flush_rewrite_rules();

	// Get Post by ID
    $post = get_post($id);  

    if ( is_object( $post ) ){
		// Get Cats
		$post_sport = wp_get_object_terms( $post->ID, 'sport' );
		$post_state = wp_get_object_terms( $post->ID, 'state' );

		if($post_sport && $post_state){
			// Only Cat
			// Return /category/post-link
			$slug = str_replace( '%club_slug%' , $post_sport[0]->slug . '/' . $post_state[0]->slug, $post_link );
            return $slug;
        } else {
			// No Cat
			// Return /category/post-link => this will be uncategorized
			$slug = str_replace( '%club_slug%' , 'club', $post_link );
            return $slug;
		}
    }
    return $post_link;  
}
add_filter( 'post_type_link', 'findaclub_club_post_link', 1, 3 );


// Add destination email to the club form fields
function custom_shortcode_atts_wpcf7_filter( $out, $pairs, $attrs ) {

	$custom_attributes = ['destination-email', 'destination-club'];

	foreach ($custom_attributes as $custom_attribute) { 
		if ( isset( $attrs[$custom_attribute] ) ) {
			$out[$custom_attribute] = $attrs[$custom_attribute];
		}
	}

	return $out;
}
add_filter( 'shortcode_atts_wpcf7', 'custom_shortcode_atts_wpcf7_filter', 10, 3 );


function clubs_remove_site_title_from_seo_title($use){
	if( get_post_type() === 'club' && !is_archive() ){
		return false;
	}
	return $use;
}
add_filter(	'the_seo_framework_use_title_branding',	'clubs_remove_site_title_from_seo_title', 10, 2 );


function clubs_add_club_location_info_to_meta_description($description, $args) {
	
	if( get_post_type() === 'club' && !is_archive() ){

		
		
		// Get post information
		$post = get_post();
		$club_state_term = get_the_terms( $post->ID, 'state');
		$club_region_term = get_the_terms( $post->ID, 'region');
		$club_sport_term = get_the_terms( $post->ID, 'sport');

		// Set values with default backups
		$sport_name 	= $club_sport_term ? $club_sport_term[0]->name : 'Sporting';
		$region_name	= $club_region_term ? $club_region_term[0]->name : '';
		$state_name 	= $club_state_term ? $club_state_term[0]->name : 'Australia';
		$location_name 	= $region_name ? $region_name . ', '. $state_name : $state_name;

		// Add club name for empty descriptions
		if(!$description){
			$description = $post->post_title;
		}

		// Update Desc
		$description = $description . ' | ' . $sport_name . ' club in ' . $location_name;

		$description = 'View teams and opportunities available at ' . $post->post_title . ', a ' . $sport_name . ' club based in ' . $location_name;
		
	}

	return $description;
}
add_filter('the_seo_framework_custom_field_description', 'clubs_add_club_location_info_to_meta_description', 10, 2);
add_filter('the_seo_framework_generated_description', 'clubs_add_club_location_info_to_meta_description', 10, 2);
add_filter('the_seo_framework_fetched_description_excerpt', 'clubs_add_club_location_info_to_meta_description', 10, 2);

function clubs_add_club_location_info_to_meta_title($title){
	if( get_post_type() === 'club' && !is_archive() ){

		
		
		// Get post information
		$post = get_post();
		$club_state_term = get_the_terms( $post->ID, 'state');
		$club_region_term = get_the_terms( $post->ID, 'region');
		$club_sport_term = get_the_terms( $post->ID, 'sport');

		// Set values with default backups
		$sport_name 	= $club_sport_term ? $club_sport_term[0]->name : 'Sporting';
		$region_name	= $club_region_term ? $club_region_term[0]->name : '';
		$state_name 	= $club_state_term ? $club_state_term[0]->name : 'Australia';
		$location_name 	= $region_name ?: $state_name;

		// Add club name for empty title
		if(!$title){
			$title = $post->post_title;
		}

		// Update Desc
		$title = $title . ' | ' . $sport_name . ' club in ' . $location_name;
		
	}

	return $title;
}

add_filter('the_seo_framework_title_from_custom_field', 'clubs_add_club_location_info_to_meta_title', 10, 2);