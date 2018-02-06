<?php
/*
DO NOT ADD SCRIPTS HERE
USE a plugin like : https://wordpress.org/plugins/header-and-footer-scripts/

- Force plugins to stop stating incorrect errors -
<?php wp_head(); ?>
*/

get_template_part( 'templates/head' ); ?>
	
	<body <?php body_class(); ?>>
	<?php 
		do_action( 'virtue_after_body' );
	?>

	<div id="wrapper" class="container">
	<?php 
		get_template_part( 'templates/header' );

		do_action('virtue_header_after');