<?php 
/*-----------------------------------------------------------------------------------*/
/* This theme supports WooCommerce */
/*-----------------------------------------------------------------------------------*/

add_action( 'after_setup_theme', 'woocommerce_support' );
function woocommerce_support() {
  add_theme_support( 'woocommerce' );
}
/*-----------------------------------------------------------------------------------*/
/* WooCommerce Functions */
/*-----------------------------------------------------------------------------------*/

if (class_exists('woocommerce')) {
	if ( version_compare( WC_VERSION, '3.0', '>' ) ) {
		$virtue = virtue_get_options();
		if(isset($virtue['product_gallery_zoom']) && 1 == $virtue['product_gallery_zoom']) {
			add_theme_support( 'wc-product-gallery-zoom' );
		}
		if(isset($virtue['product_gallery_slider']) && 1 == $virtue['product_gallery_slider']) {
			add_theme_support( 'wc-product-gallery-slider' );
		}
	}
  	add_filter( 'woocommerce_enqueue_styles', '__return_false' );
  
    // Disable WooCommerce Lightbox
    if (get_option( 'woocommerce_enable_lightbox' ) == true ) {
        update_option( 'woocommerce_enable_lightbox', false );
    }

  // Makes the product finder plugin work.
  remove_action( 'template_redirect' , array( 'WooCommerce_Product_finder' , 'load_template' ) );

   if(class_exists('WC_PDF_Product_Vouchers')) {
      add_filter('template_include', 'kt_wc_voucher_override', 20);
        function kt_wc_voucher_override($template) {
            $cpt = get_post_type();
            if ($cpt == 'wc_voucher') {
              remove_filter('template_include', array('Kadence_Wrapping', 'wrap'), 101);
            }
            return $template;
        }
    }
    
}
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

add_action('kt_afterheader', 'kt_wc_print_notices');
function kt_wc_print_notices() {
  if (class_exists('woocommerce')) {
    if(!is_shop() and !is_woocommerce() and !is_cart() and !is_checkout() and !is_account_page() ) {
      echo '<div class="container">';
      echo do_shortcode( '[woocommerce_messages]' );
      echo '</div>';
    }
  }
}

// Set the number of columns to 3
function kad_woocommerce_cross_sells_columns( $columns ) {
  return 3;
}
add_filter( 'woocommerce_cross_sells_columns', 'kad_woocommerce_cross_sells_columns', 10, 1 );

// Limit the number of cross sells displayed to a maximum of 3
function kad_woocommerce_cross_sells_total( $limit ) {
  return 3;
}
add_filter( 'woocommerce_cross_sells_total', 'kad_woocommerce_cross_sells_total', 10, 1 );

// Redefine woocommerce_output_related_products()
function kadence_woo_related_products_limit() {
  global $product, $woocommerce;
  $related = $product->get_related();
  $args = array(
    'post_type'           => 'product',
    'no_found_rows'       => 1,
    'posts_per_page'      => 4,
    'ignore_sticky_posts'   => 1,
    //'orderby'               => $orderby,
    'post__in'              => $related,
    'post__not_in'          => array($product->get_id())
  );
  return $args;
}
add_filter( 'woocommerce_related_products_args', 'kadence_woo_related_products_limit' );
// Number of products per page
add_filter('loop_shop_per_page', 'kadence_wooframework_products_per_page');
if (!function_exists('kadence_wooframework_products_per_page')) {
  function kadence_wooframework_products_per_page() {
    global $virtue;
    if ( isset( $virtue['products_per_page'] ) ) {
      return $virtue['products_per_page'];
    }
  }
}

// Display product tabs?
add_action('wp_head','kadence_wooframework_tab_check');
if ( ! function_exists( 'kadence_wooframework_tab_check' ) ) {
  function kadence_wooframework_tab_check() {
    global $virtue;
    if ( isset( $virtue[ 'product_tabs' ] ) && $virtue[ 'product_tabs' ] == "0" ) {
      remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
    }
  }
}

