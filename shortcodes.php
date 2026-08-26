<?php

function clubs_shortcode_function($attributes)
{
	ob_start();

	$sport = $attributes['sport'] ?? '';
	$state = $attributes['state'] ?? '';
	$region = $attributes['region'] ?? '';
	$league = $attributes['league'] ?? '';
	$limit = $attributes['limit'] ?? null;
	$link = $attributes['link'] ?? '';
	$name = $attributes['name'] ?? '';
	$id = $attributes['id'] ?? '';
	$link_text = $attributes['link_text'] ?? 'View More';
	$premium_only = ($attributes['premium_only'] ?? '') === 'true';

	if (!$sport && !$state && !$region && !$league && !$id) {
		return 'No Params Provided';
	}

	$taxonomy_args = array(
		'relation' => 'AND'
	);

	if ($sport) {
		array_push(
			$taxonomy_args,
			array(
				'taxonomy' => 'sport',
				'field' => 'name',
				'terms' => $sport,
			),
		);
	}

	if ($state) {
		array_push(
			$taxonomy_args,
			array(
				'taxonomy' => 'state',
				'field' => 'name',
				'terms' => $state,
			),
		);
	}

	if ($region) {
		array_push(
			$taxonomy_args,
			array(
				'taxonomy' => 'region',
				'field' => 'name',
				'terms' => $region,
			),
		);
	}

	if ($league) {
		array_push(
			$taxonomy_args,
			array(
				'taxonomy' => 'league',
				'field' => 'name',
				'terms' => $league,
			),
		);
	}

	if ($name) {
		array_push(
			$taxonomy_args,
			array(
				'taxonomy' => 'league',
				'field' => 'name',
				'terms' => $league,
			),
		);
	}

	$meta_query_args = array();

	if ($premium_only) {
		array_push(
			$meta_query_args,
			array(
				'key' => 'is_premium',
				'value' => '1',
				'compare' => '='
			)
		);
	}

	$query_args = array(
		'post_type' => 'club',
		'posts_per_page' => $limit,
		'meta_key' => 'is_premium',
		'meta_query' => $meta_query_args,
		'orderby' => array(
			'is_premium' => 'DESC',
			'title' => 'ASC',
		),
		'tax_query' => $taxonomy_args
	);

	if ($id) {
		$query_args['p'] = intval($id);
	}

	$query = new WP_Query(
		$query_args
	);

	if ($query->have_posts()) : ?>
		<div class="container p-0">
			<div class="row">
				<?php while ($query->have_posts()) : $query->the_post(); ?>
					<div class="<?= $limit == '1' ? "col" : "col-12 col-md-6 col-lg-4" ?>">
						<?php
						$existing_image_id = get_post_meta(get_the_ID(), '_clubs_attached_image', true);
						$club_banner_image = wp_get_attachment_image_url($existing_image_id, 'fullsize');
						?>
						<?php get_template_part('custom-templates/card', '', array(
							'type' => '',
							'image_url' => $club_banner_image,
							'link' => get_the_permalink(),
							'title' => get_the_title(),
							'tag' => '',
							'posted' => '',
							'logo' => get_the_post_thumbnail_url(get_the_ID()),
						)); ?>
					</div>
				<?php endwhile; ?>
				<?php if ($link) {
				?>
					<div class="col-12 mt-4">
						<div class="w-100 text-center">
							<?php
							echo $link
								? '<a href="' . $link . '" class="btn-primary btn px-5 py-3 rounded">' . $link_text . '<span class="ms-2 arrow">→</span></a>'
								: '';
							?>
						</div>
					</div>
				<?php
				} ?>
			</div>
		</div>
	<?php endif;

	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode('ClubList', 'clubs_shortcode_function');

function  info_checklist($attributes)
{
	ob_start();

	$checklist_items = [
		$attributes['item_one'] ?? '',
		$attributes['item_two'] ?? '',
		$attributes['item_three'] ?? '',
	];

	?>

	<ul class="pb-5">
		<?php foreach ($checklist_items as $item) {
			if ($item) {
		?>
				<li class=" flex-fill w-100 d-flex gap-2 text-dark mb-2">
					<div class="text-primary">
						<div class="p-1 bg-primary text-white rounded-3">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path
									d="M10.5858 13.4142L7.75735 10.5858L6.34314 12L10.5858 16.2427L17.6568 9.1716L16.2426 7.75739L10.5858 13.4142Z"
									fill="currentColor" />
							</svg>
						</div>
					</div>
					<span class="pt-1">
						<?= $item; ?>
					</span>
				</li>
		<?php
			}
		} ?>
	</ul>

<?php
	return ob_get_clean();
}
add_shortcode('InfoChecklist', 'info_checklist');
