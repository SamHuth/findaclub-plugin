<?php

// // Register Custom Taxonomies for Custom Post Type Club
function findaclub_register_club_taxonomies()
{

	// Sport Type
	register_taxonomy('sport', 'club', array(
		'labels' => array(
			'name' 				=> _x('Sports - Choose 1', 'taxonomy general name'),
			'singular_name' 	=> _x('Sport', 'taxonomy singular name'),
			'search_items' 		=> __('Search Sports'),
			'all_items' 		=> __('All Sports'),
			'parent_item' 		=> __('Parent Sport'),
			'parent_item_colon'	=> __('Parent Sport:'),
			'edit_item' 		=> __('Edit Sport'),
			'update_item' 		=> __('Update Sport'),
			'add_new_item' 		=> __('Add New Sport'),
			'new_item_name' 	=> __('New Sport Name'),
			'menu_name' 		=> __('Sports'),
		),
	));
	// State
	register_taxonomy('state', 'club', array(
		'labels' => array(
			'name' 				=> _x('States - Choose - 1', 'taxonomy general name'),
			'singular_name' 	=> _x('State', 'taxonomy singular name'),
			'search_items' 		=> __('Search States'),
			'all_items' 		=> __('All States'),
			'parent_item' 		=> __('Parent State'),
			'parent_item_colon'	=> __('Parent State:'),
			'edit_item' 		=> __('Edit State'),
			'update_item' 		=> __('Update State'),
			'add_new_item' 		=> __('Add New State'),
			'new_item_name' 	=> __('New State Name'),
			'menu_name' 		=> __('States'),

		),
	));
	// Region
	register_taxonomy('region', 'club', array(
		'labels' => array(
			'name' 				=> _x('Regions', 'taxonomy general name'),
			'singular_name' 	=> _x('Region', 'taxonomy singular name'),
			'search_items' 		=> __('Search Regions'),
			'all_items' 		=> __('All Regions'),
			'parent_item' 		=> __('Parent Region'),
			'parent_item_colon'	=> __('Parent Region:'),
			'edit_item' 		=> __('Edit Region'),
			'update_item' 		=> __('Update Region'),
			'add_new_item' 		=> __('Add New Region'),
			'new_item_name' 	=> __('New Region Name'),
			'menu_name' 		=> __('Regions'),

		),
	));
	// League
	register_taxonomy('league', 'club', array(
		'labels' => array(
			'name' 				=> _x('Leagues', 'taxonomy general name'),
			'singular_name' 	=> _x('League', 'taxonomy singular name'),
			'search_items' 		=> __('Search Leagues'),
			'all_items' 		=> __('All Leagues'),
			'parent_item' 		=> __('Parent League'),
			'parent_item_colon'	=> __('Parent League:'),
			'edit_item' 		=> __('Edit League'),
			'update_item' 		=> __('Update League'),
			'add_new_item' 		=> __('Add New League'),
			'new_item_name' 	=> __('New League Name'),
			'menu_name' 		=> __('Leagues'),

		),
	));
}
add_action('init', 'findaclub_register_club_taxonomies', 0);

// Register Custom Post Type => CLUB
function findaclub_register_custom_post_types()
{
	register_post_type(
		'club',
		array(
			'labels'				=> array(
				'name'          		=> __('Clubs', 'textdomain'),
				'singular_name' 		=> __('Club', 'textdomain'),
				'add_new'				=> __('Add Club', 'textdomain'),
				'add_new_item'			=> __('Add New Club', 'textdomain'),
				'edit_item' 			=> __('Edit Club', 'textdomain'),
				'new_item' 				=> __('New Club', 'textdomain'),
				'view_item' 			=> __('View Club', 'textdomain'),
				'view_items'			=> __('View Clubs', 'textdomain'),
				'search_items'			=> __('Search Clubs', 'textdomain'),
				'not_found'				=> __('No Clubs Found', 'textdomain'),
				'not_found_in_trash'	=> __('No Clubs', 'textdomain'),
			),
			'public'      			=> true,
			'publicly_queryable'	=> true,
			'has_archive'			=> true,
			'hierarchical' 			=> true,
			'menu_icon'				=> 'dashicons-admin-users',
			'supports' 				=> array('title', 'editor', 'thumbnail', 'custom-fields'),
			'taxonomies' 			=> array('sport', 'state', 'region', 'league'),
			'rewrite' 				=> array('slug' => '%club_slug%'),
		)
	);
}
add_action('init', 'findaclub_register_custom_post_types', 0);

