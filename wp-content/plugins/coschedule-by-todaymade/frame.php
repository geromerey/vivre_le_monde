<?php
if ( get_option( 'tm_coschedule_token' ) ) {
    if ( current_user_can( 'edit_posts' ) ) {
        $url = "https://app.coschedule.com/#/authenticate?calendarID=" . urlencode( get_option( 'tm_coschedule_calendar_id' ) );
        $url .= "&wordpressSiteID=" . urlencode( get_option( 'tm_coschedule_wordpress_site_id' ) );
        $url .= "&redirect=" . $redirect . "&build=" . $this->build;
        $url .= "&userID=" . $this->current_user_id;

        $userToken = '';
        if ( isset( $_GET['tm_cos_user_token'] ) ) {
            $userToken = $_GET['tm_cos_user_token'];
        }

        if ( isset( $userToken ) && ! empty( $userToken ) ) {
            $url .= '&userToken=' . urlencode( $userToken );
        }

        ?>

        <iframe id="CoSiFrame" src="<?php echo esc_url( $url ); ?>" width="100%" style="border:none; margin-left: -20px; width: calc(100% + 20px)"></iframe>

        <script>
            jQuery(document).ready(function ($) {
                $('.update-nag').remove();
                $('#wpfooter').remove();
                $('#wpwrap').find('#footer').remove();
                $('#wpbody-content').css('paddingBottom', 0);

                $('#CoSiFrame').css('min-height', $('#wpbody').height());

                var resize = function () {
                    var p = $(window).height() - $('#wpadminbar').height() - 4;
                    $('#CoSiFrame').height(p);
                };

                resize();
                $(window).resize(function () {
                    resize();
                });
            });
        </script>
        <?php
    } else {
        include( '_access-denied.html' );
    }
} else {
    include( '_missing-token.html' );
}
