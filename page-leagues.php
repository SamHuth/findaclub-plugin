<?php

/*
 * Leagues picker for the "Sports in Region" page template.
 *
 * Adds a meta box to pages using page-templates/sports-in-region.php where an
 * editor sets the heading over the league list and chooses which leagues that
 * page lists. The heading is stored in _fac_page_leagues_heading and the
 * choice as an array of league term IDs in _fac_page_leagues; the theme reads
 * both (see inc/clubs.php in findaclub-v4).
 *
 * The box only appears for that template, so an ordinary page is not cluttered
 * with it. Switching a page to the template and saving reveals it.
 */

defined('ABSPATH') || exit;

const FAC_PAGE_LEAGUES_META = '_fac_page_leagues';
const FAC_PAGE_LEAGUES_HEADING_META = '_fac_page_leagues_heading';
const FAC_PAGE_LEAGUES_TEMPLATE = 'page-templates/sports-in-region.php';

function fac_page_leagues_register_meta()
{
	register_post_meta('page', FAC_PAGE_LEAGUES_META, array(
		'type'          => 'array',
		'single'        => true,
		'show_in_rest'  => array(
			'schema' => array(
				'items' => array('type' => 'integer'),
			),
		),
		'auth_callback' => function () {
			return current_user_can('edit_pages');
		},
	));
}
add_action('init', 'fac_page_leagues_register_meta');

function fac_page_leagues_register_heading_meta()
{
	register_post_meta('page', FAC_PAGE_LEAGUES_HEADING_META, array(
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => function () {
			return current_user_can('edit_pages');
		},
	));
}
add_action('init', 'fac_page_leagues_register_heading_meta');

function fac_page_leagues_add_meta_box($post_type, $post)
{
	if ('page' !== $post_type) {
		return;
	}

	if (FAC_PAGE_LEAGUES_TEMPLATE !== get_page_template_slug($post)) {
		return;
	}

	add_meta_box(
		'fac_page_leagues',
		__('Leagues to list', 'findaclub'),
		'fac_page_leagues_render_meta_box',
		'page',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'fac_page_leagues_add_meta_box', 10, 2);

function fac_page_leagues_render_meta_box($post)
{
	wp_nonce_field('fac_page_leagues_save', 'fac_page_leagues_nonce');

	// The heading over the league list on the page; the theme falls back to
	// "Local leagues in the area" when this is left empty.
	$heading = (string) get_post_meta($post->ID, FAC_PAGE_LEAGUES_HEADING_META, true);

?>
	<p>
		<label for="fac-page-leagues-heading"><strong><?php esc_html_e('Section heading', 'findaclub'); ?></strong></label>
		<input
			type="text"
			class="widefat"
			id="fac-page-leagues-heading"
			name="<?php echo esc_attr(FAC_PAGE_LEAGUES_HEADING_META); ?>"
			value="<?php echo esc_attr($heading); ?>"
			placeholder="<?php esc_attr_e('Local leagues in the area', 'findaclub'); ?>" />
		<span class="description"><?php esc_html_e('Shown above the leagues. Leave empty to use "Local leagues in the area".', 'findaclub'); ?></span>
	</p>
<?php

	$terms = get_terms(array(
		'taxonomy'   => 'league',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	));

	if (is_wp_error($terms) || empty($terms)) {
		echo '<p>' . esc_html__('No leagues exist yet.', 'findaclub') . '</p>';
		return;
	}

	$selected = fac_get_page_leagues_ids($post->ID);

	// Chosen leagues first, so a long list opens on what is already set.
	usort($terms, function ($a, $b) use ($selected) {
		$a_on = in_array((int) $a->term_id, $selected, true) ? 0 : 1;
		$b_on = in_array((int) $b->term_id, $selected, true) ? 0 : 1;

		return $a_on === $b_on ? strcasecmp($a->name, $b->name) : $a_on - $b_on;
	});

?>
	<p class="description">
		<?php esc_html_e('Tick the leagues this page should list. They appear in the order shown here (alphabetical), and the page shows nothing if none are ticked.', 'findaclub'); ?>
	</p>

	<p>
		<input type="search" class="widefat" id="fac-page-leagues-filter" placeholder="<?php esc_attr_e('Filter leagues…', 'findaclub'); ?>" />
	</p>

	<ul id="fac-page-leagues-list" style="max-height:320px;overflow-y:auto;margin:0;padding:8px;border:1px solid #dcdcde;background:#fff;">
		<?php foreach ($terms as $term) : ?>
			<li style="margin:0 0 4px;">
				<label>
					<input
						type="checkbox"
						name="<?php echo esc_attr(FAC_PAGE_LEAGUES_META); ?>[]"
						value="<?php echo esc_attr($term->term_id); ?>"
						<?php checked(in_array((int) $term->term_id, $selected, true)); ?> />
					<?php echo esc_html($term->name); ?>
					<span style="color:#787c82;">(<?php echo esc_html(number_format_i18n($term->count)); ?>)</span>
				</label>
			</li>
		<?php endforeach; ?>
	</ul>

	<script>
		(function() {
			var filter = document.getElementById('fac-page-leagues-filter');
			var list = document.getElementById('fac-page-leagues-list');

			if (!filter || !list) {
				return;
			}

			filter.addEventListener('input', function() {
				var needle = filter.value.toLowerCase();

				Array.prototype.forEach.call(list.children, function(item) {
					item.style.display = item.textContent.toLowerCase().indexOf(needle) === -1 ? 'none' : '';
				});
			});
		})();
	</script>
<?php
}

function fac_page_leagues_save($post_id, $post)
{
	if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
		return;
	}

	if (!isset($_POST['fac_page_leagues_nonce']) || !wp_verify_nonce($_POST['fac_page_leagues_nonce'], 'fac_page_leagues_save')) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	// Heading first: the league list below returns early when nothing is ticked.
	$heading = isset($_POST[FAC_PAGE_LEAGUES_HEADING_META]) ? sanitize_text_field(wp_unslash($_POST[FAC_PAGE_LEAGUES_HEADING_META])) : '';

	if ('' === $heading) {
		delete_post_meta($post_id, FAC_PAGE_LEAGUES_HEADING_META);
	} else {
		update_post_meta($post_id, FAC_PAGE_LEAGUES_HEADING_META, $heading);
	}

	$submitted = isset($_POST[FAC_PAGE_LEAGUES_META]) ? (array) $_POST[FAC_PAGE_LEAGUES_META] : array();
	$league_ids = array();

	foreach ($submitted as $league_id) {
		$league_id = (int) $league_id;

		if ($league_id && term_exists($league_id, 'league')) {
			$league_ids[] = $league_id;
		}
	}

	if (empty($league_ids)) {
		delete_post_meta($post_id, FAC_PAGE_LEAGUES_META);
		return;
	}

	update_post_meta($post_id, FAC_PAGE_LEAGUES_META, array_values(array_unique($league_ids)));
}
add_action('save_post_page', 'fac_page_leagues_save', 10, 2);

/*
 * The league IDs chosen for a page, as integers. Used by the meta box and
 * available to the theme.
 */
function fac_get_page_leagues_ids($post_id)
{
	$stored = get_post_meta((int) $post_id, FAC_PAGE_LEAGUES_META, true);

	return is_array($stored) ? array_map('intval', $stored) : array();
}
