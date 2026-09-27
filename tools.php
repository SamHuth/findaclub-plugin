<?php

/*
 * Tools — one-off maintenance jobs, run from the admin.
 *
 * Tools appear under a top-level "findaclub" menu, one card each: a description,
 * a Preview button that reports what would change, and a Run button that does
 * it.
 * Nothing runs on a page load; both buttons are POSTs with a nonce, and only
 * administrators see the page.
 *
 * To add a tool, add an entry to fac_tools_registry() with a callback taking
 * ($dry_run) and returning array('summary' => string, 'lines' => string[]).
 */

defined('ABSPATH') || exit;

const FAC_TOOLS_RESULT_TRANSIENT = 'fac_tools_result_';

function fac_tools_registry()
{
	return array(
		'normalise_club_meta' => array(
			'label'       => __('Normalise club field names', 'findaclub'),
			'description' => __('Renames the club custom fields to one consistent lower_snake_case spelling: coachname and "coach name" both become coach_name, trainingdays becomes training_days, play_Mens becomes play_mens, and so on. Where a club would end up holding the same field twice, the one with a value is kept. Every affected row is written to a JSON backup in uploads first.', 'findaclub'),
			'run_label'   => __('Rename the fields', 'findaclub'),
			'callback'    => 'fac_tool_normalise_club_meta',
		),
	);
}

function fac_tools_menu()
{
	add_menu_page(
		__('findaclub Tools', 'findaclub'),
		__('Find a Club', 'findaclub'),
		'manage_options',
		'fac-tools',
		'fac_tools_render_page',
		'dashicons-flag',
		58
	);
}
add_action('admin_menu', 'fac_tools_menu');

function fac_tools_render_page()
{
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You do not have permission to run these tools.', 'findaclub'));
	}

	$result = get_transient(FAC_TOOLS_RESULT_TRANSIENT . get_current_user_id());

	if ($result) {
		delete_transient(FAC_TOOLS_RESULT_TRANSIENT . get_current_user_id());
	}

?>
	<div class="wrap">
		<h1><?php esc_html_e('findaclub Tools', 'findaclub'); ?></h1>

		<p class="description">
			<?php esc_html_e('One-off maintenance jobs. Preview first: it reports what would change without touching anything.', 'findaclub'); ?>
		</p>

		<?php if ($result) : ?>
			<div class="notice notice-<?php echo esc_attr($result['dry_run'] ? 'info' : 'success'); ?>">
				<p>
					<strong><?php echo esc_html($result['tool']); ?></strong> —
					<?php echo esc_html($result['dry_run'] ? __('preview', 'findaclub') : __('done', 'findaclub')); ?>:
					<?php echo esc_html($result['summary']); ?>
				</p>

				<?php if (!empty($result['lines'])) : ?>
					<ul style="margin:0 0 12px 20px;list-style:disc;">
						<?php foreach ($result['lines'] as $line) : ?>
							<li><code><?php echo esc_html($line); ?></code></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php foreach (fac_tools_registry() as $slug => $tool) : ?>
			<div class="card" style="max-width:820px;padding:16px 20px;margin-top:20px;">
				<h2 style="margin-top:0;"><?php echo esc_html($tool['label']); ?></h2>
				<p><?php echo esc_html($tool['description']); ?></p>

				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
					<input type="hidden" name="action" value="fac_run_tool" />
					<input type="hidden" name="tool" value="<?php echo esc_attr($slug); ?>" />
					<input type="hidden" name="dry_run" value="1" />
					<?php wp_nonce_field('fac_run_tool_' . $slug); ?>
					<?php submit_button(__('Preview', 'findaclub'), 'secondary', 'submit', false); ?>
				</form>

				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;margin-left:8px;"
					onsubmit="return confirm('<?php echo esc_js(__('This changes club data. A backup is written first. Continue?', 'findaclub')); ?>');">
					<input type="hidden" name="action" value="fac_run_tool" />
					<input type="hidden" name="tool" value="<?php echo esc_attr($slug); ?>" />
					<input type="hidden" name="dry_run" value="0" />
					<?php wp_nonce_field('fac_run_tool_' . $slug); ?>
					<?php submit_button($tool['run_label'], 'primary', 'submit', false); ?>
				</form>
			</div>
		<?php endforeach; ?>
	</div>
<?php
}

function fac_tools_handle_post()
{
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You do not have permission to run these tools.', 'findaclub'));
	}

	$slug  = isset($_POST['tool']) ? sanitize_key($_POST['tool']) : '';
	$tools = fac_tools_registry();

	if (!isset($tools[$slug])) {
		wp_die(esc_html__('Unknown tool.', 'findaclub'));
	}

	check_admin_referer('fac_run_tool_' . $slug);

	$dry_run = !empty($_POST['dry_run']);
	$result  = call_user_func($tools[$slug]['callback'], $dry_run);

	set_transient(
		FAC_TOOLS_RESULT_TRANSIENT . get_current_user_id(),
		array(
			'tool'    => $tools[$slug]['label'],
			'dry_run' => $dry_run,
			'summary' => $result['summary'],
			'lines'   => $result['lines'],
		),
		5 * MINUTE_IN_SECONDS
	);

	wp_safe_redirect(admin_url('admin.php?page=fac-tools'));
	exit;
}
add_action('admin_post_fac_run_tool', 'fac_tools_handle_post');