// Register custom urls for taxonomy based items
function findaclub_register_rewrite_rules()
{

	// Get terms
	$sport_tax_terms = get_terms(array(
		'taxonomy' => 'sport',
		'hide_empty' => false,
	));

	$state_tax_terms = get_terms(array(
		'taxonomy' => 'state',
		'hide_empty' => false,
	));

	if (!empty($sport_tax_terms) && !empty($state_tax_terms)) {
		foreach ($sport_tax_terms as $sport_term) {
			foreach ($state_tax_terms as $state_term) {

				add_rewrite_rule(
					'^' . $sport_term->slug . '/' . $state_term->slug . '/(.*)/?$',
					'index.php?post_type=club&name=$matches[1]',
					'top'
				);
			}
		}
	}
}
add_action('init', 'findaclub_register_rewrite_rules');

function new_sort_order($query)
{

	if ((($query->is_main_query() && (is_tax('sport') || is_tax('state') || is_tax('league') || is_tax('region'))) || $query->is_search()) && !is_admin()) {

		$meta_query_args = array(
			'relation' => 'AND',
		);

		$meta_query_play_filters = array();

		// Add premium filter if applied
		if (isset($_GET['positions']) && $_GET['positions'] === 'open') {
			array_push(
				$meta_query_args,
				array(
					'key' => 'is_premium',
					'value' => '1',
					'compare' => '='
				)
			);
		}

		// Check for play option filters
		$play_options = [
			'Mens',
			'Mens_Community/Metro',
			'Mens_Masters',
			'Womens',
			'Womens_Community/Metro',
			'Womens_Masters',
			'Juniors_Academy',
			'Juniors_Community',
			'Coach'
		];

		foreach ($play_options as $option) {
			if (isset($_GET[$option]) && $_GET[$option] === '1') {

				array_push(
					$meta_query_play_filters,
					array(
						'key' => 'play_' . $option,
						'value' => '1',
						'compare' => '='
					),
				);
			}
		}

		if (count($meta_query_play_filters) > 0) {
			$meta_query_play_filters = ['relation' => 'OR', ...$meta_query_play_filters];
			array_push($meta_query_args, $meta_query_play_filters);
		}

		$query->set('meta_query', $meta_query_args);


		// Set the meta_key
		$query->set('meta_key', 'is_premium');

		// Set the orderby
		$query->set('orderby', array(
			'is_premium' => 'DESC',
			'title' => 'ASC',
		));


		// Set posts per page
		$query->set('posts_per_page', 27);
	}
};
add_action('pre_get_posts', 'new_sort_order');


// Enable formdata for contact-form7 functionality
function clubs_add_edit_form_multipart_encoding()
{
	echo ' enctype="multipart/form-data"';
}
add_action('post_edit_form_tag', 'clubs_add_edit_form_multipart_encoding');



