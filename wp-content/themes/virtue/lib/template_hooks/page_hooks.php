<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}
add_action( 'virtue_page_footer', 'virtue_page_comments', 20 );
function virtue_page_comments() {
	global $virtue;
	if(isset($virtue['page_comments']) && $virtue['page_comments'] == '1') {
		comments_template('/templates/comments.php');
	}
}

add_action( 'virtue_page_title_container', 'virtue_page_title', 20 );
function virtue_page_title() {
	get_template_part('/templates/page', 'header'); 
}

add_action( 'virtue_pagination', 'virtue_pagination', 10 );
function virtue_pagination() {

  	$args['mid_size'] = 3;
  	$args['end_size'] = 1;
  	$args['prev_text'] = '«';
  	$args['next_text'] = '»';

  	echo '<div class="wp-pagenavi">';
 			the_posts_pagination($args);
 	 echo '</div>';
}