/*
 * The club field names, old spelling => the one to keep.
 *
 * Only spellings that actually need changing; play_womens_masters and
 * play_juniors_community are already right. Comparisons against these are
 * BINARY throughout, because MySQL's default collation is case-insensitive —
 * without it 'play_Mens' matches 'play_mens' and a rename looks like a clash
 * with itself.
 */
function fac_tool_club_meta_map()
{
	return array(
		'coachname'                   => 'coach_name',
		'coach name'                  => 'coach_name',
		'coachemail'                  => 'coach_email',
		'coach email'                 => 'coach_email',
		'coachphone'                  => 'coach_phone',
		'coach phone'                 => 'coach_phone',
		'coachtitle'                  => 'coach_title',
		'coach title'                 => 'coach_title',
		'trainingdays'                => 'training_days',
		'training days'               => 'training_days',
		'play_Mens'                   => 'play_mens',
		'play_mens_communitymetro'    => 'play_mens_community_metro',
		'play_Mens_Masters'           => 'play_mens_masters',
		'play_Womens'                 => 'play_womens',
		'play_womens_communitymetro'  => 'play_womens_community_metro',
		'play_Womens_Community/Metro' => 'play_womens_community_metro',
		'play_Coach'                  => 'play_coach',
		'play_Juniors_Academy'        => 'play_juniors_academy',
	);
}

/*
 * Renames the club custom fields to one consistent spelling.
 *
 * With $dry_run true it only counts. Otherwise it backs up every affected row to
 * uploads/findaclub-backups/ first, then renames, removing the duplicate where a
 * club holds both spellings — keeping whichever has a value.
 */
function fac_tool_normalise_club_meta($dry_run = true)
{
	global $wpdb;

	$map     = fac_tool_club_meta_map();
	$lines   = array();
	$clubs   = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'club'";
	$renamed = 0;
	$removed = 0;
	$backup  = '';

	if (!$dry_run) {
		$backup = fac_tool_backup_club_meta(array_keys($map));

		if (is_wp_error($backup)) {
			return array(
				'summary' => sprintf(
					/* translators: %s: error message. */
					__('Stopped before changing anything: the backup could not be written (%s).', 'findaclub'),
					$backup->get_error_message()
				),
				'lines'   => array(),
			);
		}
	}

	foreach ($map as $old => $new) {
		$rows = (int) $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id IN ({$clubs}) AND BINARY meta_key = %s", $old)
		);

		if (!$rows) {
			continue;
		}

		/*
		 * Clubs holding both spellings. meta_id is compared so a row is never
		 * matched against itself, which a case-only rename would otherwise do.
		 */
		$both = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.post_id, o.meta_value old_value, n.meta_value new_value
				 FROM {$wpdb->postmeta} o
				 JOIN {$wpdb->postmeta} n
				   ON n.post_id = o.post_id AND BINARY n.meta_key = %s AND n.meta_id <> o.meta_id
				 WHERE BINARY o.meta_key = %s AND o.post_id IN ({$clubs})",
				$new,
				$old
			),
			ARRAY_A
		);

		$lines[] = sprintf(
			'%1$s → %2$s: %3$d rows%4$s',
			$old,
			$new,
			$rows,
			$both ? sprintf(', %d already holding %s', count($both), $new) : ''
		);

		if ($dry_run) {
			continue;
		}

		foreach ($both as $row) {
			$drop = '' !== trim((string) $row['new_value']) ? $old : $new;

			$removed += (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND BINARY meta_key = %s",
					(int) $row['post_id'],
					$drop
				)
			);
		}

		$renamed += (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE BINARY meta_key = %s AND post_id IN ({$clubs})",
				$new,
				$old
			)
		);
	}

	if ($dry_run) {
		return array(
			'summary' => sprintf(
				/* translators: %d: number of old field names found. */
				_n('%d old field name found. Nothing has been changed.', '%d old field names found. Nothing has been changed.', count($lines), 'findaclub'),
				count($lines)
			),
			'lines'   => $lines,
		);
	}

	wp_cache_flush();

	$lines[] = sprintf('backup: %s', $backup);

	return array(
		'summary' => sprintf(
			/* translators: 1: rows renamed. 2: duplicate rows removed. */
			__('%1$d rows renamed, %2$d duplicates removed.', 'findaclub'),
			$renamed,
			$removed
		),
		'lines'   => $lines,
	);
}

/*
 * Writes every club meta row for the given keys to a JSON file in uploads.
 *
 * Returns the file path, or WP_Error when it cannot be written — the caller
 * stops rather than changing data with no way back.
 */
function fac_tool_backup_club_meta($keys)
{
	global $wpdb;

	$uploads = wp_upload_dir();

	if (!empty($uploads['error'])) {
		return new WP_Error('fac_uploads', $uploads['error']);
	}

	$dir = trailingslashit($uploads['basedir']) . 'findaclub-backups';

	if (!wp_mkdir_p($dir)) {
		return new WP_Error('fac_mkdir', __('could not create the backup directory', 'findaclub'));
	}

	$placeholders = implode(',', array_fill(0, count($keys), '%s'));

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta}
			 WHERE post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'club')
			 AND BINARY meta_key IN ({$placeholders})",
			$keys
		),
		ARRAY_A
	);

	$file    = trailingslashit($dir) . 'club-meta-' . gmdate('Ymd-His') . '.json';
	$written = file_put_contents($file, wp_json_encode($rows));

	if (false === $written) {
		return new WP_Error('fac_write', __('could not write the backup file', 'findaclub'));
	}

	return $file;
}
