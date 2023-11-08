<ul class="woo-new-prod-setting">
	<li>
		<label for="webpushr_woo_new_prod_title">Notification Title</label>
		<input name="webpushr_woo_new_prod_title" id="webpushr_woo_new_prod_title emojifield" class="required-field webpushr_13fw3_textfield emojifield" type="text" value="<?php echo get_option('webpushr_woo_new_prod_title');?>" size="100" placeholder=""  required >
		<div class="webpushr_13fw3_info webpushr_13fw3_placeholders">Placeholders<span class="dashicons dashicons-editor-help"></span><p>{product_name}<br />{short_description}<br />{product_category}<br />{regular_price}</p></div>
		<input type="hidden" name="webpushr_new_product" value="1">
	</li>

	<li>
		<label for="webpushr_woo_new_prod_message">Notification Message</label>
		<input type="text" name="webpushr_woo_new_prod_message" required class="lp_textarea required-field webpushr_13fw3_textfield emojifield" value="<?php echo get_option('webpushr_woo_new_prod_message');?>">
		<div class="webpushr_13fw3_info webpushr_13fw3_placeholders">Placeholders<span class="dashicons dashicons-editor-help"></span><p>{product_name}<br />{short_description}<br />{product_category}<br />{regular_price}</p></div>
	</li>

	<li>
		<label for="webpushr_woo_prod_icon">Notification Icon</label>
		<div class="options-wrapper has-custom-option vertical  p-0">
			<div class="custom-field wpp_woo_prod_icon">
				<input name="webpushr_woo_prod_icon" id="wpp_woo_prod_icon" class="upload-field webpushr_13fw3_textfield" placeholder="" type="text" value="<?php echo get_option('webpushr_woo_prod_icon');?>" size="100"><button style="float:right;" class="webpushr_13fw3_btn btn-primary upload-icon" data-label="Icon" data-field="wpp_woo_prod_icon" type="button" id="">Choose Icon</button>
			</div>
		</div>
		<div class="webpushr_13fw3_info webpushr_13fw3_placeholders upload-field">Placeholders<span class="dashicons dashicons-editor-help"></span><p>{product_image}</p></div>
	</li>

	<li>
		<label for="webpushr_woo_prod_image">Notification Image </label>
		<div class="options-wrapper has-custom-option vertical  p-0">
			<div class="custom-field wpp_woo_prod_image">
				<input name="webpushr_woo_prod_image" id="wpp_woo_prod_image" class="upload-field webpushr_13fw3_textfield" placeholder="" type="text" value="<?php echo get_option('webpushr_woo_prod_image');?>" size="100"><button style="float:right;" class="webpushr_13fw3_btn btn-primary upload-icon" data-label="Image" data-field="wpp_woo_prod_image" type="button" id="">Choose Image</button>
			</div>
		</div>
		<div class="webpushr_13fw3_info webpushr_13fw3_placeholders upload-field">Placeholders<span class="dashicons dashicons-editor-help"></span><p>{product_image}</p></div>
	</li>

	<li>
		<label for="webpushr_woo_prod_url">Target URL</label>
		<input name="webpushr_woo_prod_url" id="webpushr_woo_prod_url" class="webpushr_13fw3_textfield" type="text" value="<?php echo get_option('webpushr_woo_prod_url');?>" size="100" aria-required="true" >
		<div class="webpushr_13fw3_info webpushr_13fw3_placeholders">Placeholders<span class="dashicons dashicons-editor-help"></span><p>{product_url}</p></div>
	</li>




</ul>