<?php
if ( post_password_required() ) {
	return;
}

if ( have_comments() ) : ?>
	<section id="comments">
		<h3 class="comments-title">
			<?php
				$comments_number = get_comments_number();
				if ( '1' === $comments_number ) {
					esc_html__( 'One Response', 'virtue' );
				} else {
					/* translators: %d: Response Number */
					printf( esc_html( _n( '%d Response', '%d Responses', $comments_number, 'virtue' ) ), esc_html( $comments_number ) );
				}
			?>
		</h3>

		<ol class="media-list comment-list">
			<?php 
			$args = array(
				'walker'            => new Virtue_Walker_Comment,
				'max_depth'         => '',
				'style'             => 'ul',
				'callback'          => null,
				'end-callback'      => null,
				'type'              => 'all',
				'reply_text'        => 'Reply',
				'page'              => '',
				'per_page'          => '',
				'avatar_size'       => 36,
				'reverse_top_level' => null,
				'reverse_children'  => '',
				'format'            => 'html5',
				'short_ping'        => false,
				'echo'              => true
			);
			wp_list_comments( $args ); ?>
		</ol>

		<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
			<nav>
				<ul class="pager">
				<?php if ( get_previous_comments_link() ) : ?>
					<li class="previous"><?php previous_comments_link( __( '&larr; Older comments', 'virtue' ) ); ?></li>
				<?php endif;
				if ( get_next_comments_link() ) : ?>
					<li class="next"><?php next_comments_link( __( 'Newer comments &rarr;', 'virtue' ) ); ?></li>
				<?php endif; ?>
				</ul>
			</nav>
		<?php endif;
		if ( ! comments_open() && !is_page() && post_type_supports( get_post_type(), 'comments' ) ) : 
			global $virtue; 
			if( isset( $virtue[ 'close_comments' ] ) ) {
				$show_closed_comment = $virtue[ 'close_comments' ]; 
			} else {
				$show_closed_comment = 1;
			}
			if( 1 == $show_closed_comment ) { ?>
				<div class="alert">
					<?php esc_html_e( 'Comments are closed.', 'virtue' ); ?>
				</div>
			<?php }
		endif; ?>
	</section><!-- /#comments -->

<?php endif; 

if ( ! have_comments() && !comments_open() && !is_page() && post_type_supports( get_post_type(), 'comments' ) ) : 
	global $virtue; 
	if( isset( $virtue[ 'close_comments' ] ) ) {
		$show_closed_comment = $virtue[ 'close_comments' ]; 
	} else {
		$show_closed_comment = 1;
	}
	if( 1 == $show_closed_comment ){ ?>
		<section id="comments">
			<div class="alert">
				<?php esc_html_e( 'Comments are closed.', 'virtue' ); ?>
			</div>
		</section><!-- /#comments -->
	<?php } 
endif; 

if ( comments_open() ) : ?>
	<section id="respond">
	<?php $comment_args = array( 'fields' => apply_filters( 'comment_form_default_fields', array(
			'author' => '<div class="col-md-4">' . '<label for="author">' . __( 'Name', 'virtue' ) . ( $req ? ' <span class="comment-required">*</span>' : '' ) . '</label> ' .
						'<input id="author" name="author" type="text" value="' . esc_attr( $comment_author ) . '" ' . ( $req ? 'aria-required="true"' : '') . ' /></div>',
			'email'  => '<div class="col-md-4"><label for="email">' . __( 'Email (will not be published)', 'virtue') . ( $req ? ' <span class="comment-required">*</span>' : '' ) . '</label> ' .
						'<input type="email" class="text" name="email" id="email" value="' . esc_attr(  $comment_author_email ) . '" ' . ( $req ? 'aria-required="true"' : '') . ' /></div>',
			'url'    => '<div class="col-md-4"><label for="url">' . __( 'Website', 'virtue' ) . '</label> ' .
						'<input id="url" name="url" type="url" value="' . esc_attr( $comment_author_url ) . '" /></div>',
					) 
			),
			'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . __( 'Comment', 'virtue' ) . '</label> <textarea id="comment" name="comment" cols="45" rows="8" class="input-xlarge" aria-required="true" required="required"></textarea></p>',
			'comment_notes_before' => '',
			'comment_notes_after'  => '',
			'id_form'              => 'commentform',
			'id_submit'            => 'submit',
			'class_submit'         => 'kad-btn kad-btn-primary',
			'name_submit'          => 'submit',
			'title_reply'          => __( 'Leave a Reply', 'virtue' ),
			/* translators: %s: post title */
			'title_reply_to'       => __( 'Leave a Reply to %s', 'virtue' ),
			'label_submit'         => __( 'Submit Comment', 'virtue' ),
			'submit_button'        => '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" />',
			'submit_field'         => '<p class="form-submit">%1$s %2$s</p>',
			'format'               => 'html5',
        );
        comment_form( $comment_args ); ?>
	</section><!-- /#respond -->
<?php endif; ?>