// Display related products?
add_action('wp_head','kadence_wooframework_related_products');
if ( ! function_exists( 'kadence_wooframework_related_products' ) ) {
  function kadence_wooframework_related_products() {
    global $virtue;
    if ( isset( $virtue[ 'related_products' ] ) && $virtue[ 'related_products' ] == "0" ) {
      remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
    }
  }
}
function virtue_woocommerce_cart_fragments_support() {
	if (class_exists('woocommerce')) {
		if ( version_compare( WC_VERSION, '3.0', '>' ) ) {
	    	add_filter('woocommerce_add_to_cart_fragments', 'virtue_woocommerce_header_add_to_cart_fragment');
	    } else {
	    	add_filter('add_to_cart_fragments', 'virtue_woocommerce_header_add_to_cart_fragment');
	    }
		function virtue_woocommerce_header_add_to_cart_fragment( $fragments ) {
		    ob_start(); ?>
		    <a class="cart-contents" href="<?php echo esc_url( wc_get_cart_url() ); ?>" title="<?php esc_attr_e( 'View your shopping cart', 'virtue' ); ?>">
		        <i class="icon-shopping-cart" style="padding-right:5px;"></i>
		        <?php esc_html_e('Your Cart', 'virtue');?>
		        <span class="kad-cart-dash">-</span>
		        <?php if ( WC()->cart->tax_display_cart == 'incl' ) {
						echo wp_kses_post( WC()->cart->get_cart_subtotal() ); 
		            } else {
						echo wp_kses_post( WC()->cart->get_cart_total() );
		            }
		        ?>
		    </a>
		    <?php
		    $fragments['a.cart-contents'] = ob_get_clean();
		    return $fragments;
		}
	}
}
add_action( 'after_setup_theme', 'virtue_woocommerce_cart_fragments_support' );

function virtue_woocommerce_archive_title_link_start() {
	echo '<a href="'.get_the_permalink().'" class="product_item_link">';
}
add_action( 'woocommerce_shop_loop_item_title', 'virtue_woocommerce_archive_title_link_start', 7 );

remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
add_action( 'woocommerce_shop_loop_item_title', 'virtue_woocommerce_template_loop_product_title', 10);
function virtue_woocommerce_template_loop_product_title() {
	echo '<h5>' . get_the_title() . '</h5>';
}
function virtue_woocommerce_archive_title_link_end() {
	echo '</a>';
}
add_action( 'woocommerce_shop_loop_item_title', 'virtue_woocommerce_archive_title_link_end', 15 );

remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );
remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
add_action( 'woocommerce_single_variation', 'kt_woocommerce_single_variation', 10 );
add_action( 'woocommerce_single_variation', 'kt_woocommerce_single_variation_add_to_cart_button', 20 );