function clubs_update_post($post_id, $post)
{

	// Get the post type. Since this function will run for ALL post saves (no matter what post type), we need to know this.
	// It's also important to note that the save_post action can runs multiple times on every post save, so you need to check and make sure the
	// post type in the passed object isn't "revision"
	$post_type = $post->post_type;

	// Make sure our flag is in there, otherwise it's an autosave and we should bail.
	if ($post_id && isset($_POST['clubs_manual_save_flag'])) {

		// Logic to handle specific post types
		switch ($post_type) {

			// If this is a post. You can change this case to reflect your custom post slug
			case 'club':

				// Do content update for positions content
				$club_positions = $_POST['club_positions'];
				update_post_meta($post_id, '_clubs_positions_content', $club_positions);


				$clear_image = $_POST['clearImage'];

				if ($clear_image === 'true') {

					// Remove linked image
					update_post_meta($post_id, '_clubs_attached_image', null);
				} else {
					// HANDLE THE FILE UPLOAD

					// If the upload field has a file in it
					if (isset($_FILES['clubs_image']) && ($_FILES['clubs_image']['size'] > 0)) {

						// Get the type of the uploaded file. This is returned as "type/extension"
						$arr_file_type = wp_check_filetype(basename($_FILES['clubs_image']['name']));
						$uploaded_file_type = $arr_file_type['type'];

						// Set an array containing a list of acceptable formats
						$allowed_file_types = array('image/jpg', 'image/jpeg', 'image/gif', 'image/png');

						// If the uploaded file is the right format
						if (in_array($uploaded_file_type, $allowed_file_types)) {

							// Options array for the wp_handle_upload function. 'test_upload' => false
							$upload_overrides = array('test_form' => false);

							// Handle the upload using WP's wp_handle_upload function. Takes the posted file and an options array
							$uploaded_file = wp_handle_upload($_FILES['clubs_image'], $upload_overrides);

							// If the wp_handle_upload call returned a local path for the image
							if (isset($uploaded_file['file'])) {

								// The wp_insert_attachment function needs the literal system path, which was passed back from wp_handle_upload
								$file_name_and_location = $uploaded_file['file'];

								// Generate a title for the image that'll be used in the media library
								$file_title_for_media_library = 'Cover Photo';

								// Set up options array to add this file as an attachment
								$attachment = array(
									'post_mime_type' => $uploaded_file_type,
									'post_title' => 'Uploaded image ' . addslashes($file_title_for_media_library),
									'post_content' => '',
									'post_status' => 'inherit'
								);

								// Run the wp_insert_attachment function. This adds the file to the media library and generates the thumbnails. If you wanted to attch this image to a post, you could pass the post id as a third param and it'd magically happen.
								$attach_id = wp_insert_attachment($attachment, $file_name_and_location);
								require_once(ABSPATH . "wp-admin" . '/includes/image.php');
								$attach_data = wp_generate_attachment_metadata($attach_id, $file_name_and_location);
								wp_update_attachment_metadata($attach_id,  $attach_data);

								// Before we update the post meta, trash any previously uploaded image for this post.
								// You might not want this behavior, depending on how you're using the uploaded images.
								$existing_uploaded_image = (int) get_post_meta($post_id, '_clubs_attached_image', true);
								if (is_numeric($existing_uploaded_image)) {
									wp_delete_attachment($existing_uploaded_image);
								}

								// Now, update the post meta to associate the new image with the post
								update_post_meta($post_id, '_clubs_attached_image', $attach_id);

								// Set the feedback flag to false, since the upload was successful
								$upload_feedback = false;
							} else { // wp_handle_upload returned some kind of error. the return does contain error details, so you can use it here if you want.

								$upload_feedback = 'There was a problem with your upload.';
								update_post_meta($post_id, '_clubs_attached_image', $attach_id);
							}
						} else { // wrong file type

							$upload_feedback = 'Please upload only image files (jpg, gif or png).';
							update_post_meta($post_id, '_clubs_attached_image', $attach_id);
						}
					} else { // No file was passed

						$upload_feedback = '';
					}

					// Update the post meta with any feedback
					update_post_meta($post_id, '_clubs_attached_image_upload_feedback', $upload_feedback);
				}


				break;

			default:
		} // End switch

		return;
	} // End if manual save flag

	return;
}
add_action('save_post', 'clubs_update_post', 1, 2);



function clubs_render_positions_box($post)
{

	$existing_content = get_post_meta($post->ID, '_clubs_positions_content', true);

	$args = array(
		'media_buttons' => false, // This setting removes the media button.
		'textarea_name' => 'club_positions', // Set custom name.
		'textarea_rows' => get_option('default_post_edit_rows', 10), //Determine the number of rows.
	);

	wp_editor($existing_content, 'custom-editor', $args);
}

