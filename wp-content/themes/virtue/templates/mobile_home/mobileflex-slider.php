 <?php  global $virtue; 
        
        if(isset($virtue['mobile_slider_size'])) {
          $slideheight = $virtue['mobile_slider_size'];
        } else {
          $slideheight = 400;
        }
        if(isset($virtue['mobile_slider_size_width'])) {
          $slidewidth = $virtue['mobile_slider_size_width'];
        } else {
          $slidewidth = 1140;
        }
        if(isset($virtue['mobile_slider_captions'])) {
          $captions = $virtue['mobile_slider_captions'];
        } else {
          $captions = '';
        }
        if(isset($virtue['home_mobile_slider'])) {
          $slides = $virtue['home_mobile_slider'];
        } else {
          $slides = '';
        }

        if(isset($virtue['mobile_trans_type'])) {
          $transtype = $virtue['mobile_trans_type'];
        } else {
          $transtype = 'slide';
        }
        if(isset($virtue['mobile_slider_transtime'])) {
          $transtime = $virtue['mobile_slider_transtime'];
        } else {
          $transtime = '300';
        }
        if(isset($virtue['mobile_slider_autoplay'])) {
          $autoplay = $virtue['mobile_slider_autoplay'];
        } else {
          $autoplay = 'true';
        }
        if(isset($virtue['mobile_slider_pausetime'])) {
          $pausetime = $virtue['mobile_slider_pausetime'];
        } else {
          $pausetime = '7000';
        }
    ?>

    <div class="sliderclass kad-mobile-slider">
        <div id="imageslider" class="container">
            <div id="mflex" class="flexslider kt-flexslider loading" style="max-width:<?php echo esc_attr($slidewidth);?>px; margin-left: auto; margin-right:auto;" data-flex-speed="<?php echo esc_attr($pausetime);?>" data-flex-anim-speed="<?php echo esc_attr($transtime);?>" data-flex-animation="<?php echo esc_attr($transtype); ?>" data-flex-auto="<?php echo esc_attr($autoplay);?>">
                <ul class="slides">
                    <?php foreach ($slides as $slide) : 
                    if(!empty($slide['target']) && $slide['target'] == 1) {$target = '_blank';} else {$target = '_self';}
                    $image = aq_resize($slide['url'], $slidewidth, $slideheight, true);
                    if(empty($image)) {$image = $slide['url'];} ?>
                        <li> 
                        <?php if($slide['link'] != '') echo '<a href="'.esc_url( $slide['link'] ).'" target="'.esc_attr( $target ).'">'; ?>
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($slide['title']); ?>" width="<?php echo esc_attr($slidewidth);?>" height="<?php echo esc_attr($slideheight);?>"  />
                                <?php if ($captions == '1') { ?> 
                                    <div class="flex-caption">
                                    <?php if ($slide['title'] != '') echo '<div class="captiontitle headerfont">'.esc_html( $slide['title'] ).'</div>'; ?>
                                    <?php if ($slide['description'] != '') echo '<div><div class="captiontext headerfont"><p>'.wp_kses_post( $slide['description'] ).'</p></div></div>';?>
                                    </div> 
                                <?php } ?>
                        <?php if($slide['link'] != '') echo '</a>'; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div> <!--Flex Slides-->
        </div><!--Container-->
    </div><!--feat-->