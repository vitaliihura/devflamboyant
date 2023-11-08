
<!-- tpl type -->
<div class="td-meta-box-row">

	<span class="td-page-o-custom-label">Template Type: </span>

	<?php

	$post_id = get_the_id();
	$tdb_template_type = get_post_meta( $post_id, 'tdb_template_type', true );

    ?>

	<div class="td-select-style-overwrite">
		<label for="tdb-mb-template-type">
			<select id="tdb-mb-template-type" name="tdb_template_type" class="td-panel-dropdown">
				<option value="">No template type</option>
				<?php

				$filters = array(
					'header',
					'footer',
					'single',
					'404',
					'attachment',
					'author',
					'category',
					'date',
					'search',
					'tag',
					'module',
                    'cpt',
                    'cpt_tax'
				);
				$filters = apply_filters( 'tdb_template_types', $filters );

				foreach ( $filters as $template_type ) {
					$selected = $tdb_template_type === $template_type ? ' selected="selected"' : '';
					?>
					<option value="<?php echo $template_type ?>"<?php echo $selected; ?>>
						<?php echo ucfirst($template_type) ?>
					</option>
					<?php

				}

				?>
			</select>
		</label>

	</div>

	<span class="td-page-o-info"></span>

</div>