if ( ! function_exists( 'kt_woocommerce_single_variation_add_to_cart_button' ) ) {
	/**
	* Output the add to cart button for variations.
	*/
	function kt_woocommerce_single_variation_add_to_cart_button() {
		global $product; ?>
		<div class="variations_button">
			<?php woocommerce_quantity_input( array( 'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 1 ) ); ?>
			<button type="submit" class="kad_add_to_cart headerfont kad-btn kad-btn-primary single_add_to_cart_button"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
			<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
			<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
			<input type="hidden" name="variation_id" class="variation_id" value="" />
		</div>
		<?php
	}
}

if ( ! function_exists( 'kt_woocommerce_single_variation' ) ) {
	/**
	* Output placeholders for the single variation.
	*/
	function kt_woocommerce_single_variation() {
		echo '<div class="single_variation headerfont"></div>';
	}
}

// Shop Page Image
function virtue_woocommerce_image_link_open() {
	echo '<a href="'.get_the_permalink().'" class="product_item_link product_img_link">';
}
add_action( 'woocommerce_before_shop_loop_item_title', 'virtue_woocommerce_image_link_open', 5 );

function virtue_woocommerce_image_link_close() {
	echo '</a>';
}
add_action( 'woocommerce_before_shop_loop_item_title', 'virtue_woocommerce_image_link_close', 50 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
add_action( 'woocommerce_before_shop_loop_item_title', 'virtue_woocommerce_template_loop_product_thumbnail', 10 );

function virtue_woocommerce_template_loop_product_thumbnail() {
	global $virtue, $woocommerce_loop, $post;

	// Store column count for displaying the grid
	if ( empty( $woocommerce_loop['columns'] ) ) {
		$woocommerce_loop['columns'] = apply_filters( 'loop_shop_columns', 4 );
	}

	if ($woocommerce_loop['columns'] == '3'){ 
		$productimgwidth = 365;
		$productimgheight = 365;
	} else {
		$productimgwidth = 268;
		$productimgheight = 268;
	}

	if(isset($virtue[ 'product_img_resize' ]) && $virtue[ 'product_img_resize' ] == 0) {
		$resizeimage = 0;
	} else {
		$resizeimage = 1;
	}

	if($resizeimage == 1) { 
		if ( has_post_thumbnail() ) {
			$image_id = get_post_thumbnail_id( $post->ID );
			$img = virtue_get_image_array($productimgwidth, $productimgheight, true, null, null, $image_id);

			if( empty( $img[ 'alt' ] ) ) { $img[ 'alt' ] = get_the_title(); }
			ob_start();  ?> 
				<img width="<?php echo esc_attr( $img[ 'width' ] );?>" height="<?php echo esc_attr( $img[ 'height' ] );?>" src="<?php echo esc_url( $img[ 'src' ] );?>" <?php echo wp_kses_post( $img[ 'srcset' ] );?> class="attachment-shop_catalog size-<?php echo esc_attr($productimgwidth.'x'.$productimgheight);?> wp-post-image" alt="<?php echo esc_attr( $img[ 'alt' ] ); ?>">
				<?php 
				echo wp_kses_post( apply_filters('post_thumbnail_html', ob_get_clean(), $post->ID, $image_id, array($productimgwidth, $productimgheight), $attr = '') );
		} elseif ( woocommerce_placeholder_img_src() ) {
			echo wp_kses_post( woocommerce_placeholder_img( 'shop_catalog' ) );
		}  
	} else { 
		echo '<div class="kad-woo-image-size">';
			echo wp_kses_post( woocommerce_template_loop_product_thumbnail() );
		echo '</div>';
	}
}

remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_product_link_close', 5 );

remove_action( 'woocommerce_shop_loop_subcategory_title', 'woocommerce_template_loop_category_title', 10 );
add_action( 'woocommerce_shop_loop_subcategory_title', 'kt_woocommerce_template_loop_category_title', 10 );

 function kt_woocommerce_template_loop_category_title( $category ) {
	?>
	<h5>
	<?php
		echo esc_html( $category->name );
		if ( $category->count > 0 ) {
			echo wp_kses_post( apply_filters( 'woocommerce_subcategory_count_html', ' <mark class="count">(' . $category->count . ')</mark>', $category ) );
		}
	?>
	</h5>
<?php
}



// define the woocommerce_loop_add_to_cart_link callback
function kt_add_class_woocommerce_loop_add_to_cart_link($array, $product) {
	$array['class'] .= ' kad-btn headerfont kad_add_to_cart';
	return $array;
}   
add_filter('woocommerce_loop_add_to_cart_args', 'kt_add_class_woocommerce_loop_add_to_cart_link', 10, 2);

remove_action( 'woocommerce_before_subcategory', 'woocommerce_template_loop_category_link_open', 10 );
remove_action( 'woocommerce_after_subcategory', 'woocommerce_template_loop_category_link_close', 10 );
add_action( 'woocommerce_before_subcategory', 'kt_woocommerce_template_loop_category_link_open', 10 );
add_action( 'woocommerce_after_subcategory', 'kt_woocommerce_template_loop_category_link_close', 10 );

function kt_woocommerce_template_loop_category_link_open( $category ) {
    echo '<a href="' . esc_url( get_term_link( $category->slug, 'product_cat' ) ) . '">';
}
function kt_woocommerce_template_loop_category_link_close() {
    echo '</a>';
}

/*
*
* WOO ARCHIVE CAT IMAGES
*
*/
function kad_woo_archive_cat_image_output() {
    remove_action( 'woocommerce_before_subcategory_title', 'woocommerce_subcategory_thumbnail', 10 );
    add_action( 'woocommerce_before_subcategory_title', 'kad_woocommerce_subcategory_thumbnail', 10 );
    function kad_woocommerce_subcategory_thumbnail( $category ) {
		global $woocommerce_loop, $virtue;

		if ( empty( $woocommerce_loop[ 'columns' ] ) ) {
			$woocommerce_loop[ 'columns' ] = apply_filters( 'loop_shop_columns', 4 );
		}
		$product_cat_column = $woocommerce_loop['columns'];

		if ( $product_cat_column == '3' ){
			$catimgwidth = 380;
		} else {
			$catimgwidth = 270;
		}
		if( isset( $virtue[ 'product_cat_img_ratio' ] ) ) {
			$img_ratio = $virtue[ 'product_cat_img_ratio' ];
		} else {
			$img_ratio = 'widelandscape';
		}

		if($img_ratio == 'portrait') {
			$tempcatimgheight = $catimgwidth * 1.35;
			$catimgheight = floor($tempcatimgheight);
		} else if($img_ratio == 'landscape') {
			$tempcatimgheight = $catimgwidth / 1.35;
			$catimgheight = floor($tempcatimgheight);
		} else if($img_ratio == 'square') {
			$catimgheight = $catimgwidth;
		} else {
			$tempcatimgheight = $catimgwidth / 2;
			$catimgheight = floor($tempcatimgheight);
		}
		if($img_ratio == 'off') {
			woocommerce_subcategory_thumbnail($category);
		} else {
			$thumbnail_id = get_woocommerce_term_meta( $category->term_id, 'thumbnail_id', true  );
			if( ! empty( $thumbnail_id ) ) {
				$img = virtue_get_image_array( $catimgwidth, $catimgheight, true, null, null, $thumbnail_id, false );
				echo '<div class="kt-cat-intrinsic" style="padding-bottom:'.esc_attr( ( $img[ 'height' ]/$img[ 'width' ] ) * 100 ).'%;">';
				echo '<img src="' . esc_url( $img[ 'src' ] ) . '" width="'.esc_attr( $img[ 'width' ] ).'" height="'.esc_attr( $img[ 'height' ] ).'" alt="' . esc_attr( $img[ 'alt' ] ) . '" '. wp_kses_post( $img[ 'srcset' ] ).' />';
				echo '</div>';
			} else {
				    $cat_image = array(virtue_img_placeholder(),$catimgwidth,$catimgheight); 
		            if ( $cat_image[0] ) {
		                    echo '<div class="kt-cat-intrinsic" style="padding-bottom:'.esc_attr( ( $catimgheight/$catimgwidth ) * 100 ).'%;">';
		                    echo '<img src="' . esc_url( $cat_image[0] ) . '" width="'.esc_attr( $cat_image[1] ).'" height="'.esc_attr( $cat_image[2] ).'" alt="' . esc_attr( $category->name ) . '" />';
		                    echo '</div>';
		            }
			}
        }

    }
}
add_action( 'init', 'kad_woo_archive_cat_image_output');
add_action( 'woocommerce_before_shop_loop', 'virtue_woo_cat_loop', 60 );
function virtue_woo_cat_loop() {
	if ( version_compare( WC_VERSION, '3.3', '<' ) ) {
		if ( ! is_search() ) {
			echo '<div class="clearfix rowtight product_category_padding">';
				woocommerce_product_subcategories(); 
			echo '</div>';
		}
	}
}