function clubs_render_image_attachment_box($post)
{

	// See if there's an existing image. (We're associating images with posts by saving the image's 'attachment id' as a post meta value)
	// Incidentally, this is also how you'd find any uploaded files for display on the frontend.
	$existing_image_id = get_post_meta($post->ID, '_clubs_attached_image', true);
	if (is_numeric($existing_image_id)) {

		echo '<div>';
		$arr_existing_image = wp_get_attachment_image_src($existing_image_id, 'large');
		$existing_image_url = $arr_existing_image[0];
		echo '<img src="' . $existing_image_url . '" />';
		echo '</div>';
	}



	echo 'Upload an image: <input type="file" name="clubs_image" id="clubs_image" />';

	if ($existing_image_id) {
		echo '<label><br /><input type="checkbox" id="clearImage" name="clearImage" value="true"> Clear Image<br/><small><i>Sorry not enough time to make this fancy. Check the box and press update to remove image.</i></small></label>';
	}


	// See if there's a status message to display (we're using this to show errors during the upload process, though we should probably be using the WP_error class)
	$status_message = get_post_meta($post->ID, '_clubs_attached_image_upload_feedback', true);

	// Show an error message if there is one
	if ($status_message) {

		echo '<div class="upload_status_message">';
		echo $status_message;
		echo '</div>';
	}

	// Put in a hidden flag. This helps differentiate between manual saves and auto-saves (in auto-saves, the file wouldn't be passed).
	echo '<input type="hidden" name="clubs_manual_save_flag" value="true" />';
}

function clubs_setup_meta_boxes()
{
	// Add the box to a particular custom content type page
	add_meta_box('clubs_image_box', 'Custom Banner Image', 'clubs_render_image_attachment_box', 'club', 'normal', 'high');
	add_meta_box('clubs_positions_box', 'Club Positions', 'clubs_render_positions_box', 'club', 'normal', 'high');
}

add_action('admin_init', 'clubs_setup_meta_boxes');



$fac_club_taxonomies = array('sport', 'state', 'region', 'league');

foreach ($fac_club_taxonomies as $fac_tax) {
	add_action("{$fac_tax}_add_form_fields",  'fac_taxonomy_wysiwyg_add_field');
	add_action("{$fac_tax}_edit_form_fields", 'fac_taxonomy_wysiwyg_edit_field', 10, 2);
	add_action("created_{$fac_tax}",          'fac_taxonomy_wysiwyg_save', 10, 2);
	add_action("edited_{$fac_tax}",           'fac_taxonomy_wysiwyg_save', 10, 2);
}

function fac_taxonomy_wysiwyg_add_field($taxonomy)
{
?>
	<div class="form-field">
		<label for="fac_term_wysiwyg"><?php _e('Content (Rich Text)', 'understrap-child'); ?></label>
		<?php
		wp_editor('', 'fac_term_wysiwyg', array(
			'textarea_name' => 'fac_term_wysiwyg',
			'textarea_rows' => 10,
			'media_buttons' => true,
		));
		?>
	</div>
<?php
}

function fac_taxonomy_wysiwyg_edit_field($term, $taxonomy)
{
	$value = get_term_meta($term->term_id, 'fac_term_wysiwyg', true);
?>
	<tr class="form-field">
		<th scope="row">
			<label for="fac_term_wysiwyg"><?php _e('Content (Rich Text)', 'understrap-child'); ?></label>
		</th>
		<td>
			<?php
			wp_editor($value ?: '', 'fac_term_wysiwyg', array(
				'textarea_name' => 'fac_term_wysiwyg',
				'textarea_rows' => 10,
				'media_buttons' => true,
			));
			?>
		</td>
	</tr>
<?php
}

function fac_taxonomy_wysiwyg_save($term_id)
{
	if (isset($_POST['fac_term_wysiwyg'])) {
		$content = current_user_can('unfiltered_html') ? wp_unslash($_POST['fac_term_wysiwyg']) : wp_kses_post($_POST['fac_term_wysiwyg']);
		update_term_meta($term_id, 'fac_term_wysiwyg', $content);
	}
}
