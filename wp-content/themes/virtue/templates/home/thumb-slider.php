<?php 
/* Thumbnail Flex Slider */
global $virtue; 
?>
<div class="sliderclass kad-desktop-slider">
    <div id="imageslider" class="container">
        <?php // Get slider settings
            if(isset($virtue['slider_size'])) {
              $slideheight = $virtue['slider_size'];
            } else { 
              $slideheight = 400;
            }
            if(isset($virtue['slider_size_width'])) {
              $slidewidth = $virtue['slider_size_width'];
            } else {
              $slidewidth = 1170;
            }
            if(isset($virtue['slider_captions'])) {
              $captions = $virtue['slider_captions'];
            } else {
              $captions = '';
            }
            if(isset($virtue['home_slider'])) {
              $slides = $virtue['home_slider']; 
            } else {
              $slides = '';
            }
            if(isset($virtue['trans_type'])) {
              $transtype = $virtue['trans_type'];
            } else {
              $transtype = 'slide'; 
            }
            if(isset($virtue['slider_transtime'])) {
              $transtime = $virtue['slider_transtime']; 
            } else {
              $transtime = '300'; 
            }
            if(isset($virtue['slider_autoplay']) && $virtue['slider_autoplay'] == '1') { 
              $autoplay = 'true';
            } else { 
                $autoplay = 'false'; 
            }
            if(isset($virtue['slider_pausetime'])) {
              $pausetime = $virtue['slider_pausetime'];
            } else { 
              $pausetime = '7000'; 
            }
             ?>
              
              <div id="flex" class="flexslider kt-flexslider-thumb loading" style="max-width:<?php echo esc_attr($slidewidth);?>px; margin-left: auto; margin-right:auto;" data-flex-speed="<?php echo esc_attr($pausetime);?>" data-flex-anim-speed="<?php echo esc_attr($transtime);?>" data-flex-animation="<?php echo esc_attr($transtype); ?>" data-flex-auto="<?php echo esc_attr($autoplay);?>">
                  <ul class="slides">
                    <?php foreach ($slides as $slide) :
                        if(!empty($slide['target']) && $slide['target'] == 1) {
                          $target = '_blank';
                        } else {
                          $target = '_self';
                        }
                        $image = aq_resize($slide['url'], $slidewidth, $slideheight, true);
                        if(empty($image)) {$image = $slide['url'];} ?>
                              <li> 
                                  <?php if($slide['link'] != '') echo '<a href="'.esc_url($slide['link']).'" target="'.esc_attr($target).'">'; ?>
                                      <img src="<?php echo esc_url($image); ?>" width="<?php echo esc_attr($slidewidth);?>" height="<?php echo esc_attr($slideheight);?>" alt="<?php echo esc_attr($slide['title']); ?>" />
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

              <div id="thumbnails" class="flexslider" style="max-width:<?php echo esc_attr($slidewidth);?>px; margin-left: auto; margin-right:auto;">
                   <ul class="slides">
                       <?php foreach ($slides as $slide) :
                                $imgurl = $slide['url'];
                                $thumbslide = aq_resize( $imgurl, 180, 100, true ); ?>
                              <li> 
                                  <img src="<?php echo esc_url($thumbslide); ?>" />
                              </li>
                          <?php endforeach; ?>
                    </ul>
                 </div><!--Flex thumb-->
              </div><!--Container-->
          </div><!--feat-->