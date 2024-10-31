<?php

/*

Plugin name: Post Search
Description: This plugin will help you to search posts
Author: Md. Sarwar-A-Kawsar
Author URI: https://fiverr.com/sa_kawsar
Version: 1.0

*/

defined('ABSPATH') or die('You can\'t access to this page');
define('POSTSEARCH_DIR',plugin_dir_url(__FILE__));
add_shortcode( 'post-search', 'postsearch_callback' );
function postsearch_callback(){
	ob_start();
	?>
	<div id="all-holder" class="post-search-container">
		<h2>Description</h2>
		<?php
			$latest = new WP_Query(
		        array(
		            'post_type' => 'post',
		            'post_status' => 'publish',
		            'posts_per_page' => 1,
		            'orderby' => 'modified',
		            'order' => 'DESC'
		        )
		    );
		    if($latest->have_posts()){
		        while($latest->have_posts()): $latest->the_post();
					$modified_date = get_the_date();
				endwhile;
		    }
	    ?>
		<p>Last update <?php echo $modified_date; ?></p>
		<div id="data-holder"></div>
	</div>
	<script type="text/javascript">
		window.onload = function(){
			ajax_trigger_function(10,1,'');
		}
		function ajax_trigger_function(post_count,see_more_visibility,cat_value){
			jQuery(document).ready(function(){
				var update_div = jQuery('#data-holder');
				var post_year = jQuery('#post-year').val();
				var selected_cat = jQuery('#selected_cat').val();
				var value_count = post_count;
				var ajaxurl = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
				jQuery("#post-list-holder").html('<center><img style="margin:auto;" class="loading" src="<?php echo POSTSEARCH_DIR; ?>asset/images/loading.gif"/></center>');
				jQuery.ajax({
				    type: 'POST',
				    url: ajaxurl,
				    dataType: 'html',
				    data: {
				        action: 'get_post_list',
				        newValue: [post_year, value_count, see_more_visibility, cat_value]
				    },
				    success: function(response) {
				        console.log(response);
				        update_div.html('');
				        update_div.html(response);
				    },
				    error: function(errorThrown){
				        console.log(errorThrown);
				    }    
				})
			});
		}
		</script>
	<?php
	return ob_get_clean();
}
function postsearch_get_post_list(){
	if(isset($_POST['newValue'])){
		$data = sanitize_text_field( $_POST['newValue'] );
		?>
		<div class="list-head">
			<div class="cat-list">
				<p style="cursor:pointer;<?php if($data[3]==''){echo 'border-bottom:2px solid #808080;';} ?>" onclick="ajax_trigger_function(10,1,'')">All Categories</p>
				<?php
				$cat_obj = get_terms( array(
					    'taxonomy' => 'category',
					    'hide_empty' => false,
					));
					foreach ($cat_obj as $cat => $cat_data) {
					?>
						<p style="cursor:pointer;<?php if($data[3]==$cat_data->term_id){echo 'border-bottom:2px solid #808080;'; } ?>" onclick="ajax_trigger_function(10,1,'<?php echo $cat_data->term_id; ?>')"><?php echo $cat_data->name; ?></p>
					<?php 
					}
				?>
			</div>
			<form method="post">
				<select onchange="ajax_trigger_function(10,1,<?php echo $data[3]; ?>)" id="post-year" name="select-year">
					<?php
						global $wpdb;
						$table_name = $wpdb->prefix.'posts';
						$years = $wpdb->get_results( "SELECT YEAR(post_date) AS year FROM $table_name WHERE post_type = 'post' AND (post_status = 'publish') GROUP BY year DESC");
						$gyear = [];
						foreach($years as $year){
							$gyear[] = $year->year;
						}
						rsort($gyear); ?>
						<option value="" <?php if($data[0]==""){ echo "selected";} ?>>All posts</option>
						<?php foreach ($gyear as $value) { ?>
							<option value="<?php echo $value; ?>" <?php echo ($value == $data[0]) ? 'selected' : ''; ?>><?php echo $value; ?></option>';
						<?php }
					?>
				</select>
			</form>
		</div>
		<div id="post-list-holder" class="post-list-container">
		<?php
		$post_count = $data[1];
		$args = array(
			'post_type' => array(
				'post',
			),
			'post_status' => array(
				'publish',
			),
			'posts_per_page' => $post_count,
			'year' => $data[0],
			'cat' => $data[3],
		);
		
		$query = new WP_Query( $args );
		if($query->have_posts()):
			while($query->have_posts()): $query->the_post();
				?>
				<div class="post-list">
					<a href="<?php echo the_permalink(); ?>"><?php the_title();?></a>
					<p><?php echo get_the_date(); ?></p>
				</div>
				<?php
			endwhile;
		else:
			echo esc_html( '<center><h2>No post found</h2></center>' );
		endif;
		if( $query->found_posts > 10 && $data[2] == 1){
		?>
			<center><button onclick="ajax_trigger_function(20,0,<?php echo $data[3]; ?>)" id="see_more">See more</button></center>
		<?php
		}
		echo esc_html( '</div>' );
	}
	exit();
}
add_action('wp_ajax_get_post_list','postsearch_get_post_list');
add_action('wp_ajax_nopriv_get_post_list','postsearch_get_post_list');

function postsearch_wp_enqueue_script(){
	$plugin_dir = plugin_dir_url( __FILE__ );
	wp_enqueue_script('query');
	wp_enqueue_style( 'saps-custom-style', $plugin_dir.'asset/css/style.css' );
}
add_action( 'wp_enqueue_scripts', 'postsearch_wp_enqueue_script' );