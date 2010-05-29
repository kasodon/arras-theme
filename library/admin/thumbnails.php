<?php
function arras_add_regenthumbs_menu() {
	$regen_page = add_submenu_page( 'arras-options', __('Regenerate Thumbnails', 'arras'), __('Regen. Thumbnails', 'arras'), 'switch_themes', 'arras-regen-thumbs', 'arras_regen_thumbs' );
	
	add_action('admin_print_scripts-'. $regen_page, 'arras_regenthumbs_scripts');
	add_action('admin_print_styles-'. $regen_page, 'arras_regenthumbs_styles');
}

function arras_regenthumbs_scripts() {
	wp_enqueue_script('jquery-ui-progressbar', get_template_directory_uri() . '/js/jquery-ui.progressbar.min.js', null, 'jquery');
}

function arras_regenthumbs_styles() {
?> <link rel="stylesheet" href="<?php bloginfo('template_directory'); ?>/css/admin.css" type="text/css" /> <?php
}


function arras_regen_thumbs() {
	global $wpdb;
?>
<div class="wrap clearfix">

<?php screen_icon('themes') ?>
<h2 id="arras-header"><?php _e('Arras Theme &ndash; Regenerate Thumbnails', 'arras') ?></h2>

<div id="message" class="updated fade" style="display:none"></div>

<?php
	// If the button was clicked
	if ( !empty($_POST['arras-regen-thumbs']) ) {
		// Capability check
		if ( !current_user_can('manage_options') )
			wp_die( __('Cheatin&#8217; uh?') );

		// Form nonce check
		check_admin_referer( 'regenerate-thumbnails' );
		arras_regen_thumbs_process();
	} else {
		?>
		<p><?php _e( "Use this tool to regenerate thumbnails for all images that you have uploaded to your blog. This is useful if you have changed your layout, or edited any of the thumbnail sizes. Old thumbnails will be kept to avoid any broken images due to hard-coded URLs.", 'arras' ); ?></p>

		<p><?php _e( "This process is not reversible, although you can just change your thumbnail dimensions back to the old values and click the button again if you don't like the results.", 'arras'); ?></p>

		<p><?php _e( "To begin, just press the button below.", 'arras'); ?></p>

		<p><?php printf( __("Based on Viper007Bond's <a href='%s'>Regenerate Thumbnails</a> plugin.", 'arras'), 'http://wordpress.org/extend/plugins/regenerate-thumbnails/' ) ?></p>

		<form method="post" action="">
		<?php wp_nonce_field('regenerate-thumbnails') ?>

		<p><input type="submit" class="button hide-if-no-js" name="arras-regen-thumbs" id="arras-regen-thumbs" value="<?php _e( 'Regenerate All Thumbnails', 'arras' ) ?>" /></p>

		<noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', 'arras' ) ?></em></p></noscript>

		</form>

		</div><!-- .wrap -->
		<?php
	}
}

function arras_ajax_process_image() {
	if ( !current_user_can( 'manage_options' ) )
		die('-1');

	$id = (int) $_REQUEST['id'];

	if ( empty($id) )
		die('-1');

	$fullsizepath = get_attached_file( $id );

	if ( false === $fullsizepath || !file_exists($fullsizepath) )
		die('-1');

	set_time_limit( 60 );

	if ( wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $fullsizepath ) ) )
		die('1');
	else
		die('-1');
}

function arras_regen_thumbs_process() {
	global $wpdb;
	
	// Just query for the IDs only to reduce memory usage
	$images = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'" );
	
	if ( empty($images) ) {
		echo '	<p>' . sprintf( __( "Unable to find any images. Are you sure <a href='%s'>some exist</a>?", 'arras' ), admin_url('upload.php?post_mime_type=image') ) . "</p>\n\n";
	} else {
		echo '	<p>' . __( "Please be patient while all thumbnails are regenerated. This can take a while if your server is slow (cheap hosting) or if you have many images. Do not navigate away from this page until this script is done or all thumbnails won't be resized. You will be notified via this page when all regenerating is completed.", 'arras' ) . '</p>';
		// Generate the list of IDs
		$ids = array();
		foreach ( $images as $image )
			$ids[] = $image->ID;
		$ids = implode( ',', $ids );

		$count = count($images);
		?>
		<noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', 'arras' ) ?></em></p></noscript>
		
		<div id="regenthumbsbar" style="position:relative;height:25px;">
			<div id="regenthumbsbar-percent" style="position:absolute;left:50%;top:50%;width:50px;margin-left:-25px;height:25px;margin-top:-9px;font-weight:bold;text-align:center;"></div>
		</div>

		<script type="text/javascript">
		// <![CDATA[
			jQuery(document).ready(function($){
				var i;
				var rt_images = [<?php echo $ids; ?>];
				var rt_total = rt_images.length;
				var rt_count = 1;
				var rt_percent = 0;

				$("#regenthumbsbar").progressbar();
				$("#regenthumbsbar-percent").html( "0%" );

				function RegenThumbs(id) {
					$.post("admin-ajax.php", { action: "regenthumbnail", id: id }, function() {
						rt_percent = ( rt_count / rt_total ) * 100;
						$("#regenthumbsbar").progressbar( "value", rt_percent );
						$("#regenthumbsbar-percent").html( Math.round(rt_percent) + "%" );
						rt_count = rt_count + 1;

						if ( rt_images.length ) {
							RegenThumbs( rt_images.shift() );
						} else {
							$("#message").html("<p><strong><?php echo esc_js( sprintf(__( 'All done! Processed %d images.', 'arras'), $count) ); ?></strong></p>");
							$("#message").show();
						}

					});
				}

				RegenThumbs(rt_images.shift());
			});
		// ]]>
		</script>
		<?php
	}
}

/* End of file thumbnails.php */
/* Location: ./library/admin/thumbnails.php */