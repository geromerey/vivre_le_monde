<?php 
	get_header();
	?>
	<div class="wrap contentclass" role="document">

	<?php do_action( 'kt_afterheader' );

		include kadence_template_path();

			if ( virtue_display_sidebar() ) : ?>
				<aside class="<?php echo esc_attr( virtue_sidebar_class() ); ?> kad-sidebar" role="complementary" itemscope itemtype="http://schema.org/WPSideBar">
					<div class="sidebar">
						<?php include kadence_sidebar_path(); ?>
					</div><!-- /.sidebar -->
				</aside><!-- /aside -->
			<?php endif; ?>
			</div><!-- /.row-->
		</div><!-- /.content -->
	</div><!-- /.wrap -->
	<?php 
	get_footer();
