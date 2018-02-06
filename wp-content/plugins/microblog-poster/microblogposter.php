<?php
/**
 *
 * Plugin Name: Microblog Poster
 * Plugin URI: http://efficientscripts.com/microblogposter
 * Description: Automatically publishes your new and old blog content to Social Networks. Auto posts to Twitter, Facebook, Linkedin, Tumblr, Blogger, Xing..
 * Version: 1.9.2
 * Author: Efficient Scripts
 * Author URI: http://efficientscripts.com/
 * Text Domain: microblog-poster
 *
 */

require_once "microblogposter_curl.php";
require_once "microblogposter_oauth.php";
require_once "microblogposter_bitly.php";
require_once "microblogposter_googl.php";



class MicroblogPoster_Poster
{
    
    
    /**
    * Activate function of this plugin called on activate action hook
    * 
    * 
    * @return  void
    */
    public static function activate()
    {
        global  $wpdb;
        
        $table_accounts = $wpdb->prefix . 'microblogposter_accounts';
        $table_logs = $wpdb->prefix . 'microblogposter_logs';
        $table_old_items = $wpdb->prefix . 'microblogposter_old_items';
        $table_user_accounts = $wpdb->prefix . 'microblogposter_user_accounts';
        $table_items_meta = $wpdb->prefix . 'microblogposter_items_meta';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_accounts} (
            account_id int(11) NOT NULL AUTO_INCREMENT,
            username varchar(150) NOT NULL DEFAULT '',
            password varchar(200) DEFAULT NULL,
            consumer_key varchar(200) DEFAULT NULL,
            consumer_secret varchar(200) DEFAULT NULL,
            access_token varchar(200) DEFAULT NULL,
            access_token_secret varchar(200) DEFAULT NULL,
            type varchar(100) NOT NULL DEFAULT '',
            message_format text,
            extra text,
            PRIMARY KEY (account_id),
            UNIQUE username_type (username,type)
	)";
	
        $wpdb->query($sql);
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_logs} (
            log_id int(11) NOT NULL AUTO_INCREMENT,
            account_id int(11) NOT NULL,
            account_type varchar(100) NOT NULL DEFAULT '',
            username varchar(200) NOT NULL DEFAULT '',
            post_id bigint UNSIGNED NOT NULL,
            action_result tinyint NOT NULL,
            update_message text,
            log_type varchar(50) NOT NULL DEFAULT 'regular',
            log_message text,
            log_datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (log_id)
        )";
        
        $wpdb->query($sql);
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_old_items} (
            item_id int(11) NOT NULL AUTO_INCREMENT,
            publish_datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            item_type varchar(50) NOT NULL DEFAULT 'post',
            extra text,
            PRIMARY KEY (item_id)
        )";
        
        $wpdb->query($sql);
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_user_accounts} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint UNSIGNED NOT NULL,
            account_id int(11) NOT NULL,
            PRIMARY KEY (id)
        )";
        
        $wpdb->query($sql);
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_items_meta} (
            id int(11) NOT NULL AUTO_INCREMENT,
            item_id int(11) NOT NULL,
            publish_datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            item_type varchar(50) NOT NULL DEFAULT 'post',
            account_id int(11) NOT NULL,
            message_format text,
            extra text,
            PRIMARY KEY (id)
        )";
        
        $wpdb->query($sql);
        
        $who_can_auto_publish_name = "microblogposter_who_can_auto_publish";
        $who_can_auto_publish_value = get_option($who_can_auto_publish_name, 555);
        if($who_can_auto_publish_value == 555)
        {
            MicroblogPoster_Poster::get_and_save_who_can_auto_publish_default();
        }
    }
    
    /**
    * Main function of this plugin called on publish_post action hook
    * 
    *
    * @param   int  $post_ID ID of the new/updated post
    * @return  void
    */
    public static function schedule($post_ID)
    {
        global  $wpdb;
        $table_items_meta = $wpdb->prefix . 'microblogposter_items_meta';
        $sql = "CREATE TABLE IF NOT EXISTS {$table_items_meta} (
            id int(11) NOT NULL AUTO_INCREMENT,
            item_id int(11) NOT NULL,
            publish_datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            item_type varchar(50) NOT NULL DEFAULT 'post',
            account_id int(11) NOT NULL,
            message_format text,
            extra text,
            PRIMARY KEY (id)
        )";
        $wpdb->query($sql);
        
        if(!MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','getScheduledMeta'))
        {
            return;
        }
        
        $post = get_post($post_ID);
        $author_id = (int)$post->post_author;
        if (!MicroblogPoster_Poster::can_user_auto_publish($author_id))
        {
            return;
        }
        if ($post->post_status != 'future')
        {
            return;
        }
        
        global $wpdb;
        
        $table_items_meta = $wpdb->prefix . 'microblogposter_items_meta';
        
        $sql = "DELETE FROM {$table_items_meta} WHERE item_id = %d";
        $wpdb->query($wpdb->prepare($sql, $post_ID));
        
        $extra = array();
        if (isset($_POST['microblogposteroff']) && $_POST['microblogposteroff'] == "on")
        {
            $extra['disabled'] = true;
        }
        else
        {
            $extra['disabled'] = false;
        }
        if (isset($_POST['mbp_microblogposter_category_to_account']) && 
            $_POST['mbp_microblogposter_category_to_account'] == "on")
        {
            $extra['cdriven'] = true;
        }
        else
        {
            $extra['cdriven'] = false;
        }
        $extra = json_encode($extra);
        $wpdb->insert(
                $table_items_meta, 
                array(
                    'item_id' => $post_ID,
                    'item_type' => $post->post_type,
                    'account_id' => '0',
                    'extra' => $extra
                ),
                array(
                    '%s',
                    '%s',
                    '%s'
                )
        );
        
        $accounts = MicroblogPoster_Poster::get_accounts_all();
        foreach($accounts as $account)
        {
            $checkbox_name = 'mbp_social_account_microblogposter_'.$account['account_id'];
            if(isset($_POST[$checkbox_name]) && trim($_POST[$checkbox_name]) == '1')
            {
                $message_format = trim($_POST['mbp_social_account_microblogposter_msg_'.$account['account_id']]);
                $wpdb->insert(
                        $table_items_meta, 
                        array(
                            'item_id' => $post_ID,
                            'item_type' => $post->post_type,
                            'account_id' => $account['account_id'],
                            'message_format' => $message_format
                        ),
                        array(
                            '%s',
                            '%s',
                            '%s',
                            '%s'
                        )
                );
            }
        }
    }
    
    /**
    * Main function of this plugin called on publish_post action hook
    * 
    *
    * @param   int  $post_ID ID of the new/updated post
    * @return  void
    */
    public static function update($post_ID)
    {
        $post = get_post($post_ID);
        $author_id = (int)$post->post_author;
        if (!MicroblogPoster_Poster::can_user_auto_publish($author_id))
        {
            return;
        }

        //If this time Microblog Poster is disabled then return
	if ($_POST['microblogposteroff'] == "on")
        {
            return;
        }
        $postMeta = false;
        if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','getScheduledMeta'))
        {
            $postMeta = MicroblogPoster_Poster_Pro::getScheduledMeta(0,$post_ID);
            if(is_array($postMeta))
            {
                $extra = json_decode($postMeta['extra'], true);
                if($extra['disabled'])
                {
                    return;
                }
            }
        }
        
        
        $post_categories = wp_get_post_categories($post_ID);
        if(MicroblogPoster_Poster::is_post_in_excluded_category($post_categories))
        {
            return;
        }
        
        $apply_filters = MicroblogPoster_Poster::is_apply_filters_activated();
        
        $shortcode_title_max_length = MicroblogPoster_Poster::get_shortcode_title_max_length();
        $shortcode_firstwords_max_length = MicroblogPoster_Poster::get_shortcode_firstwords_max_length();
        $shortcode_excerpt_max_length = MicroblogPoster_Poster::get_shortcode_excerpt_max_length();
        
        
        if($apply_filters)
        {
            $post_title = apply_filters('the_title', $post->post_title);
        }
        else
        {
            $post_title = $post->post_title;
        }
        $post_title = MicroblogPoster_Poster::shorten_title($post_title, $shortcode_title_max_length, ' ');
        
        
        if($apply_filters)
        {
            $post_content_actual = apply_filters('the_content', $post->post_content);
        }
        else
        {
            $post_content_actual = $post->post_content;
        }
        $post_content_actual_lkn = MicroblogPoster_Poster::clean_up_and_shorten_content($post_content_actual, 350, ' ');
        $post_content_actual_tmb = MicroblogPoster_Poster::shorten_content($post_content_actual, 500, '.');
        
        $permalink = get_permalink($post_ID);
        $permalink_actual = $permalink;
	$update = $post_title . " $permalink";
        
        $post_thumbnail_id = get_post_thumbnail_id($post_ID);
        $featured_image_path_full = MicroblogPoster_Poster::get_featured_image_path_full($post_thumbnail_id);
        $featured_image_src_full = MicroblogPoster_Poster::get_featured_image_src_full($post_thumbnail_id);
        $featured_image_src_thumbnail = MicroblogPoster_Poster::get_featured_image_src_thumbnail($post_thumbnail_id);
        $featured_image_src_medium = MicroblogPoster_Poster::get_featured_image_src_medium($post_thumbnail_id);
        
        $post_content = array();
        $post_content[] = home_url();
        $post_content[] = $post_title;
        $post_content[] = $permalink;
        
        $shortened_permalink = '';
        $shortened_permalink_twitter = '';
        $short_url_results = MicroblogPoster_Poster::shorten_long_url($permalink);
        if($short_url_results['shortened_permalink'])
        {
            $shortened_permalink = $short_url_results['shortened_permalink'];
            $update = $post_title . " $shortened_permalink";
            $permalink = $shortened_permalink;
        }
        if($short_url_results['shortened_permalink_twitter'])
        {
            $shortened_permalink_twitter = $short_url_results['shortened_permalink_twitter'];
        }
        
	$post_content[] = $shortened_permalink;
        
        $post_excerpt_manual = '';
        if($apply_filters)
        {
            $post_excerpt_tmp = apply_filters('the_content', $post->post_excerpt);
            $post_excerpt_tmp = MicroblogPoster_Poster::strip_shortcodes_and_tags($post_excerpt_tmp);
        }
        else
        {
            $post_excerpt_tmp = MicroblogPoster_Poster::strip_shortcodes_and_tags($post->post_excerpt);
        }
        
        if($post_excerpt_tmp)
        {
            $post_excerpt_manual = $post_excerpt_tmp;
            $post_content_actual_lkn = $post_excerpt_tmp;
        }
        $post_content[] = $post_excerpt_manual;
	
        if($post_excerpt_manual != '')
        {
            $post_content[] = $post_excerpt_manual;
        }
        else
        {
            $post_excerpt = MicroblogPoster_Poster::shorten_content($post_content_actual, $shortcode_excerpt_max_length, '.');
            $post_content[] = $post_excerpt;
        }
        
        $author = MicroblogPoster_Poster::get_author($author_id);
        $post_content[] = $author;
        
        $post_content_first_words = MicroblogPoster_Poster::clean_up_and_shorten_content($post_content_actual, $shortcode_firstwords_max_length, ' ');
        $post_content[] = $post_content_first_words;
        $post_content[] = $post_content_actual;
        
        $post_content_twitter = $post_content;
        $post_content_twitter[3] = $shortened_permalink_twitter;
        
        $tags = MicroblogPoster_Poster::get_post_tags($post_ID);
        
        $old = 0;
        
        $dash = 0;
        if(isset($_POST['mbp_control_dashboard_microblogposter']) && trim($_POST['mbp_control_dashboard_microblogposter']) == '1')
        {
            $dash = 1;
        }
        
        $mp = array();
        $mp['val'] = 0;
        $mp['type'] = '';
        
        $cdriven = false;
        if ($dash == 1)
        {
            if (isset($_POST['mbp_microblogposter_category_to_account']) && 
                $_POST['mbp_microblogposter_category_to_account'] == "on")
            {
                $cdriven = true;
            }
        }
        else
        {
            $default_behavior_cat_driven_name = "microblogposter_default_behavior_cat_driven";
            $cdriven = get_option($default_behavior_cat_driven_name, false);
            
            if(is_array($postMeta))
            {
                $extra = json_decode($postMeta['extra'], true);
                $cdriven = $extra['cdriven'];
            }
        }
        
        @ini_set('max_execution_time', '0');
        
        MicroblogPoster_Poster::update_twitter($cdriven, $old, $mp, $dash, $update, $post_content_twitter, $post_ID, $featured_image_src_full);
        MicroblogPoster_Poster::update_plurk($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID);
	MicroblogPoster_Poster::update_delicious($cdriven, $old, $mp, $dash, $post_title, $permalink, $tags, $post_content, $post_ID);
        MicroblogPoster_Poster::update_friendfeed($cdriven, $old, $mp, $dash, $post_title, $permalink, $post_content, $post_ID);
        MicroblogPoster_Poster::update_facebook($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual_lkn, $featured_image_src_full);
        MicroblogPoster_Poster::update_diigo($cdriven, $old, $mp, $dash, $post_title, $permalink, $tags, $post_content, $post_ID);
        MicroblogPoster_Poster::update_linkedin($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual_lkn, $featured_image_src_medium);
        MicroblogPoster_Poster::update_tumblr($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual_tmb, $featured_image_src_full);
        MicroblogPoster_Poster::update_blogger($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual_tmb, $featured_image_src_full);
        MicroblogPoster_Poster::update_instapaper($cdriven, $old, $mp, $dash, $post_title, $permalink, $post_content, $post_ID);
        MicroblogPoster_Poster::update_vkontakte($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual_lkn, $featured_image_src_thumbnail, $permalink_actual);
        MicroblogPoster_Poster::update_xing($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual_tmb);
        MicroblogPoster_Poster::update_pinterest($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink_actual, $post_content_actual_lkn, $featured_image_src_full);
        MicroblogPoster_Poster::update_flickr($cdriven, $old, $mp, $dash, $post_title, $update, $tags, $post_content, $post_ID, $featured_image_path_full, $post_content_actual_lkn);
        MicroblogPoster_Poster::update_wordpress($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink_actual, $post_content_actual_lkn, $featured_image_src_full, $tags);
        MicroblogPoster_Poster::update_googleplus($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink_actual, $post_content_actual_lkn, $tags);
        
        MicroblogPoster_Poster::maintain_logs();
    }
    
    public static function add_new_cron_interval($array)
    {
        $interval = MicroblogPoster_Poster::get_custom_cron_interval();
        $period = (int)$interval*60*60;
        $array['microblogposter_plg_cron_interval'] = array(
            'interval' => $period,
            'display' => 'Every x hours'
        );
        return $array;
    }
    
    /**
    * Main function of this plugin called on publish_post action hook
    * 
    *
    * @param   int  $post_ID ID of the new/updated post
    * @return  void
    */
    public static function get_custom_cron_interval()
    {
        $microblogposter_plg_old_posts_interval_name = "microblogposter_plg_old_posts_interval";
        $interval = $max_age = get_option($microblogposter_plg_old_posts_interval_name, 1);
        return $interval;
    }
    
    /**
    * Main function of this plugin called on publish_post action hook
    * 
    *
    * @param   int  $post_ID ID of the new/updated post
    * @return  void
    */
    public static function handle_old_posts_publish()
    {
        $microblogposter_plg_old_posts_active_name = "microblogposter_plg_old_posts_active";
        $old_posts_active = get_option($microblogposter_plg_old_posts_active_name, 0);
        if($old_posts_active == '0')
        {
            return;
        }
        
        $microblogposter_plg_old_posts_nb_posts_name = "microblogposter_plg_old_posts_nb_posts";
        $microblogposter_plg_old_posts_min_age_name = "microblogposter_plg_old_posts_min_age";
        $microblogposter_plg_old_posts_max_age_name = "microblogposter_plg_old_posts_max_age";
        $microblogposter_plg_old_posts_expire_age_name = "microblogposter_plg_old_posts_expire_age";
        $excluded_categories_old_name = "microblogposter_excluded_categories_old";
        $enabled_custom_types_old_name = "microblogposter_enabled_custom_types_old";
        
        $nb_posts = get_option($microblogposter_plg_old_posts_nb_posts_name, 1);
        $min_age = get_option($microblogposter_plg_old_posts_min_age_name, 30);
        $max_age = get_option($microblogposter_plg_old_posts_max_age_name, 180);
        $expire_age = get_option($microblogposter_plg_old_posts_expire_age_name, 30);
        $excluded_categories_old_value = get_option($excluded_categories_old_name, "");
        $excluded_categories_old = json_decode($excluded_categories_old_value, true);
        $enabled_custom_types_old_value = get_option($enabled_custom_types_old_name, "");
        $enabled_custom_types_old_value = json_decode($enabled_custom_types_old_value, true);
        if(!$enabled_custom_types_old_value)
        {
            $enabled_custom_types_old_value = array('post');
        }

        global  $wpdb;

        $table_old_items = $wpdb->prefix . 'microblogposter_old_items';
        $table_posts = $wpdb->prefix . 'posts';
        $table_term_relationships = $wpdb->prefix . 'term_relationships';
        $table_term_taxonomy = $wpdb->prefix . 'term_taxonomy';
        
        $interval = MicroblogPoster_Poster::get_custom_cron_interval();
        $sql="SELECT * FROM {$table_old_items} WHERE publish_datetime > DATE_SUB(NOW(), INTERVAL {$interval} HOUR)";
        $old_posts_published = $wpdb->get_results($sql, ARRAY_A);
        if(is_array($old_posts_published) && !empty($old_posts_published))
        {
            return;
        }
        
        if(in_array('post', $enabled_custom_types_old_value))
        {
            $sql_old = "SELECT * FROM {$table_posts} AS p WHERE p.post_status = 'publish' AND p.post_type = 'post'";
            if($min_age > 0)
            {
                $sql_old .= " AND p.post_date < DATE_SUB(NOW(), INTERVAL {$min_age} DAY)";
            }
            if($max_age > 0)
            {
                $sql_old .= " AND p.post_date > DATE_SUB(NOW(), INTERVAL {$max_age} DAY)";
            }
            if(is_array($excluded_categories_old) && !empty($excluded_categories_old))
            {
                $excluded_categories_string = "";
                foreach($excluded_categories_old as $excluded_category_old)
                {
                    if(intval($excluded_category_old))
                    {
                        $excluded_categories_string .= $excluded_category_old . ",";
                    }
                }
                $excluded_categories_string = rtrim($excluded_categories_string, ",");

                if($excluded_categories_string)
                {
                    $sql_old .= " AND p.ID NOT IN";
                    $sql_old .= " (SELECT termr.object_id FROM {$table_term_taxonomy} AS termt INNER JOIN {$table_term_relationships} AS termr";
                    $sql_old .= " ON termt.term_taxonomy_id=termr.term_taxonomy_id";
                    $sql_old .= " WHERE termt.term_id IN ({$excluded_categories_string}) AND termt.taxonomy='category')";
                }
            }
            if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Ultimate','resolve_sql_allowed_authors'))
            {
                $sql_old .= MicroblogPoster_Poster_Ultimate::resolve_sql_allowed_authors();
            }

            $sql_old .= " AND p.ID NOT IN (SELECT item_id from {$table_old_items} WHERE item_type='post')";
            $sql_old .= " ORDER BY p.post_date ASC";
            $sql_old .= " LIMIT 10";

            $old_posts = $wpdb->get_results($sql_old, ARRAY_A);

            if(is_array($old_posts) && !empty($old_posts))
            {
                for($i = 0; $i < $nb_posts; $i++)
                {
                    if(isset($old_posts[$i]))
                    {
                        $post_id = $old_posts[$i]['ID'];
                        $sql="INSERT INTO {$table_old_items} (item_id,item_type) 
                               VALUES ('{$post_id}','post')";
                        $wpdb->query($sql);

                        MicroblogPoster_Poster::update_old_post($post_id);
                    }
                }
            }
        }
        if(in_array('page', $enabled_custom_types_old_value))
        {
            $sql_old = "SELECT * FROM {$table_posts} AS p WHERE p.post_status = 'publish' AND p.post_type = 'page'";
            if($min_age > 0)
            {
                $sql_old .= " AND p.post_date < DATE_SUB(NOW(), INTERVAL {$min_age} DAY)";
            }
            if($max_age > 0)
            {
                $sql_old .= " AND p.post_date > DATE_SUB(NOW(), INTERVAL {$max_age} DAY)";
            }
            if(is_array($excluded_categories_old) && !empty($excluded_categories_old))
            {
                $excluded_categories_string = "";
                foreach($excluded_categories_old as $excluded_category_old)
                {
                    if(intval($excluded_category_old))
                    {
                        $excluded_categories_string .= $excluded_category_old . ",";
                    }
                }
                $excluded_categories_string = rtrim($excluded_categories_string, ",");

                if($excluded_categories_string)
                {
                    $sql_old .= " AND p.ID NOT IN";
                    $sql_old .= " (SELECT termr.object_id FROM {$table_term_taxonomy} AS termt INNER JOIN {$table_term_relationships} AS termr";
                    $sql_old .= " ON termt.term_taxonomy_id=termr.term_taxonomy_id";
                    $sql_old .= " WHERE termt.term_id IN ({$excluded_categories_string}) AND termt.taxonomy='category')";
                }
            }
            if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Ultimate','resolve_sql_allowed_authors'))
            {
                $sql_old .= MicroblogPoster_Poster_Ultimate::resolve_sql_allowed_authors();
            }

            $sql_old .= " AND p.ID NOT IN (SELECT item_id from {$table_old_items} WHERE item_type='page')";
            $sql_old .= " ORDER BY p.post_date ASC";
            $sql_old .= " LIMIT 10";

            $old_posts = $wpdb->get_results($sql_old, ARRAY_A);

            if(is_array($old_posts) && !empty($old_posts))
            {
                for($i = 0; $i < $nb_posts; $i++)
                {
                    if(isset($old_posts[$i]))
                    {
                        $post_id = $old_posts[$i]['ID'];
                        $sql="INSERT INTO {$table_old_items} (item_id,item_type) 
                               VALUES ('{$post_id}','page')";
                        $wpdb->query($sql);

                        MicroblogPoster_Poster::update_old_post($post_id);
                    }
                }
            }
        }
        
        if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','handle_old_posts_publish'))
        {
            MicroblogPoster_Poster_Pro::handle_old_posts_publish();
        }
        
        if(intval($expire_age) > 0)
        {
            $sql="DELETE FROM {$table_old_items} WHERE publish_datetime < DATE_SUB(NOW(), INTERVAL {$expire_age} DAY)";
            $wpdb->query($sql);
        }  
    }
    
    /**
    * Main function of this plugin called on publish_post action hook
    * 
    *
    * @param   int  $post_ID ID of the new/updated post
    * @return  void
    */
    public static function update_old_post($post_ID)
    {   
        $shortcode_title_max_length = MicroblogPoster_Poster::get_shortcode_title_max_length();
        $shortcode_firstwords_max_length = MicroblogPoster_Poster::get_shortcode_firstwords_max_length();
        $shortcode_excerpt_max_length = MicroblogPoster_Poster::get_shortcode_excerpt_max_length();
        
        $apply_filters = MicroblogPoster_Poster::is_apply_filters_activated();
        
        $post = get_post($post_ID);
        
        if($apply_filters)
        {
            $post_title = apply_filters('the_title', $post->post_title);
        }
        else
        {
            $post_title = $post->post_title;
        }
        $post_title = MicroblogPoster_Poster::shorten_title($post_title, $shortcode_title_max_length, ' ');
        
        if($apply_filters)
        {
            $post_content_actual = apply_filters('the_content', $post->post_content);
        }
        else
        {
            $post_content_actual = $post->post_content;
        }
        $post_content_actual_lkn = MicroblogPoster_Poster::clean_up_and_shorten_content($post_content_actual, 350, ' ');
        $post_content_actual_tmb = MicroblogPoster_Poster::shorten_content($post_content_actual, 500, '.');
        
        $permalink = get_permalink($post_ID);
        $permalink_actual = $permalink;
	$update = $post_title . " $permalink";
        
        $post_thumbnail_id = get_post_thumbnail_id($post_ID);
        $featured_image_path_full = MicroblogPoster_Poster::get_featured_image_path_full($post_thumbnail_id);
        $featured_image_src_full = MicroblogPoster_Poster::get_featured_image_src_full($post_thumbnail_id);
        $featured_image_src_thumbnail = MicroblogPoster_Poster::get_featured_image_src_thumbnail($post_thumbnail_id);
        $featured_image_src_medium = MicroblogPoster_Poster::get_featured_image_src_medium($post_thumbnail_id);
        
        $post_content = array();
        $post_content[] = home_url();
        $post_content[] = $post_title;
        $post_content[] = $permalink;
        
        $shortened_permalink = '';
        $shortened_permalink_twitter = '';
        $short_url_results = MicroblogPoster_Poster::shorten_long_url($permalink);
        if($short_url_results['shortened_permalink'])
        {
            $shortened_permalink = $short_url_results['shortened_permalink'];
            $update = $post_title . " $shortened_permalink";
            $permalink = $shortened_permalink;
        }
        if($short_url_results['shortened_permalink_twitter'])
        {
            $shortened_permalink_twitter = $short_url_results['shortened_permalink_twitter'];
        }
        
	$post_content[] = $shortened_permalink;
        
        $post_excerpt_manual = '';
        if($apply_filters)
        {
            $post_excerpt_tmp = apply_filters('the_content', $post->post_excerpt);
            $post_excerpt_tmp = MicroblogPoster_Poster::strip_shortcodes_and_tags($post_excerpt_tmp);
        }
        else
        {
            $post_excerpt_tmp = MicroblogPoster_Poster::strip_shortcodes_and_tags($post->post_excerpt);
        }
        if($post_excerpt_tmp)
        {
            $post_excerpt_manual = $post_excerpt_tmp;
            $post_content_actual_lkn = $post_excerpt_tmp;
        }
        $post_content[] = $post_excerpt_manual;
	
        if($post_excerpt_manual != '')
        {
            $post_content[] = $post_excerpt_manual;
        }
        else
        {
            $post_excerpt = MicroblogPoster_Poster::shorten_content($post_content_actual, $shortcode_excerpt_max_length, '.');
            $post_content[] = $post_excerpt;
        }
        
        $author = MicroblogPoster_Poster::get_author((int)$post->post_author);
        $post_content[] = $author;
        
        $post_content_first_words = MicroblogPoster_Poster::clean_up_and_shorten_content($post_content_actual, $shortcode_firstwords_max_length, ' ');
        $post_content[] = $post_content_first_words;
        $post_content[] = $post_content_actual;
        
        $post_content_twitter = $post_content;
        $post_content_twitter[3] = $shortened_permalink_twitter;
        
        $tags = MicroblogPoster_Poster::get_post_tags($post_ID);
        
        $old = 1;
        
        $dash = 1;
        
        $mp = array();
        $mp['val'] = 0;
        $mp['type'] = '';
        
        $microblogposter_plg_old_posts_cat_driven_active_name = "microblogposter_plg_old_posts_cat_driven_active";
        $cdriven = get_option($microblogposter_plg_old_posts_cat_driven_active_name, false);
        
        @ini_set('max_execution_time', '0');
        
        MicroblogPoster_Poster::update_twitter($cdriven, $old, $mp, $dash, $update, $post_content_twitter, $post_ID, $featured_image_src_full);
        MicroblogPoster_Poster::update_plurk($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID);
	MicroblogPoster_Poster::update_delicious($cdriven, $old, $mp, $dash, $post_title, $permalink, $tags, $post_content, $post_ID);
        MicroblogPoster_Poster::update_friendfeed($cdriven, $old, $mp, $dash, $post_title, $permalink, $post_content, $post_ID);
        MicroblogPoster_Poster::update_facebook($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual_lkn, $featured_image_src_full);
        MicroblogPoster_Poster::update_diigo($cdriven, $old, $mp, $dash, $post_title, $permalink, $tags, $post_content, $post_ID);
        MicroblogPoster_Poster::update_linkedin($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual_lkn, $featured_image_src_medium);
        MicroblogPoster_Poster::update_tumblr($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual_tmb, $featured_image_src_full);
        MicroblogPoster_Poster::update_blogger($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual_tmb, $featured_image_src_full);
        MicroblogPoster_Poster::update_instapaper($cdriven, $old, $mp, $dash, $post_title, $permalink, $post_content, $post_ID);
        MicroblogPoster_Poster::update_vkontakte($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual_lkn, $featured_image_src_thumbnail, $permalink_actual);
        MicroblogPoster_Poster::update_xing($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual_tmb);
        MicroblogPoster_Poster::update_pinterest($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink_actual, $post_content_actual_lkn, $featured_image_src_full);
        MicroblogPoster_Poster::update_flickr($cdriven, $old, $mp, $dash, $post_title, $update, $tags, $post_content, $post_ID, $featured_image_path_full, $post_content_actual_lkn);
        MicroblogPoster_Poster::update_wordpress($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink_actual, $post_content_actual_lkn, $featured_image_src_full, $tags);
        MicroblogPoster_Poster::update_googleplus($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink_actual, $post_content_actual_lkn, $tags);
        
        MicroblogPoster_Poster::maintain_logs();
    }
    
    /**
    * Updates status on twitter
    *
    * @param string  $update Text to be posted on microblogging site
    * @param array $post_content
    * @return void
    */
    public static function update_twitter($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $featured_image_src_full)
    {   
        
        $twitter_accounts = MicroblogPoster_Poster::get_accounts_by_mode('twitter', $post_ID);
        
        if(!empty($twitter_accounts))
        {
            foreach($twitter_accounts as $twitter_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($twitter_account['account_id'], $post_ID, $twitter_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $twitter_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($twitter_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $twitter_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($twitter_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $twitter_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($twitter_account['account_id'], $post_ID, $twitter_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $twitter_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($twitter_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $twitter_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($twitter_account['account_id'], $post_ID, $twitter_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($twitter_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $twitter_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                if($twitter_account['message_format'] && $mp['val'] == 0)
                {
                    $twitter_account['message_format'] = str_ireplace('{manual_excerpt}', '', $twitter_account['message_format']);
                    $twitter_account['message_format'] = str_ireplace('{excerpt}', '', $twitter_account['message_format']);
                    $twitter_account['message_format'] = str_ireplace('{content}', '', $twitter_account['message_format']);
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $twitter_account['message_format']);
                }
                elseif($twitter_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $twitter_account['message_format']);
                }
                
                $media_id_string = "";
                $extra = json_decode($twitter_account['extra'], true);
                if(isset($extra) && is_array($extra) && isset($extra['include_featured_image']) && $extra['include_featured_image'] == 1)
                {
                    $include_featured_image = true;
                }
                else
                {
                    $include_featured_image = false;
                }
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','send_signed_request_and_upload') && 
                        $include_featured_image && $featured_image_src_full)
                {
                    $curl = new MicroblogPoster_Curl();
                    $upload_result = MicroblogPoster_Poster_Pro::send_signed_request_and_upload(
                        $curl,
                        $twitter_account['consumer_key'],
                        $twitter_account['consumer_secret'],
                        $twitter_account['access_token'],
                        $twitter_account['access_token_secret'],
                        "https://upload.twitter.com/1.1/media/upload.json",
                        array("image_url"=>$featured_image_src_full)
                    );
                    $upload_result_dec = json_decode($upload_result, true);
                    if(isset($upload_result_dec['media_id_string']))
                    {
                        $media_id_string = $upload_result_dec['media_id_string'];
                    }
                    else
                    {
                        $log_data = array();
                        $log_data['account_id'] = $twitter_account['account_id'];
                        $log_data['account_type'] = "twitter";
                        $log_data['username'] = $twitter_account['username'] . ' - Upload Image';
                        $log_data['post_id'] = 0;
                        $log_data['action_result'] = 2;
                        $log_data['update_message'] = '';
                        $log_data['log_message'] = $upload_result;
                        MicroblogPoster_Poster::insert_log($log_data);
                    }
                }
                
                $parameters = array("status"=>$update);
                if(isset($media_id_string) && $media_id_string)
                {
                    $parameters["media_ids"] = $media_id_string;
                }
                
                $result = MicroblogPoster_Poster::send_signed_request(
                    $twitter_account['consumer_key'],
                    $twitter_account['consumer_secret'],
                    $twitter_account['access_token'],
                    $twitter_account['access_token_secret'],
                    "https://api.twitter.com/1.1/statuses/update.json",
                    $parameters
                );
                
                $action_result = 2;
                $result_dec = json_decode($result, true);
                if($result_dec && isset($result_dec['id']))
                {
                    $action_result = 1;
                    $result = "Success";
                }
                
                $log_data = array();
                $log_data['account_id'] = $twitter_account['account_id'];
                $log_data['account_type'] = "twitter";
                $log_data['username'] = $twitter_account['username'];
                $log_data['post_id'] = $post_ID;
                $log_data['action_result'] = $action_result;
                $log_data['update_message'] = $update;
                $log_data['log_message'] = $result;
                if($mp['val'] == 1)
                {
                    $log_data['log_type'] = 'manual';
                }
                elseif($old == 1)
                {
                    $log_data['log_type'] = 'old';
                }
                MicroblogPoster_Poster::insert_log($log_data);
            }
        }
        
    }
    
    /**
    * Updates status on plurk
    *
    * @param string  $update Text to be posted on microblogging site
    * @param array $post_content
    * @return void
    */
    public static function update_plurk($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID)
    {   
        
        $plurk_accounts = MicroblogPoster_Poster::get_accounts_by_mode('plurk', $post_ID);
        
        if(!empty($plurk_accounts))
        {
            foreach($plurk_accounts as $plurk_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($plurk_account['account_id'], $post_ID, $plurk_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $plurk_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($plurk_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $plurk_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($plurk_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $plurk_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($plurk_account['account_id'], $post_ID, $plurk_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $plurk_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($plurk_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $plurk_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($plurk_account['account_id'], $post_ID, $plurk_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($plurk_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $plurk_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                if($plurk_account['message_format'] && $mp['val'] == 0)
                {
                    $plurk_account['message_format'] = str_ireplace('{manual_excerpt}', '', $plurk_account['message_format']);
                    $plurk_account['message_format'] = str_ireplace('{excerpt}', '', $plurk_account['message_format']);
                    $plurk_account['message_format'] = str_ireplace('{content}', '', $plurk_account['message_format']);
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $plurk_account['message_format']);
                }
                elseif($plurk_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $plurk_account['message_format']);
                }
                
                $qualifier = "says";
                $extra = json_decode($plurk_account['extra'], true);
                if(is_array($extra))
                {    
                    if(isset($extra['qualifier']))
                    {
                        $qualifier = $extra['qualifier'];
                    }
                }
                $result = MicroblogPoster_Poster::send_signed_request(
                    $plurk_account['consumer_key'],
                    $plurk_account['consumer_secret'],
                    $plurk_account['access_token'],
                    $plurk_account['access_token_secret'],
                    "http://www.plurk.com/APP/Timeline/plurkAdd",
                    array("content"=>$update, "qualifier"=>$qualifier)
                );
                
                $action_result = 2;
                $result_dec = json_decode($result, true);
                if($result_dec && isset($result_dec['plurk_id']))
                {
                    $action_result = 1;
                    $result = "Success";
                }
                
                $log_data = array();
                $log_data['account_id'] = $plurk_account['account_id'];
                $log_data['account_type'] = "plurk";
                $log_data['username'] = $plurk_account['username'];
                $log_data['post_id'] = $post_ID;
                $log_data['action_result'] = $action_result;
                $log_data['update_message'] = $update;
                $log_data['log_message'] = $result;
                if($mp['val'] == 1)
                {
                    $log_data['log_type'] = 'manual';
                }
                elseif($old == 1)
                {
                    $log_data['log_type'] = 'old';
                }
                MicroblogPoster_Poster::insert_log($log_data);
            }
        }
        
    }
    
    
    /**
    * Updates status on delicious.com
    *
    * @param   string  $title Text to be posted on microblogging site
    * @param   string  $link
    * @param   string  $tags
    * @param array $post_content 
    * @return  void
    */
    public static function update_delicious($cdriven, $old, $mp, $dash, $title, $link, $tags, $post_content, $post_ID)
    {
	if($mp['val'] == 1 && $mp['type'] == 'text')
        {
            return;
        }
        
        $curl = new MicroblogPoster_Curl();
        $delicious_accounts = MicroblogPoster_Poster::get_accounts_by_mode('delicious', $post_ID);
        
        if(!empty($delicious_accounts))
        {
            foreach($delicious_accounts as $delicious_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($delicious_account['account_id'], $post_ID, $delicious_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $delicious_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($delicious_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $delicious_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($delicious_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $delicious_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($delicious_account['account_id'], $post_ID, $delicious_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $delicious_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($delicious_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $delicious_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($delicious_account['account_id'], $post_ID, $delicious_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($delicious_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $delicious_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                $post_content[8] = MicroblogPoster_Poster::strip_shortcodes_and_tags($post_content[8]);
                if($delicious_account['message_format'] && $mp['val'] == 0)
                {
                    $delicious_account['message_format'] = str_ireplace('{site_url}', '', $delicious_account['message_format']);
                    $delicious_account['message_format'] = str_ireplace('{url}', '', $delicious_account['message_format']);
                    $delicious_account['message_format'] = str_ireplace('{short_url}', '', $delicious_account['message_format']);
                    $descr = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $delicious_account['message_format']);
                }
                elseif($delicious_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $delicious_account['message_format'] = str_ireplace('{url}', '', $delicious_account['message_format']);
                    $delicious_account['message_format'] = str_ireplace('{short_url}', '', $delicious_account['message_format']);
                    $descr = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $delicious_account['message_format']);
                }
                
                $is_raw = MicroblogPoster_SupportEnc::is_enc($delicious_account['extra']);
                if(!$is_raw)
                {
                    $delicious_account['password'] = MicroblogPoster_SupportEnc::dec($delicious_account['password']);
                }
                $extra = json_decode($delicious_account['extra'], true);
                if(is_array($extra))
                {
                    $include_tags = (isset($extra['include_tags']) && $extra['include_tags'] == 1)?true:false;
                }
                $curl->set_credentials($delicious_account['username'],$delicious_account['password']);
                $curl->set_user_agent("Mozilla/6.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1");

                $link_enc=urlencode($link);
                $title_enc = urlencode($title);
                $descr_enc = urlencode($descr);
                $tags_enc = urlencode($tags);
                $update_message = $title." - ".$link;

                $url = "https://api.del.icio.us/v1/posts/add?url={$link_enc}&description={$title_enc}&extended={$descr_enc}&shared=yes";
                if($include_tags)
                {
                    $url .= "&tags=$tags_enc";
                    $update_message .= " ($tags)";
                }
                $result = $curl->fetch_url($url);
                
                $action_result = 2;
                if($result && stripos($result, 'code="done"')!==false)
                {
                    $action_result = 1;
                    $result = "Success";
                }
                
                $log_data = array();
                $log_data['account_id'] = $delicious_account['account_id'];
                $log_data['account_type'] = "delicious";
                $log_data['username'] = $delicious_account['username'];
                $log_data['post_id'] = $post_ID;
                $log_data['action_result'] = $action_result;
                $log_data['update_message'] = $update_message;
                $log_data['log_message'] = $result;
                if($mp['val'] == 1)
                {
                    $log_data['log_type'] = 'manual';
                }
                elseif($old == 1)
                {
                    $log_data['log_type'] = 'old';
                }
                MicroblogPoster_Poster::insert_log($log_data);
            }
        }
        
    }
    
    /**
    * Updates status on http://friendfeed.com/
    *
    * @param   string  $title Text to be posted on microblogging site
    * @param   string  $link
    * @param   array $post_content
    * @return  void
    */
    public static function update_friendfeed($cdriven, $old, $mp, $dash, $title, $link, $post_content, $post_ID)
    {
	
	$curl = new MicroblogPoster_Curl();
        $friendfeed_accounts = MicroblogPoster_Poster::get_accounts_by_mode('friendfeed', $post_ID);
        
        if(!empty($friendfeed_accounts))
        {
            foreach($friendfeed_accounts as $friendfeed_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($friendfeed_account['account_id'], $post_ID, $friendfeed_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $friendfeed_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($friendfeed_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $friendfeed_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($friendfeed_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $friendfeed_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($friendfeed_account['account_id'], $post_ID, $friendfeed_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $friendfeed_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($friendfeed_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $friendfeed_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($friendfeed_account['account_id'], $post_ID, $friendfeed_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($friendfeed_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $friendfeed_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                if($friendfeed_account['message_format'] && $mp['val'] == 0)
                {
                    $friendfeed_account['message_format'] = str_ireplace('{site_url}', '', $friendfeed_account['message_format']);
                    $friendfeed_account['message_format'] = str_ireplace('{url}', '', $friendfeed_account['message_format']);
                    $friendfeed_account['message_format'] = str_ireplace('{short_url}', '', $friendfeed_account['message_format']);
                    $friendfeed_account['message_format'] = str_ireplace('{manual_excerpt}', '', $friendfeed_account['message_format']);
                    $friendfeed_account['message_format'] = str_ireplace('{excerpt}', '', $friendfeed_account['message_format']);
                    $title = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $friendfeed_account['message_format']);
                }
                elseif($friendfeed_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $friendfeed_account['message_format'] = str_ireplace('{url}', '', $friendfeed_account['message_format']);
                    $friendfeed_account['message_format'] = str_ireplace('{short_url}', '', $friendfeed_account['message_format']);
                    $title = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $friendfeed_account['message_format']);
                }
                
                $is_raw = MicroblogPoster_SupportEnc::is_enc($friendfeed_account['extra']);
                if(!$is_raw)
                {
                    $friendfeed_account['password'] = MicroblogPoster_SupportEnc::dec($friendfeed_account['password']);
                }
                $curl->set_credentials($friendfeed_account['username'],$friendfeed_account['password']);
	
                $url = "http://friendfeed-api.com/v2/entry";
                $post_args = array(
                    'body' => $title
                );
                if($mp['val'] == 0 || ($mp['val'] == 1 && $mp['type'] == 'link'))
                {
                    $post_args['link'] = $link;
                }

                $result = $curl->send_post_data($url, $post_args);
                
                $update_message = $title." - ".$link;
                
                $action_result = 2;
                $result_dec = json_decode($result, true);
                if($result_dec && isset($result_dec['id']))
                {
                    $action_result = 1;
                    $result = "Success";
                }
                
                $log_data = array();
                $log_data['account_id'] = $friendfeed_account['account_id'];
                $log_data['account_type'] = "friendfeed";
                $log_data['username'] = $friendfeed_account['username'];
                $log_data['post_id'] = $post_ID;
                $log_data['action_result'] = $action_result;
                $log_data['update_message'] = $update_message;
                $log_data['log_message'] = $result;
                if($mp['val'] == 1)
                {
                    $log_data['log_type'] = 'manual';
                }
                elseif($old == 1)
                {
                    $log_data['log_type'] = 'old';
                }
                MicroblogPoster_Poster::insert_log($log_data);
            }
            
        }
	
    }
    
    /**
    * Updates status on facebook
    *
    * @param string  $update Text to be posted on microblogging site
    * @param array $post_content 
    * @return void
    */
    public static function update_facebook($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual, $featured_image_src)
    {
        
        $curl = new MicroblogPoster_Curl();
        $facebook_accounts = MicroblogPoster_Poster::get_accounts_by_mode('facebook', $post_ID);
        
        if(!empty($facebook_accounts))
        {
            foreach($facebook_accounts as $facebook_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($facebook_account['account_id'], $post_ID, $facebook_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $facebook_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($facebook_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $facebook_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($facebook_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $facebook_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($facebook_account['account_id'], $post_ID, $facebook_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $facebook_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($facebook_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $facebook_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($facebook_account['account_id'], $post_ID, $facebook_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($facebook_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $facebook_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                if(!$facebook_account['extra'])
                {
                    continue;
                }
                
                $post_content[8] = MicroblogPoster_Poster::strip_shortcodes_and_tags($post_content[8]);
                if($facebook_account['message_format'] && $mp['val'] == 0)
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $facebook_account['message_format']);
                }
                elseif($facebook_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $facebook_account['message_format']);
                }
                elseif($mp['val'] == 1 && $mp['type'] == 'text')
                {
                    
                }
                else
                {
                    $update = "";
                }
                
                $extra = json_decode($facebook_account['extra'], true);
                if($mp['val'] == 1 && $mp['type'] == 'text')
                {
                    $extra['post_type'] = 'text';
                }
                
                if(isset($extra['user_id']) && isset($extra['access_token']))
                {
                    
                    $post_data = array();
                    $post_data['update'] = $update;
                    $post_data['post_title'] = $post_title;
                    $post_data['permalink'] = $permalink;
                    $post_data['post_content_actual'] = $post_content_actual;
                    $post_data['featured_image_src'] = $featured_image_src;
                    
                    $acc_extra = $extra;
                    
                    if(isset($extra['target_type']) && $extra['target_type']=='page' && isset($extra['page_id']))
                    {
                        $url = "https://graph.facebook.com/{$extra['page_id']}/feed";
                        $post_args = array(
                            'access_token' => $extra['access_token'],
                            'message' => $update
                        );
                        if(isset($extra['post_type']) && $extra['post_type'] == 'link')
                        {
                            //$post_args['name'] = $post_title;
                            $post_args['link'] = $permalink;
                            //$post_args['description'] = $post_content_actual;
                            $picture_url = '';
                            /*if(isset($extra['default_image_url']) && $extra['default_image_url'])
                            {
                                $picture_url = $extra['default_image_url'];
                            }
                            if($featured_image_src)
                            {
                                $picture_url = $featured_image_src;
                            }
                            $post_args['picture'] = $picture_url;*/
                        }
                        $result = $curl->send_post_data($url, $post_args);
                        
                    }
                    elseif(isset($extra['target_type']) && $extra['target_type']=='group' && isset($extra['group_id']))
                    {
                        if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','update_facebook_group'))
                        {
                            $result = MicroblogPoster_Poster_Pro::update_facebook_group($curl, $acc_extra, $post_data);
                        }
                    }
                    else
                    {
                        $url = "https://graph.facebook.com/{$extra['user_id']}/feed";
                        $post_args = array(
                            'access_token' => $extra['access_token'],
                            'message' => $update
                        );

                        if(isset($extra['post_type']) && $extra['post_type'] == 'link')
                        {
                            //$post_args['name'] = $post_title;
                            $post_args['link'] = $permalink;
                            //$post_args['description'] = $post_content_actual;
                            /*$picture_url = '';
                            if(isset($extra['default_image_url']) && $extra['default_image_url'])
                            {
                                $picture_url = $extra['default_image_url'];
                            }
                            if($featured_image_src)
                            {
                                $picture_url = $featured_image_src;
                            }
                            $post_args['picture'] = $picture_url;*/
                        }

                        $result = $curl->send_post_data($url, $post_args);
                        
                    }
                    
                    $result_dec = json_decode($result, true);
                    
                    $action_result = 2;
                    if($result_dec && isset($result_dec['id']))
                    {
                        $action_result = 1;
                        $result = "Success";
                    }

                    $log_data = array();
                    $log_data['account_id'] = $facebook_account['account_id'];
                    $log_data['account_type'] = "facebook";
                    $log_data['username'] = $facebook_account['username'];
                    $log_data['post_id'] = $post_ID;
                    $log_data['action_result'] = $action_result;
                    $log_data['update_message'] = $update;
                    $log_data['log_message'] = $result;
                    if($mp['val'] == 1)
                    {
                        $log_data['log_type'] = 'manual';
                    }
                    elseif($old == 1)
                    {
                        $log_data['log_type'] = 'old';
                    }
                    MicroblogPoster_Poster::insert_log($log_data);
                }
                
            }
            
        }
    }
    
    /**
    * Updates status on diigo.com
    *
    * @param   string  $title Text to be posted on microblogging site
    * @param   string  $link
    * @param   string  $tags
    * @param array $post_content 
    * @return  void
    */
    public static function update_diigo($cdriven, $old, $mp, $dash, $title, $link, $tags, $post_content, $post_ID)
    {
	if($mp['val'] == 1 && $mp['type'] == 'text')
        {
            return;
        }
        
        $curl = new MicroblogPoster_Curl();
        $diigo_accounts = MicroblogPoster_Poster::get_accounts_by_mode('diigo', $post_ID);
        
        if(!empty($diigo_accounts))
        {
            foreach($diigo_accounts as $diigo_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($diigo_account['account_id'], $post_ID, $diigo_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $diigo_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($diigo_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $diigo_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($diigo_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $diigo_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($diigo_account['account_id'], $post_ID, $diigo_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $diigo_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($diigo_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $diigo_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($diigo_account['account_id'], $post_ID, $diigo_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($diigo_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $diigo_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                $post_content[8] = MicroblogPoster_Poster::strip_shortcodes_and_tags($post_content[8]);
                if($diigo_account['message_format'] && $mp['val'] == 0)
                {
                    $diigo_account['message_format'] = str_ireplace('{site_url}', '', $diigo_account['message_format']);
                    $diigo_account['message_format'] = str_ireplace('{url}', '', $diigo_account['message_format']);
                    $diigo_account['message_format'] = str_ireplace('{short_url}', '', $diigo_account['message_format']);
                    $descr = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $diigo_account['message_format']);
                }
                elseif($diigo_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $diigo_account['message_format'] = str_ireplace('{url}', '', $diigo_account['message_format']);
                    $diigo_account['message_format'] = str_ireplace('{short_url}', '', $diigo_account['message_format']);
                    $descr = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $diigo_account['message_format']);
                }
                
                $is_raw = MicroblogPoster_SupportEnc::is_enc($diigo_account['extra']);
                if(!$is_raw)
                {
                    $diigo_account['password'] = MicroblogPoster_SupportEnc::dec($diigo_account['password']);
                }
                $extra = json_decode($diigo_account['extra'], true);
                if(is_array($extra))
                {
                    $include_tags = (isset($extra['include_tags']) && $extra['include_tags'] == 1)?true:false;
                    $api_key = $extra['api_key'];
                }
                $curl->set_credentials($diigo_account['username'], $diigo_account['password']);
                $curl->set_user_agent("Mozilla/6.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1");

                $update_message = $descr." - ".$link;

                $url = "https://secure.diigo.com/api/v2/bookmarks";
                $post_args = array(
                    'key' => $api_key,
                    'title' => $title,
                    'desc' => $descr,
                    'url' => $link,
                    'shared' => 'yes'
                );
                if($include_tags)
                {
                    $post_args['tags'] = $tags;
                    $update_message .= " ($tags)";
                }
                $result = $curl->send_post_data($url, $post_args);
                $result_dec = json_decode($result, true);
                
                $action_result = 2;
                if($result_dec && isset($result_dec['code']) && $result_dec['code'] == 1)
                {
                    $action_result = 1;
                    $result = "Success";
                }
                else
                {
                    $result = "Please recheck your username/password and API Key.";
                }
                
                $log_data = array();
                $log_data['account_id'] = $diigo_account['account_id'];
                $log_data['account_type'] = "diigo";
                $log_data['username'] = $diigo_account['username'];
                $log_data['post_id'] = $post_ID;
                $log_data['action_result'] = $action_result;
                $log_data['update_message'] = $update_message;
                $log_data['log_message'] = $result;
                if($mp['val'] == 1)
                {
                    $log_data['log_type'] = 'manual';
                }
                elseif($old == 1)
                {
                    $log_data['log_type'] = 'old';
                }
                MicroblogPoster_Poster::insert_log($log_data);
            }
        }
        
    }
    
    /**
    * Updates status on linkedin
    *
    * @param string  $update Text to be posted on microblogging site
    * @param array $post_content 
    * @return void
    */
    public static function update_linkedin($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual, $featured_image_src)
    {
        if($mp['val'] == 1 && $mp['type'] == 'text')
        {
            return;
        }
        
        $curl = new MicroblogPoster_Curl();
        $linkedin_accounts = MicroblogPoster_Poster::get_accounts_by_mode('linkedin', $post_ID);
        
        if(!empty($linkedin_accounts))
        {
            foreach($linkedin_accounts as $linkedin_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($linkedin_account['account_id'], $post_ID, $linkedin_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $linkedin_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($linkedin_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $linkedin_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($linkedin_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $linkedin_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($linkedin_account['account_id'], $post_ID, $linkedin_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $linkedin_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($linkedin_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $linkedin_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($linkedin_account['account_id'], $post_ID, $linkedin_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($linkedin_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $linkedin_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                if(!$linkedin_account['extra'])
                {
                    continue;
                }
                
                if($linkedin_account['message_format'] && $mp['val'] == 0)
                {
                    $linkedin_account['message_format'] = str_ireplace('{content}', '', $linkedin_account['message_format']);
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $linkedin_account['message_format']);
                }
                elseif($linkedin_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $linkedin_account['message_format']);
                }
                elseif($mp['val'] == 1 && $mp['type'] == 'text')
                {
                    
                }
                else
                {
                    $update = "";
                }
                
                $extra = json_decode($linkedin_account['extra'], true);
                
                if(isset($extra['access_token']))
                {
                    
                    $post_data = array();
                    $post_data['update'] = $update;
                    $post_data['post_title'] = $post_title;
                    $post_data['permalink'] = $permalink;
                    $post_data['post_content_actual'] = $post_content_actual;
                    $post_data['featured_image_src'] = $featured_image_src;
                    
                    $acc_extra = $extra;
                    
                    if(isset($extra['target_type']) && $extra['target_type']=='group' && isset($extra['group_id']))
                    {
                        if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','update_linkedin_group'))
                        {
                            $result = MicroblogPoster_Poster_Pro::update_linkedin_group($curl, $acc_extra, $post_data);
                        }
                        
                        $action_result = 2;
                        if(!$result)
                        {
                            $action_result = 1;
                            $result = "Success";
                        }
                    }
                    elseif(isset($extra['target_type']) && $extra['target_type']=='company' && isset($extra['company_id']))
                    {
                        if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','update_linkedin_company'))
                        {
                            $result = MicroblogPoster_Poster_Pro::update_linkedin_company($curl, $acc_extra, $post_data);
                        }
                        
                        $action_result = 2;
                        if($result && stripos($result, '<update-key>')!==false && stripos($result, '</update-key>')!==false)
                        {
                            $action_result = 1;
                            $result = "Success";
                        }
                    }
                    else
                    {
                        $url = "https://api.linkedin.com/v1/people/~/shares/?oauth2_access_token={$extra['access_token']}";
                        
                        $body = new stdClass();
                        $body->comment = $update;

                        if(isset($extra['post_type']) && $extra['post_type'] == 'link')
                        {
                            $body->content = new stdClass();
                            $body->content->title = $post_title;
                            $body->content->{'submitted-url'} = $permalink;
                            $body->content->description = $post_content_actual;
                            $picture_url = 'http://localhost/imageplaceholder.jpg';// 180 wid, 110 hei
                            if(isset($extra['default_image_url']) && $extra['default_image_url'])
                            {
                                $picture_url = $extra['default_image_url'];
                            }
                            if($featured_image_src)
                            {
                                $picture_url = $featured_image_src;
                            }
                            $body->content->{'submitted-image-url'} = $picture_url;
                        }

                        $body->visibility = new stdClass();
                        $body->visibility->code = 'anyone';
                        $body_json = json_encode($body);

                        $curl->set_headers(array('Content-Type'=>'application/json'));
                        $result = $curl->send_post_data_json($url, $body_json);
                        
                        $action_result = 2;
                        if($result && stripos($result, '<update-key>')!==false && stripos($result, '</update-key>')!==false)
                        {
                            $action_result = 1;
                            $result = "Success";
                        }
                    }
                    
                    
                    $log_data = array();
                    $log_data['account_id'] = $linkedin_account['account_id'];
                    $log_data['account_type'] = "linkedin";
                    $log_data['username'] = $linkedin_account['username'];
                    $log_data['post_id'] = $post_ID;
                    $log_data['action_result'] = $action_result;
                    $log_data['update_message'] = $update;
                    $log_data['log_message'] = $result;
                    if($mp['val'] == 1)
                    {
                        $log_data['log_type'] = 'manual';
                    }
                    elseif($old == 1)
                    {
                        $log_data['log_type'] = 'old';
                    }
                    MicroblogPoster_Poster::insert_log($log_data);
                }
                
            }
            
        }
    }
    
    /**
    * Updates status on tumblr
    *
    * @param string  $update Text to be posted on microblogging site
    * @param array $post_content
    * @return void
    */
    public static function update_tumblr($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual, $featured_image_src_full)
    {   
        
        $tumblr_accounts = MicroblogPoster_Poster::get_accounts_by_mode('tumblr', $post_ID);
        
        if(!empty($tumblr_accounts))
        {
            foreach($tumblr_accounts as $tumblr_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($tumblr_account['account_id'], $post_ID, $tumblr_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $tumblr_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($tumblr_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $tumblr_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($tumblr_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $tumblr_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($tumblr_account['account_id'], $post_ID, $tumblr_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $tumblr_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($tumblr_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $tumblr_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($tumblr_account['account_id'], $post_ID, $tumblr_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($tumblr_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $tumblr_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                if($tumblr_account['message_format'] && $mp['val'] == 0)
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $tumblr_account['message_format']);
                }
                elseif($tumblr_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $tumblr_account['message_format']);
                }
                
                $extra = json_decode($tumblr_account['extra'], true);
                if(!$extra)
                {
                    continue;
                }
                if(isset($extra['include_featured_image']) && $extra['include_featured_image'] == 1 && 
                    $featured_image_src_full && $extra['post_type'] == 'text')
                {
                    $extra['post_type'] = 'photo';
                }
                if($mp['val'] == 1 && $mp['type'] == 'text')
                {
                    $extra['post_type'] = 'text';
                }
                
                $post_data = array();
                $post_data['update'] = $update;
                $post_data['post_title'] = $post_title;
                $post_data['permalink'] = $permalink;
                $post_data['post_content_actual'] = $post_content_actual;
                
                $acc_extra = $extra;
                
                if($extra['post_type'] == 'text')
                {
                    $result = MicroblogPoster_Poster::send_signed_request(
                        $tumblr_account['consumer_key'],
                        $tumblr_account['consumer_secret'],
                        $tumblr_account['access_token'],
                        $tumblr_account['access_token_secret'],
                        "http://api.tumblr.com/v2/blog/{$extra['blog_hostname']}/post",
                        array("type"=>'text',"title"=>$post_title,"body"=>$update)
                    );
                }
                elseif($extra['post_type'] == 'photo')
                {
                    $result = MicroblogPoster_Poster::send_signed_request(
                        $tumblr_account['consumer_key'],
                        $tumblr_account['consumer_secret'],
                        $tumblr_account['access_token'],
                        $tumblr_account['access_token_secret'],
                        "http://api.tumblr.com/v2/blog/{$extra['blog_hostname']}/post",
                        array("type"=>'photo', "caption"=>$update, "source"=>$featured_image_src_full)
                    );
                }
                elseif($extra['post_type'] == 'link')
                {
                    if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','update_tumblr_link'))
                    {
                        $result = MicroblogPoster_Poster_Pro::update_tumblr_link($tumblr_account, $acc_extra, $post_data);
                    }
                }
                
                $action_result = 2;
                $result_dec = json_decode($result, true);
                if($result_dec && isset($result_dec['meta']['msg']) && $result_dec['meta']['msg']=="Created")
                {
                    $action_result = 1;
                    $result = "Success";
                }
                
                $log_data = array();
                $log_data['account_id'] = $tumblr_account['account_id'];
                $log_data['account_type'] = "tumblr";
                $log_data['username'] = $tumblr_account['username'];
                $log_data['post_id'] = $post_ID;
                $log_data['action_result'] = $action_result;
                $log_data['update_message'] = $update;
                $log_data['log_message'] = $result;
                if($mp['val'] == 1)
                {
                    $log_data['log_type'] = 'manual';
                }
                elseif($old == 1)
                {
                    $log_data['log_type'] = 'old';
                }
                MicroblogPoster_Poster::insert_log($log_data);
            }
        }
        
    }
    
    /**
    * Make new post to blogger blogs
    *
    * @param string  $update Text to be posted on microblogging site
    * @param array $post_content
    * @return void
    */
    public static function update_blogger($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual, $featured_image_src_full)
    {
        $blogger_accounts = MicroblogPoster_Poster::get_accounts_by_mode('blogger', $post_ID);
        
        if(!empty($blogger_accounts))
        {
            if($mp['val'] == 0)
            {
                $post_content[2] = '<a href="'.$post_content[2].'">'.$post_content[2].'</a>';
                $post_content[3] = '<a href="'.$post_content[3].'">'.$post_content[3].'</a>';
            }
            elseif($mp['val'] == 1 && $mp['type'] == 'link')
            {
                $post_content[1] = '<a href="'.$post_content[1].'">'.$post_content[1].'</a>';
                $post_content[2] = '<a href="'.$post_content[2].'">'.$post_content[2].'</a>';
            }
            
            foreach($blogger_accounts as $blogger_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($blogger_account['account_id'], $post_ID, $blogger_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $blogger_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($blogger_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $blogger_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($blogger_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $blogger_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($blogger_account['account_id'], $post_ID, $blogger_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $blogger_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($blogger_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $blogger_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($blogger_account['account_id'], $post_ID, $blogger_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($blogger_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $blogger_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                $post_content[8] = nl2br($post_content[8]);
                if($blogger_account['message_format'] && $mp['val'] == 0)
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $blogger_account['message_format']);
                }
                elseif($blogger_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $blogger_account['message_format']);
                }
                
                $extra = json_decode($blogger_account['extra'], true);
                if(!$extra)
                {
                    continue;
                }
                if(isset($extra['include_featured_image']) && $extra['include_featured_image'] == 1 && $featured_image_src_full)
                {
                    $featured_image_html = '<div style="margin-bottom:15px;"><a href="'.$featured_image_src_full.'"><img src="'.$featured_image_src_full.'"/></a></div>';
                    $update = $featured_image_html . $update;
                }
                
                
                $url = "https://accounts.google.com/o/oauth2/token";
                $post_args = array(
                    'refresh_token' => $extra['refresh_token'],
                    'grant_type' => 'refresh_token',
                    'client_id' => $blogger_account['consumer_key'],
                    'client_secret' => $blogger_account['consumer_secret']
                );
                $curl = new MicroblogPoster_Curl();
                $json_res = $curl->send_post_data($url, $post_args);
                $response = json_decode($json_res, true);

                if(isset($response['access_token']) && isset($response['token_type']) && $response['token_type'] == 'Bearer')
                {
                    $access_token_short = $response['access_token'];
                    $blogger_end_point = 'https://www.googleapis.com/blogger/v3/blogs/'.$extra['blog_id'].'/posts/';
                    $access_token = "Bearer " . $access_token_short;
                    $body = new stdClass();
                    $body->kind = 'blogger#post';
                    $body->title = $post_title;
                    $body->content = $update;
                    $body->blog = new stdClass();
                    $body->blog->id = $extra['blog_id'];
                    $body_json = json_encode($body);
                    $headers = array(
                        'Authorization' => $access_token,
                        'Content-type'  => 'application/json',
                    );
                    $curl->set_headers($headers);
                    $result = $curl->send_post_data_json($blogger_end_point, $body_json);
                    $result_dec = json_decode($result, true);
                    $action_result = 2;
                    if($result_dec && isset($result_dec['kind']) && $result_dec['kind']=='blogger#post')
                    {
                        $action_result = 1;
                        $result = "Success";
                    }

                    $log_data = array();
                    $log_data['account_id'] = $blogger_account['account_id'];
                    $log_data['account_type'] = "blogger";
                    $log_data['username'] = $blogger_account['username'];
                    $log_data['post_id'] = $post_ID;
                    $log_data['action_result'] = $action_result;
                    $log_data['update_message'] = $update;
                    $log_data['log_message'] = $result;
                    if($mp['val'] == 1)
                    {
                        $log_data['log_type'] = 'manual';
                    }
                    elseif($old == 1)
                    {
                        $log_data['log_type'] = 'old';
                    }
                    MicroblogPoster_Poster::insert_log($log_data);
                }
                else
                {
                    $log_data = array();
                    $log_data['account_id'] = $blogger_account['account_id'];
                    $log_data['account_type'] = "blogger";
                    $log_data['username'] = $blogger_account['username'];
                    $log_data['post_id'] = $post_ID;
                    $log_data['action_result'] = 2;
                    $log_data['update_message'] = $update;
                    $log_data['log_message'] = $json_res;
                    if($mp['val'] == 1)
                    {
                        $log_data['log_type'] = 'manual';
                    }
                    elseif($old == 1)
                    {
                        $log_data['log_type'] = 'old';
                    }
                    MicroblogPoster_Poster::insert_log($log_data);
                }
            }
        }
    }
    
    /**
    * Updates status on instapaper.com
    *
    * @param   string  $title Text to be posted on microblogging site
    * @param   string  $link
    * @param   string  $tags
    * @param array $post_content 
    * @return  void
    */
    public static function update_instapaper($cdriven, $old, $mp, $dash, $title, $link, $post_content, $post_ID)
    {
	if($mp['val'] == 1 && $mp['type'] == 'text')
        {
            return;
        }
        
        $curl = new MicroblogPoster_Curl();
        $instapaper_accounts = MicroblogPoster_Poster::get_accounts_by_mode('instapaper', $post_ID);
        
        if(!empty($instapaper_accounts))
        {
            foreach($instapaper_accounts as $instapaper_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($instapaper_account['account_id'], $post_ID, $instapaper_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $instapaper_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($instapaper_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $instapaper_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($instapaper_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $instapaper_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($instapaper_account['account_id'], $post_ID, $instapaper_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $instapaper_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($instapaper_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $instapaper_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($instapaper_account['account_id'], $post_ID, $instapaper_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($instapaper_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $instapaper_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                $post_content[8] = MicroblogPoster_Poster::strip_shortcodes_and_tags($post_content[8]);
                if($instapaper_account['message_format'] && $mp['val'] == 0)
                {
                    $instapaper_account['message_format'] = str_ireplace('{site_url}', '', $instapaper_account['message_format']);
                    $instapaper_account['message_format'] = str_ireplace('{url}', '', $instapaper_account['message_format']);
                    $instapaper_account['message_format'] = str_ireplace('{short_url}', '', $instapaper_account['message_format']);
                    $descr = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $instapaper_account['message_format']);
                }
                elseif($instapaper_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $instapaper_account['message_format'] = str_ireplace('{url}', '', $instapaper_account['message_format']);
                    $instapaper_account['message_format'] = str_ireplace('{short_url}', '', $instapaper_account['message_format']);
                    $descr = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $instapaper_account['message_format']);
                }
                
                $is_raw = MicroblogPoster_SupportEnc::is_enc($instapaper_account['extra']);
                if(!$is_raw)
                {
                    $instapaper_account['password'] = MicroblogPoster_SupportEnc::dec($instapaper_account['password']);
                }
                
                $curl->set_credentials($instapaper_account['username'], $instapaper_account['password']);
                $curl->set_user_agent("Mozilla/6.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1");

                $update_message = $descr." - ".$link;

                $url = "https://www.instapaper.com/api/add";
                $post_args = array(
                    'title' => $title,
                    'selection' => $descr,
                    'url' => $link
                );
                
                $result = $curl->send_post_data($url, $post_args);
                $result_dec = json_decode($result, true);
                $action_result = 2;
                if($result_dec && isset($result_dec['bookmark_id']))
                {
                    $action_result = 1;
                    $result = "Success";
                }
                elseif($result == 400)
                {
                    $result = "Bad request or exceeded the rate limit.";
                }
                elseif($result == 403)
                {
                    $result = "Please recheck your username/password.";
                }
                elseif($result == 500)
                {
                    $result = "The service encountered an error. Please try again later.";
                }
                else
                {
                    $result = "Unknown error.";
                }
                
                $log_data = array();
                $log_data['account_id'] = $instapaper_account['account_id'];
                $log_data['account_type'] = "instapaper";
                $log_data['username'] = $instapaper_account['username'];
                $log_data['post_id'] = $post_ID;
                $log_data['action_result'] = $action_result;
                $log_data['update_message'] = $update_message;
                $log_data['log_message'] = $result;
                if($mp['val'] == 1)
                {
                    $log_data['log_type'] = 'manual';
                }
                elseif($old == 1)
                {
                    $log_data['log_type'] = 'old';
                }
                MicroblogPoster_Poster::insert_log($log_data);
            }
        }
        
    }
    
    /**
    * Updates status on vkontakte
    *
    * @param string  $update Text to be posted on microblogging site
    * @param array $post_content 
    * @return void
    */
    public static function update_vkontakte($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual, $featured_image_src, $permalink_actual)
    {
        
        $curl = new MicroblogPoster_Curl();
        $vkontakte_accounts = MicroblogPoster_Poster::get_accounts_by_mode('vkontakte', $post_ID);
        
        if(!empty($vkontakte_accounts))
        {
            foreach($vkontakte_accounts as $vkontakte_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($vkontakte_account['account_id'], $post_ID, $vkontakte_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $vkontakte_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($vkontakte_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $vkontakte_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($vkontakte_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $vkontakte_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($vkontakte_account['account_id'], $post_ID, $vkontakte_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $vkontakte_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($vkontakte_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $vkontakte_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($vkontakte_account['account_id'], $post_ID, $vkontakte_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($vkontakte_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $vkontakte_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                if(!$vkontakte_account['extra'])
                {
                    continue;
                }
                
                $post_content[8] = MicroblogPoster_Poster::strip_shortcodes_and_tags($post_content[8]);
                if($vkontakte_account['message_format'] && $mp['val'] == 0)
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $vkontakte_account['message_format']);
                }
                elseif($vkontakte_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $vkontakte_account['message_format']);
                }
                elseif($mp['val'] == 1 && $mp['type'] == 'text')
                {
                    
                }
                else
                {
                    $update = "";
                }
                
                $extra = json_decode($vkontakte_account['extra'], true);
                if($mp['val'] == 1 && $mp['type'] == 'text')
                {
                    $extra['post_type'] = 'text';
                }
                
                if(isset($extra['target_id']) && isset($extra['access_token']))
                {
                    
                    $post_data = array();
                    $post_data['message'] = $update;
                    $post_data['attachments'] = $permalink_actual;
                    
                    $acc_extra = $extra;
                    
                    if(isset($extra['target_type']) && in_array($extra['target_type'], array('group')) )
                    {
                        if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','update_vkontakte_community'))
                        {
                            $result = MicroblogPoster_Poster_Pro::update_vkontakte_community($curl, $acc_extra, $post_data);
                        }
                    }
                    elseif( isset($extra['target_type']) && in_array($extra['target_type'], array('profile','page','event')) )
                    {
                        $url = "https://api.vk.com/method/wall.post";
                        $post_args = array(
                            'access_token' => $extra['access_token'],
                            'owner_id' => $extra['target_id'],
                            'message' => $update
                        );
                        if(in_array($extra['target_type'], array('page','event')))
                        {
                            $post_args['owner_id'] = '-'.$extra['target_id'];
                            $post_args['from_group'] = '1';
                        }

                        if(isset($extra['post_type']) && $extra['post_type'] == 'link')
                        {
                            $post_args['attachments'] = $permalink_actual;
                        }

                        $result = $curl->send_post_data($url, $post_args);
                    }
                    
                    $result_dec = json_decode($result, true);
                    
                    $action_result = 2;
                    if($result_dec && isset($result_dec['response']['post_id']))
                    {
                        $action_result = 1;
                        $result = "Success";
                    }

                    $log_data = array();
                    $log_data['account_id'] = $vkontakte_account['account_id'];
                    $log_data['account_type'] = "vkontakte";
                    $log_data['username'] = $vkontakte_account['username'];
                    $log_data['post_id'] = $post_ID;
                    $log_data['action_result'] = $action_result;
                    $log_data['update_message'] = $update;
                    $log_data['log_message'] = $result;
                    if($mp['val'] == 1)
                    {
                        $log_data['log_type'] = 'manual';
                    }
                    elseif($old == 1)
                    {
                        $log_data['log_type'] = 'old';
                    }
                    MicroblogPoster_Poster::insert_log($log_data);
                }
            }
        }
    }
    
    /**
    * Updates status on tumblr
    *
    * @param string  $update Text to be posted on microblogging site
    * @param array $post_content
    * @return void
    */
    public static function update_xing($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual)
    {   
        
        $xing_accounts = MicroblogPoster_Poster::get_accounts_by_mode('xing', $post_ID);
        
        if(!empty($xing_accounts))
        {
            foreach($xing_accounts as $xing_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($xing_account['account_id'], $post_ID, $xing_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $xing_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($xing_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $xing_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($xing_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $xing_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($xing_account['account_id'], $post_ID, $xing_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $xing_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($xing_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $xing_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($xing_account['account_id'], $post_ID, $xing_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($xing_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $xing_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                if($xing_account['message_format'] && $mp['val'] == 0)
                {
                    $xing_account['message_format'] = str_ireplace('{content}', '', $xing_account['message_format']);
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $xing_account['message_format']);
                }
                elseif($xing_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $xing_account['message_format']);
                }
                
                $extra = json_decode($xing_account['extra'], true);
                if(!$extra)
                {
                    continue;
                }
                if($mp['val'] == 1 && $mp['type'] == 'text')
                {
                    $extra['post_type'] = 'text';
                }
                
                if($extra['post_type'] == 'text')
                {
                    $result = MicroblogPoster_Poster::send_signed_request(
                        $xing_account['consumer_key'],
                        $xing_account['consumer_secret'],
                        $xing_account['access_token'],
                        $xing_account['access_token_secret'],
                        "https://api.xing.com/v1/users/{$extra['user_id']}/status_message",
                        array("id"=>$extra['user_id'],"message"=>$update)
                    );
                }
                elseif($extra['post_type'] == 'link')
                {
                    $result = MicroblogPoster_Poster::send_signed_request(
                        $xing_account['consumer_key'],
                        $xing_account['consumer_secret'],
                        $xing_account['access_token'],
                        $xing_account['access_token_secret'],
                        "https://api.xing.com/v1/users/me/share/link",
                        array("uri"=>$permalink,"text"=>$update)
                    );
                }
                
                $action_result = 2;
                $result_dec = json_decode($result, true);
                if(($result && $result == 'Status update has been posted') ||
                    ($result_dec && isset($result_dec['ids'])))
                {
                    $action_result = 1;
                    $result = "Success";
                }
                
                $log_data = array();
                $log_data['account_id'] = $xing_account['account_id'];
                $log_data['account_type'] = "xing";
                $log_data['username'] = $xing_account['username'];
                $log_data['post_id'] = $post_ID;
                $log_data['action_result'] = $action_result;
                $log_data['update_message'] = $update;
                $log_data['log_message'] = $result;
                if($mp['val'] == 1)
                {
                    $log_data['log_type'] = 'manual';
                }
                elseif($old == 1)
                {
                    $log_data['log_type'] = 'old';
                }
                MicroblogPoster_Poster::insert_log($log_data);
            }
        }
        
    }
    
    /**
    * Make new post to blogger blogs
    *
    * @param string  $update Text to be posted on microblogging site
    * @param array $post_content
    * @return void
    */
    public static function update_pinterest($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual, $featured_image_src_full)
    {
        $curl = new MicroblogPoster_Curl();
        $pinterest_accounts = MicroblogPoster_Poster::get_accounts_by_mode('pinterest', $post_ID);
        
        if(!empty($pinterest_accounts))
        {
            
            foreach($pinterest_accounts as $pinterest_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($pinterest_account['account_id'], $post_ID, $pinterest_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $pinterest_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($pinterest_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $pinterest_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($pinterest_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $pinterest_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($pinterest_account['account_id'], $post_ID, $pinterest_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $pinterest_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($pinterest_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $pinterest_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($pinterest_account['account_id'], $post_ID, $pinterest_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($pinterest_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $pinterest_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                $post_content[8] = MicroblogPoster_Poster::strip_shortcodes_and_tags($post_content[8]);
                if($pinterest_account['message_format'] && $mp['val'] == 0)
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $pinterest_account['message_format']);
                }
                elseif($pinterest_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $pinterest_account['message_format']);
                }
                
                $extra = json_decode($pinterest_account['extra'], true);
                if(!$extra)
                {
                    continue;
                }

                if(isset($extra['access_token']) && $featured_image_src_full)
                {
                    $access_token_short = $extra['access_token'];
                    $pinterest_end_point = 'https://api.pinterest.com/v1/pins/';
                    $access_token = "Bearer " . $access_token_short;
                    $body = new stdClass();
                    if(isset($extra['user_name']))
                    {
                        $pinterest_account_username = $extra['user_name'];
                    }
                    else
                    {
                        $pinterest_account_username = $pinterest_account['username'];
                    }
                    $body->board = $pinterest_account_username.'/'.$extra['board_name'];
                    $body->note = $update;
                    $body->link = $permalink;
                    $body->image_url = $featured_image_src_full;
                    
                    $body_json = json_encode($body);
                    $headers = array(
                        'Authorization' => $access_token,
                        'Content-type'  => 'application/json',
                    );
                    $curl->set_headers($headers);
                    $result = $curl->send_post_data_json($pinterest_end_point, $body_json);
                    $result_dec = json_decode($result, true);
                    $action_result = 2;
                    if($result_dec && isset($result_dec['data']['id']))
                    {
                        $action_result = 1;
                        $result = "Success";
                    }

                    $log_data = array();
                    $log_data['account_id'] = $pinterest_account['account_id'];
                    $log_data['account_type'] = "pinterest";
                    $log_data['username'] = $pinterest_account['username'];
                    $log_data['post_id'] = $post_ID;
                    $log_data['action_result'] = $action_result;
                    $log_data['update_message'] = $update;
                    $log_data['log_message'] = $result;
                    if($mp['val'] == 1)
                    {
                        $log_data['log_type'] = 'manual';
                    }
                    elseif($old == 1)
                    {
                        $log_data['log_type'] = 'old';
                    }
                    MicroblogPoster_Poster::insert_log($log_data);
                }
                else
                {
                    $log_data = array();
                    $log_data['account_id'] = $pinterest_account['account_id'];
                    $log_data['account_type'] = "pinterest";
                    $log_data['username'] = $pinterest_account['username'];
                    $log_data['post_id'] = $post_ID;
                    $log_data['action_result'] = 2;
                    $log_data['update_message'] = $update;
                    $log_data['log_message'] = 'Account not authorized OR Featured Image is missing.';
                    if($mp['val'] == 1)
                    {
                        $log_data['log_type'] = 'manual';
                    }
                    elseif($old == 1)
                    {
                        $log_data['log_type'] = 'old';
                    }
                    MicroblogPoster_Poster::insert_log($log_data);
                }
                
            }
        }
    }
    
    public static function update_flickr($cdriven, $old, $mp, $dash, $post_title, $update, $tags, $post_content, $post_ID, $featured_image_path_full, $post_content_actual)
    {   
        
        $flickr_accounts = MicroblogPoster_Poster::get_accounts_by_mode('flickr', $post_ID);
        
        if(!empty($flickr_accounts))
        {
            foreach($flickr_accounts as $flickr_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($flickr_account['account_id'], $post_ID, $flickr_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $flickr_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($flickr_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $flickr_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($flickr_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $flickr_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($flickr_account['account_id'], $post_ID, $flickr_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $flickr_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($flickr_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $flickr_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($flickr_account['account_id'], $post_ID, $flickr_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($flickr_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $flickr_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                $post_content[8] = MicroblogPoster_Poster::strip_shortcodes_and_tags($post_content[8]);
                if($flickr_account['message_format'] && $mp['val'] == 0)
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $flickr_account['message_format']);
                }
                elseif($flickr_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $flickr_account['message_format']);
                }
                
                $log_type = 'regular';
                if($mp['val'] == 1)
                {
                    $log_type = 'manual';
                }
                elseif($old == 1)
                {
                    $log_type = 'old';
                }
                
                $extra = json_decode($flickr_account['extra'], true);
                $parameters1 = array();
                if(isset($featured_image_path_full) && $featured_image_path_full)
                {
                    $picture_path = '@'. $featured_image_path_full;
                    $parameters = array(
                        "title" => $post_title,
                        "description" => $update,
                        "tags" => $tags,
                        "photo" => $picture_path,
                        "format" => "json"
                    );
                    $upload_result = MicroblogPoster_Poster::send_signed_request_and_upload_flc(
                        $flickr_account['consumer_key'],
                        $flickr_account['consumer_secret'],
                        $flickr_account['access_token'],
                        $flickr_account['access_token_secret'],
                        "https://up.flickr.com/services/upload/",
                        $parameters  
                    );
                    
                    if (preg_match( "/<photoid>([0-9]+)/", $upload_result, $matches )) 
                    {
                        if(isset($extra['album_id']) && $extra['album_id'])
                        {
                            $parameters1 = array(
                                "photoset_id" => $extra['album_id'],
                                "photo_id" => $matches[1]
                            );
                        }
                        else
                        {
                            $action_result = 1;
                            $result = "Success";
                            
                            $log_data = array();
                            $log_data['log_type'] = $log_type;
                            $log_data['account_id'] = $flickr_account['account_id'];
                            $log_data['account_type'] = "flickr";
                            $log_data['username'] = $flickr_account['username'];
                            $log_data['post_id'] = $post_ID;
                            $log_data['action_result'] = $action_result;
                            $log_data['update_message'] = $update;
                            $log_data['log_message'] = $result;
                            MicroblogPoster_Poster::insert_log($log_data);
                        }  
                    }
                    else
                    {
                        $action_result = 2;
                        
                        $log_data = array();
                        $log_data['log_type'] = $log_type;
                        $log_data['account_id'] = $flickr_account['account_id'];
                        $log_data['account_type'] = "flickr";
                        $log_data['username'] = $flickr_account['username'] . ' - Upload Image';
                        $log_data['post_id'] = $post_ID;
                        $log_data['action_result'] = $action_result;
                        $log_data['update_message'] = $update;
                        $log_data['log_message'] = $upload_result;
                        MicroblogPoster_Poster::insert_log($log_data);
                    }
                }
                else 
                {
                    $log_data = array();
                    $log_data['log_type'] = $log_type;
                    $log_data['account_id'] = $flickr_account['account_id'];
                    $log_data['account_type'] = "flickr";
                    $log_data['username'] = $flickr_account['username'] . ' - Upload Image';
                    $log_data['post_id'] = $post_ID;
                    $log_data['action_result'] = 2;
                    $log_data['update_message'] = '';
                    $log_data['log_message'] = 'Account not authorized OR Featured Image is missing.';
                    MicroblogPoster_Poster::insert_log($log_data);
                }
                
                if(isset($parameters1['photo_id']))
                {
                    $result = MicroblogPoster_Poster::send_signed_request(
                        $flickr_account['consumer_key'],
                        $flickr_account['consumer_secret'],
                        $flickr_account['access_token'],
                        $flickr_account['access_token_secret'],
                        "https://api.flickr.com/services/rest/?method=flickr.photosets.addPhoto",
                        $parameters1
                    );
                    
                    $action_result = 2;
                    if(preg_match( "|<rsp stat=\"ok\">|", $result, $matches))
                    {
                        $action_result = 1;
                        $result = "Success";
                    }
                
                    $log_data = array();
                    $log_data['log_type'] = $log_type;
                    $log_data['account_id'] = $flickr_account['account_id'];
                    $log_data['account_type'] = "flickr";
                    $log_data['username'] = $flickr_account['username'];
                    $log_data['post_id'] = $post_ID;
                    $log_data['action_result'] = $action_result;
                    $log_data['update_message'] = $update;
                    $log_data['log_message'] = $result;
                    MicroblogPoster_Poster::insert_log($log_data);
                }
            }
        }
    }
    
    /**
    * Updates wp based blog
    *
    * @param   string  $title Text to be posted on microblogging site
    * @param   string  $link
    * @param   array $post_content
    * @return  void
    */
    public static function update_wordpress($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink_actual, $post_content_actual_lkn, $featured_image_src_full, $tags)
    {
        $curl = new MicroblogPoster_Curl();
        $wordpress_accounts = MicroblogPoster_Poster::get_accounts_by_mode('wordpress', $post_ID);
        
        if(!empty($wordpress_accounts))
        {
            
            foreach($wordpress_accounts as $wordpress_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($wordpress_account['account_id'], $post_ID, $wordpress_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $wordpress_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($wordpress_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $wordpress_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($wordpress_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $wordpress_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($wordpress_account['account_id'], $post_ID, $wordpress_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $wordpress_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($wordpress_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $wordpress_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($wordpress_account['account_id'], $post_ID, $wordpress_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($wordpress_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $wordpress_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                if($wordpress_account['message_format'] && $mp['val'] == 0)
                {
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $wordpress_account['message_format']);
                }
                
                $extra = json_decode($wordpress_account['extra'], true);
                if(!$extra)
                {
                    continue;
                }

                $is_raw = MicroblogPoster_SupportEnc::is_enc($wordpress_account['extra']);
                if(!$is_raw)
                {
                    $wordpress_account['password'] = MicroblogPoster_SupportEnc::dec($wordpress_account['password']);
                }
                
                $xmlrpc_url = $extra['blog_url'];
                $args = array(
                    'headers' => array(
                    'Authorization' => 'Basic '. base64_encode($wordpress_account['username'].':'.$wordpress_account['password'])
                ));
                $curl = new MicroblogPoster_Curl();
                $curl->set_headers($args);
                $username = $wordpress_account['username'];
                $password = $wordpress_account['password'];
                $post_categories = wp_get_post_categories($post_ID, array('fields' => 'names'));
                
                $content = array(
                    'title' => $post_title,
                    'description' => $update,
                    'mt_allow_comments' => 1,
                    'mt_allow_pings' => 1,
                    'post_type' => 'post',
                    'mt_keywords' => $tags,
                    'categories' => $post_categories,
                );
                if(extension_loaded('xmlrpc'))
                {
                    $params = array(0 ,$username, $password, $content, true);
                    $requestname = 'metaWeblog.newPost';
                    $request = xmlrpc_encode_request($requestname, $params);
                    $result = $curl->send_post_data($xmlrpc_url, $request);
                    $result_dec = xmlrpc_decode($result);
                    $action_result = 2;
                    if(isset($result_dec) && $result_dec != 0 && is_numeric(trim($result_dec)))
                    {
                        $action_result = 1;
                        $result = "Success";
                    }
                    elseif(isset($result_dec) && $result_dec['faultString'])
                    {
                        $result = $result_dec['faultString'];
                    }
                }
                else
                {
                    $action_result = 2;
                    $result = "PHP Extension xmlrpc not loaded.";
                }
                
                
                $log_data = array();
                $log_data['account_id'] = $wordpress_account['account_id'];
                $log_data['account_type'] = "wordpress";
                $log_data['username'] = $wordpress_account['username'];
                $log_data['post_id'] = $post_ID;
                $log_data['action_result'] = $action_result;
                $log_data['update_message'] = $update;
                $log_data['log_message'] = $result;
                if($mp['val'] == 1)
                {
                    $log_data['log_type'] = 'manual';
                }
                elseif($old == 1)
                {
                    $log_data['log_type'] = 'old';
                }
                MicroblogPoster_Poster::insert_log($log_data);
                
            }
        }
    }
    
    public static function update_googleplus($cdriven, $old, $mp, $dash, $update, $post_content, $post_ID, $post_title, $permalink, $post_content_actual_lkn, $tags)
    {
	if($mp['val'] == 1 && $mp['type'] == 'text')
        {
            return;
        }
        $curl = new MicroblogPoster_Curl();
        $googleplus_accounts = MicroblogPoster_Poster::get_accounts_by_mode('googleplus', $post_ID);
        
        if(!empty($googleplus_accounts))
        {
            foreach($googleplus_accounts as $googleplus_account)
            {
                if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 0)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven($googleplus_account['account_id'], $post_ID, $googleplus_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $googleplus_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account($googleplus_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $googleplus_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_mp') && 
                   $dash == 1 && $mp['val'] == 1 && $old == 0)
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_mp($googleplus_account['account_id']);
                    if($active === false)
                    {
                        continue;
                    }
                    else
                    {
                        if(isset($active['message_format']) && $active['message_format'])
                        {
                            $googleplus_account['message_format'] = $active['message_format'];
                        }
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster','filter_single_account_old') && 
                   $dash == 1 && $mp['val'] == 0 && $old == 1)
                {
                    if($cdriven && 
                        MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_old'))
                    {
                        $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_old($googleplus_account['account_id'], $post_ID, $googleplus_account['extra']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $googleplus_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    else
                    {
                        $active = MicroblogPoster_Poster::filter_single_account_old($googleplus_account['account_id']);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $googleplus_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                    
                }
                elseif($cdriven && MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven_wodash'))
                {
                    $active = MicroblogPoster_Poster_Enterprise::filter_single_account_cdriven_wodash($googleplus_account['account_id'], $post_ID, $googleplus_account['extra']);
                    if($active === false)
                    {
                        continue;
                    }
                }
                elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','filter_single_account_scheduled') && 
                       $dash == 0 && $mp['val'] == 0 && $old == 0)
                {
                    if(MicroblogPoster_Poster_Pro::isScheduled($post_ID))
                    {
                        $active = MicroblogPoster_Poster_Pro::filter_single_account_scheduled($googleplus_account['account_id'],$post_ID);
                        if($active === false)
                        {
                            continue;
                        }
                        else
                        {
                            if(isset($active['message_format']) && $active['message_format'])
                            {
                                $googleplus_account['message_format'] = $active['message_format'];
                            }
                        }
                    }
                }
                
                $post_content[8] = MicroblogPoster_Poster::strip_shortcodes_and_tags($post_content[8]);
                if($googleplus_account['message_format'] && $mp['val'] == 0)
                {
                    $googleplus_account['message_format'] = str_ireplace('{site_url}', '', $googleplus_account['message_format']);
                    $googleplus_account['message_format'] = str_ireplace('{url}', '', $googleplus_account['message_format']);
                    $googleplus_account['message_format'] = str_ireplace('{short_url}', '', $googleplus_account['message_format']);
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes(), $post_content, $googleplus_account['message_format']);
                }
                elseif($googleplus_account['message_format'] && $mp['val'] == 1 && $mp['type'] == 'link')
                {
                    $googleplus_account['message_format'] = str_ireplace('{url}', '', $googleplus_account['message_format']);
                    $googleplus_account['message_format'] = str_ireplace('{short_url}', '', $googleplus_account['message_format']);
                    $update = str_ireplace(MicroblogPoster_Poster::get_shortcodes_mp(), $post_content, $googleplus_account['message_format']);
                }
                
                $extra = json_decode($googleplus_account['extra'], true);
                $update = MicroblogPoster_Poster::clean_up_content_update($update);
                if(!$extra)
                {
                    continue;
                }
                
                $include_tags = (isset($extra['include_tags']) && $extra['include_tags'] == 1)?true:false;
                if($include_tags && $tags)
                {
                    $tags = MicroblogPoster_Poster::create_tags_string($tags);
                    $update = $update . $tags;
                }
                
                $update = $update .' '. $permalink;
                if(isset($extra['googleplus_id']))
                { 
                    $access_token = MicroblogPoster_Poster::get_connected_buffer_api_key($extra['connected_buffer']);
                    $url = 'https://api.bufferapp.com/1/updates/create.json?access_token='.$access_token;
                    $headers = array(
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    );
                    $curl->set_headers($headers);
                    $post_data = array(
                        'profile_ids' => $extra['googleplus_id'],
                        'now' => true,
                        'text' => $update
                    );
                    $result = $curl->send_post_data($url, $post_data);
                    $result_dec = json_decode($result, true);
                }
                else
                {
                    $result = 'Google+ ID is required';
                }
                
                $action_result = 2;
                if($result_dec && isset($result_dec['message']))
                {
                    $result = $result_dec['message'];
                }
                if($result_dec && isset($result_dec['success']) && $result_dec['success'] == 1)
                {
                    $action_result = 1;
                    $result = "Success";
                }

                $log_data = array();
                $log_data['account_id'] = $googleplus_account['account_id'];
                $log_data['account_type'] = "googleplus";
                $log_data['username'] = $googleplus_account['username'];
                $log_data['post_id'] = $post_ID;
                $log_data['action_result'] = $action_result;
                $log_data['update_message'] = $update;
                $log_data['log_message'] = $result;
                if($mp['val'] == 1)
                {
                    $log_data['log_type'] = 'manual';
                }
                elseif($old == 1)
                {
                    $log_data['log_type'] = 'old';
                }
                MicroblogPoster_Poster::insert_log($log_data);
            }
        }
    }
    
    /**
    * Sends OAuth signed request
    *
    * @param   string  $c_key Application consumer key
    * @param   string  $c_secret Application consumer secret
    * @param   string  $token Account access token
    * @param   string  $token_secret Account access token secret
    * @param   string  $api_url URL of the API end point
    * @param   string  $params Parameters to be passed
    * @return  void
    */
    public static function send_signed_request($c_key, $c_secret, $token, $token_secret, $api_url, $params)
    {
        $consumer = new MicroblogPosterOAuthConsumer($c_key, $c_secret);
        $access_token = new MicroblogPosterOAuthConsumer($token, $token_secret);
        
        $request = MicroblogPosterOAuthRequest::from_consumer_and_token(
                $consumer,
                $access_token,
                "POST",
                $api_url,
                $params
        );
        $hmac_method = new MicroblogPosterOAuthSignatureMethod_HMAC_SHA1();
        $request->sign_request($hmac_method, $consumer, $access_token);
        
        if(($pos=strpos($request,"?")) !== false)
        {
            $url = substr($request,0,$pos);
            $parameters = substr($request,$pos+1);
        }
        
        $curl = new MicroblogPoster_Curl();
        $result = $curl->send_post_data($url, $parameters);
        return $result;
    
    }
    
    /**
    * Filters single social account
    *
    * @param int $account_id
    * @return mixed
    */
    public static function send_signed_request_and_upload_flc($c_key, $c_secret, $token, $token_secret, $api_url, $params)
    {
        $consumer = new MicroblogPosterOAuthConsumer($c_key, $c_secret);
        $access_token = new MicroblogPosterOAuthConsumer($token, $token_secret);
        
        $request = MicroblogPosterOAuthRequest::from_consumer_and_token(
                $consumer,
                $access_token,
                "POST",
                $api_url,
                $params
        );
        $hmac_method = new MicroblogPosterOAuthSignatureMethod_HMAC_SHA1();
        $request->sign_request($hmac_method, $consumer, $access_token);
        
        $body = new stdClass();
        $body ->photo = $params['photo'];
        $body ->title = $params['title'];
        $body ->description = $params['description'];
        $body ->tags = $params['tags'];
        $body ->format = $params['format'];
       
        if(($pos=strpos($request,"?")) !== false)
        {
            $url = substr($request,0,$pos);
            $parameters = substr($request,$pos+1);
        }
        
        $parametersArray = explode('&',$parameters);
        $authHeader = "OAuth ";
        foreach($parametersArray as $parametersE)
        {
            $parametersTemp = explode('=', $parametersE);
            $authHeader .= $parametersTemp[0] . '="'.$parametersTemp[1].'",';
        }
        
        $headers = array(
            'Content-type'  => 'multipart/form-data',
            'Authorization' => $authHeader
        );
        
        $curl = new MicroblogPoster_Curl();
        $curl->set_headers($headers);
        $result = $curl->send_post_data_json($request->to_url(), $body);

        return $result;
    }
    
    /**
    * Filters single social account
    *
    * @param int $account_id
    * @return mixed
    */
    public static function filter_single_account_old($account_id)
    {
        global $wpdb;
        
        $table_accounts = $wpdb->prefix . 'microblogposter_accounts';
        
        $sql="SELECT * FROM $table_accounts WHERE account_id={$account_id}";
        if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Ultimate','resolve_sql_old_posts'))
        {
            $sql .= MicroblogPoster_Poster_Ultimate::resolve_sql_old_posts();
        }
        $rows = $wpdb->get_results($sql);
        if(empty($rows))
        {
            return false;
        }
        $row = $rows[0];
        $extra = json_decode($row->extra, true);
        
        $active = false;
        if(isset($extra['old_posts_active']) && $extra['old_posts_active'] == 1)
        {
            $message_format = $extra['message_format_old'];
            $active = array();
            $active['message_format'] = $message_format;
        }
        
        return $active;
    }
    
    /**
    * Shorten long url
    *
    * @param   string  $long_url
    * @return  array
    */
    public static function shorten_long_url($long_url)
    {
        $url_shortener_name = "microblogposter_plg_url_shortener";
        $url_shortener_value = get_option($url_shortener_name, "default");
        $shortened_permalink = '';
        $shortened_permalink_twitter = '';
        if($url_shortener_value == "default" || $url_shortener_value == "bitly")
        {
            $bitly_api = new MicroblogPoster_Bitly();
            $bitly_api_user_value = get_option("microblogposter_plg_bitly_api_user", "");
            $bitly_api_key_value = get_option("microblogposter_plg_bitly_api_key", "");
            $bitly_access_token_value = get_option("microblogposter_plg_bitly_access_token", "");
            if( ($bitly_api_user_value and $bitly_api_key_value) or $bitly_access_token_value )
            {
                $bitly_api->setCredentials($bitly_api_user_value, $bitly_api_key_value, $bitly_access_token_value);
                $shortened_permalink = $bitly_api->shorten($long_url);
                if($shortened_permalink)
                {
                    $shortened_permalink_twitter = $shortened_permalink;
                }
            }
        }
        elseif($url_shortener_value == "googl")
        {
            $googl_api = new MicroblogPoster_Googl();
            $shortened_permalink = $googl_api->shorten($long_url);
            if($shortened_permalink)
            {
                $shortened_permalink_twitter = $shortened_permalink;
            }
        }
        elseif($url_shortener_value == "adfly")
        {
            if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','shorten_with_adfly'))
            {
                $shortened_permalink = MicroblogPoster_Poster_Enterprise::shorten_with_adfly($long_url, false);
                if($shortened_permalink)
                {
                    $adfly_api_domain_name = "microblogposter_plg_adfly_api_domain_type";
                    $adfly_api_domain_value = get_option($adfly_api_domain_name, "");
                    if($adfly_api_domain_value == 'adfly')
                    {
                        $shortened_permalink_twitter = MicroblogPoster_Poster_Enterprise::shorten_with_adfly($long_url, true);
                    }
                    else
                    {
                        $shortened_permalink_twitter = $shortened_permalink;
                    }
                }
            }
        }
        elseif($url_shortener_value == "adfocus")
        {
            if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','shorten_with_adfocus'))
            {
                $shortened_permalink = MicroblogPoster_Poster_Enterprise::shorten_with_adfocus($long_url);
                if($shortened_permalink)
                {
                    $shortened_permalink_twitter = $shortened_permalink;
                }
            }
        }
        elseif($url_shortener_value == "ppw")
        {
            if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','shorten_with_ppw'))
            {
                $shortened_permalink = MicroblogPoster_Poster_Enterprise::shorten_with_ppw($long_url);
                if($shortened_permalink)
                {
                    $shortened_permalink_twitter = $shortened_permalink;
                }
            }
        }
        return array('shortened_permalink' => $shortened_permalink,
                    'shortened_permalink_twitter' => $shortened_permalink_twitter);
    }
    
    /**
    * Shorten long url
    *
    * @param   string  $long_url
    * @return  array
    */
    public static function is_post_in_excluded_category($post_categories)
    {
        if(is_array($post_categories) && !empty($post_categories))
        {
            $excluded_categories_name = "microblogposter_excluded_categories";
            $excluded_categories_value = get_option($excluded_categories_name, "");
            $excluded_categories = json_decode($excluded_categories_value, true);
            if(is_array($excluded_categories) && !empty($excluded_categories))
            {
                foreach($excluded_categories as $cat_id)
                {
                    if(in_array($cat_id, $post_categories))
                    {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
    * Shorten long url
    * 
    * @return  int
    */
    public static function get_shortcode_title_max_length()
    {
        $shortcode_title_max_length_name = "microblogposter_plg_shortcode_title_max_length";
        $shortcode_title_max_length_value = get_option($shortcode_title_max_length_name, "");
        $shortcode_title_max_length = 110;
        if(intval($shortcode_title_max_length_value) &&
           intval($shortcode_title_max_length_value) >= 30 && intval($shortcode_title_max_length_value) <= 120)
        {
            $shortcode_title_max_length = $shortcode_title_max_length_value;
        }
        return $shortcode_title_max_length;
    }
    
    /**
    * Shorten long url
    * 
    * @return  int
    */
    public static function get_shortcode_firstwords_max_length()
    {
        $shortcode_firstwords_max_length_name = "microblogposter_plg_shortcode_firstwords_max_length";
        $shortcode_firstwords_max_length_value = get_option($shortcode_firstwords_max_length_name, "");
        $shortcode_firstwords_max_length = 90;
        if(intval($shortcode_firstwords_max_length_value) &&
           intval($shortcode_firstwords_max_length_value) >= 30 && intval($shortcode_firstwords_max_length_value) <= 120)
        {
            $shortcode_firstwords_max_length = $shortcode_firstwords_max_length_value;
        }
        return $shortcode_firstwords_max_length;
    }
    
    /**
    * Shorten long url
    * 
    * @return  int
    */
    public static function get_shortcode_excerpt_max_length()
    {
        $shortcode_excerpt_max_length_name = "microblogposter_plg_shortcode_excerpt_max_length";
        $shortcode_excerpt_max_length_value = get_option($shortcode_excerpt_max_length_name, "");
        $shortcode_excerpt_max_length = 400;
        if(intval($shortcode_excerpt_max_length_value) &&
           intval($shortcode_excerpt_max_length_value) >= 100 && intval($shortcode_excerpt_max_length_value) <= 600)
        {
            $shortcode_excerpt_max_length = $shortcode_excerpt_max_length_value;
        }
        return $shortcode_excerpt_max_length;
    }
    
    /**
    * Shorten long url
    * 
    * @return  int
    */
    public static function get_featured_image_src_full($post_thumbnail_id)
    {
        $featured_image_src_full = '';
        if($post_thumbnail_id)
        {
            $image_attributes = wp_get_attachment_image_src($post_thumbnail_id, 'full');
            $featured_image_src_full = $image_attributes[0];
        }
        return $featured_image_src_full;
    }
    
    /**
    * Shorten long url
    * 
    * @return  int
    */
    public static function get_featured_image_src_thumbnail($post_thumbnail_id)
    {
        $featured_image_src_thumbnail = '';
        if($post_thumbnail_id)
        {
            $image_attributes = wp_get_attachment_image_src($post_thumbnail_id, 'thumbnail');
            $featured_image_src_thumbnail = $image_attributes[0];
        }
        return $featured_image_src_thumbnail;
    }
    
    /**
    * Shorten long url
    * 
    * @return  int
    */
    public static function get_featured_image_src_medium($post_thumbnail_id)
    {
        $featured_image_src_medium = '';
        if($post_thumbnail_id)
        {
            $image_attributes = wp_get_attachment_image_src($post_thumbnail_id, 'medium');
            $featured_image_src_medium = $image_attributes[0];
        }
        return $featured_image_src_medium;
    }
    
    /**
    * Shorten long url
    * 
    * @return  int
    */
    public static function get_featured_image_path_full($post_thumbnail_id)
    {
        $image= wp_get_attachment_image_src($post_thumbnail_id, 'full');
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];
        $base_url = $upload_dir['baseurl'];
        $featured_image_path_full = str_replace($base_url, $base_dir, $image[0]);
        if(file_exists( $featured_image_path_full))
        { 
            return $featured_image_path_full;
        }
        return false;
    }
    
    public static function is_apply_filters_activated()
    {
        $apply_filters_before_publishing_name = "microblogposter_plg_apply_filters_before_publishing";
        $apply_filters_before_publishing_value = get_option($apply_filters_before_publishing_name, "");
        if($apply_filters_before_publishing_value)
        {
            return true;
        }
        return false;
    }
    
    /**
    * Shorten long url
    * 
    * @return  int
    */
    public static function get_author($user_ID = 0)
    {
        $author = '';
        if (!function_exists('get_user_by'))
        {
            require_once( ABSPATH . WPINC . '/pluggable.php' );
        }
        if($user_ID == 0)
        {
            $user_ID = get_current_user_id();
        }
        
        if(intval($user_ID))
        {
            $loggedin_user = get_user_by('id', $user_ID);
            $author_tmp = $loggedin_user->display_name;
            if($author_tmp)
            {
                $author = $author_tmp;
            }
        }
        
        return $author;
    }
    
    public static function get_current_user_id()
    {
        
        if (!function_exists('get_user_by'))
        {
            require_once( ABSPATH . WPINC . '/pluggable.php' );
        }
        $user_ID = get_current_user_id();
        
        if(intval($user_ID))
        {
            return $user_ID;
        }
        else
        {
            return 0;
        }
    }
    
    public static function can_user_auto_publish($user_ID)
    {
        if (!function_exists('get_user_by'))
        {
            require_once( ABSPATH . WPINC . '/pluggable.php' );
        }
        $multi_author_mode_name = "microblogposter_plg_multi_author_mode";
        $multi_author_active = get_option($multi_author_mode_name, 0);
        
        if ( (!$multi_author_active && user_can($user_ID, 'microblogposter_who_can_auto_publish')) || 
                ($multi_author_active && user_can($user_ID, 'microblogposter_who_can_auto_publish_ma')) || 
                (is_multisite() && is_super_admin($user_ID)) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public static function can_current_user_auto_publish()
    {
        if (!function_exists('get_user_by'))
        {
            require_once( ABSPATH . WPINC . '/pluggable.php' );
        }
        $user_ID = get_current_user_id();
        $multi_author_mode_name = "microblogposter_plg_multi_author_mode";
        $multi_author_active = get_option($multi_author_mode_name, 0);
        
        if ( (!$multi_author_active && current_user_can('microblogposter_who_can_auto_publish')) ||
                ($multi_author_active && current_user_can('microblogposter_who_can_auto_publish_ma')) || 
                (is_multisite() && is_super_admin($user_ID)) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public static function get_and_save_who_can_auto_publish_default()
    {
        $who_can_auto_publish_name = "microblogposter_who_can_auto_publish";
        $who_can_auto_publish_default_value = array();
        $roles = get_editable_roles();
        if(is_array($roles) && !empty($roles))
        {
            foreach ($roles as $role_id => $role_details)
            {
                $role = get_role($role_id);
                if($role)
                {
                    $role->add_cap('microblogposter_who_can_auto_publish');
                    $who_can_auto_publish_default_value[] = $role_id;
                }
            }
        }
        $who_can_auto_publish_default_value_enc = json_encode($who_can_auto_publish_default_value);
        update_option($who_can_auto_publish_name, $who_can_auto_publish_default_value_enc);
        return $who_can_auto_publish_default_value;
    }
    
    /**
    * Shorten long url
    * 
    * @return  int
    */
    public static function get_post_tags($post_ID)
    {
        $tags = "";
	$posttags = get_the_tags($post_ID);
	if(is_array($posttags) && !empty($posttags)) 
        {
	    foreach($posttags as $tag) 
            {
                $tags .= $tag->slug . ','; 
	    }
	}
	$tags = rtrim($tags,',');
        return $tags;
    }
    
    /**
    * Get accounts from db
    *
    * @param   string  $type Type of account (=site)
    * @return  array
    */
    private static function get_accounts_by_mode($type, $post_ID)
    {
        if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Ultimate','resolve_accounts'))
        {
            $accounts = MicroblogPoster_Poster_Ultimate::resolve_accounts($type, $post_ID);
        }
        else
        {
            $accounts = MicroblogPoster_Poster::get_accounts($type);
        }
        return $accounts;
    }
    
    /**
    * Get accounts from db
    *
    * @param   string  $type Type of account (=site)
    * @return  array
    */
    private static function get_accounts($type)
    {
        global  $wpdb;

        $table_accounts = $wpdb->prefix . 'microblogposter_accounts';
        $table_user_accounts = $wpdb->prefix . 'microblogposter_user_accounts';
        
        $sql="SELECT * FROM $table_accounts WHERE type='{$type}'";
        $sql .= " AND account_id NOT IN (SELECT DISTINCT account_id FROM $table_user_accounts)";
        $rows = $wpdb->get_results($sql, ARRAY_A);
        
        return $rows;
    }
    
    /**
    * Get accounts from db
    *
    * @param   string  $type Type of account (=site)
    * @return  array
    */
    public static function get_accounts_all()
    {
        global  $wpdb;

        $table_accounts = $wpdb->prefix . 'microblogposter_accounts';
        
        $sql="SELECT * FROM $table_accounts";
        $rows = $wpdb->get_results($sql, ARRAY_A);
        
        return $rows;
    }
    
    /**
    * Get accounts from db
    *
    * @param   string  $type Type of account (=site)
    * @return  array
    */
    public static function get_accounts_object_all($type)
    {
        global  $wpdb;

        $table_accounts = $wpdb->prefix . 'microblogposter_accounts';
        $table_user_accounts = $wpdb->prefix . 'microblogposter_user_accounts';
        
        $sql="SELECT * FROM $table_accounts WHERE type='{$type}'";
        if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Ultimate','resolve_sql_old_posts'))
        {
            $sql .= MicroblogPoster_Poster_Ultimate::resolve_sql_old_posts();
        }
        else
        {
            $sql .= " AND account_id NOT IN (SELECT DISTINCT account_id FROM $table_user_accounts)";
        }
        $rows = $wpdb->get_results($sql);
        
        return $rows;
    }
    
    /**
    * Get accounts from db
    *
    * @param   string  $type Type of account (=site)
    * @return  array
    */
    public static function get_accounts_object($type)
    {
        global  $wpdb;

        $table_accounts = $wpdb->prefix . 'microblogposter_accounts';
        $table_user_accounts = $wpdb->prefix . 'microblogposter_user_accounts';
        
        $sql="SELECT * FROM $table_accounts WHERE type='{$type}'";
        if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Ultimate','resolve_sql'))
        {
            $sql .= MicroblogPoster_Poster_Ultimate::resolve_sql();
        }
        else
        {
            $sql .= " AND account_id NOT IN (SELECT DISTINCT account_id FROM $table_user_accounts)";
        }
        $rows = $wpdb->get_results($sql);
        
        return $rows;
    }
    
    /**
    * Get accounts from db
    *
    * @param   string  $type Type of account (=site)
    * @return  array
    */
    public static function get_account_object($account_id)
    {
        global  $wpdb;

        $table_accounts = $wpdb->prefix . 'microblogposter_accounts';
        
        $sql="SELECT * FROM $table_accounts WHERE account_id = %d LIMIT 1";
        $rows = $wpdb->get_results($wpdb->prepare($sql, $account_id));
        if(count($rows) == 1)
        {
            return $rows[0];
        }
        else
        {
            return null;
        }
    }
    
    /**
    * Get account from db
    *
    * @param   string  $type Type of account (=site)
    * @return  array
    */
    public static function get_googleplus_accounts($buffer_id)
    {
        $account = MicroblogPoster_Poster::get_account_object($buffer_id);
        if($account)
        {
            $extra = json_decode($account->extra, true);
            return $extra['google']['ids'];
        }
        else
        {
            return null;
        }
    }
    
    /**
    * Get account from db
    *
    * @param   string  $type Type of account (=site)
    * @return  array
    */
    public static function get_googleplus_account($connected_buffer_id, $googleplus_id)
    {
        $account = MicroblogPoster_Poster::get_account_object($connected_buffer_id);
        if($account)
        {
            $extra = json_decode($account->extra, true);
            return $extra['google']['ids'][$googleplus_id];
        }
        else
        {
            return null;
        }
    }
    
    public static function get_connected_buffer_api_key($connected_buffer)
    {
        $account = MicroblogPoster_Poster::get_account_object($connected_buffer);
        if($account)
        {
            $extra = json_decode($account->extra, true);
            return $extra['api_key'];
        }
        else
        {
            return null;
        }
    }
    
    public static function create_tags_string($tags)
    {
        $tags_array = explode(",", $tags);
        foreach ($tags_array as $tag)
        {
            $string = str_replace(' ', '', $tag);
            $string = str_replace('#', '', $string);
            $string = str_replace('-', '', $string);
            $string = ' #'. $string; 
            $tagos = $tagos . $string;
        }

        return $tagos;
    }
    
    public static function clean_up_content_update($update)
    {
        $update = strip_tags($update);
        $update = str_replace("&nbsp;", '', $update);
        $update = str_replace("&lt;", '', $update);
        $update = str_replace("&gt;", '', $update);
        $update = str_replace("&amp;", '&', $update);

        return $update;
    }
    
    /**
    * Insert new log into db
    *
    * @param   array  $log_data Log message
    * @return  bool
    */
    public static function insert_log($log_data)
    {
        global  $wpdb;

        $table_logs = $wpdb->prefix . 'microblogposter_logs';
        
        $wpdb->escape_by_ref($log_data['log_message']);
        $wpdb->escape_by_ref($log_data['update_message']);
        $wpdb->escape_by_ref($log_data['username']);
        if(!isset($log_data['log_type']))
        {
            $log_data['log_type'] = 'regular';
        }
        
        $sql="INSERT INTO $table_logs (account_id, account_type, username, post_id, action_result, update_message, log_message, log_type) 
            VALUES ('{$log_data['account_id']}','{$log_data['account_type']}','{$log_data['username']}','{$log_data['post_id']}','{$log_data['action_result']}','{$log_data['update_message']}','{$log_data['log_message']}','{$log_data['log_type']}')";
        $wpdb->query($sql);
        
        return true;
    }
    
    /**
    * 
    *
    * @param string $class_name
    * @param string $method_name
    * @return  bool
    */
    public static function is_method_callable($class_name, $method_name)
    {
        if( class_exists($class_name, false) && method_exists($class_name, $method_name) )
        {
            return true;
        }
        
        return false;
    }
    
    /**
    * Keeps logs table under 2000 rows
    *
    * @return  void
    */
    private static function maintain_logs()
    {
        global  $wpdb;

        $table_logs = $wpdb->prefix . 'microblogposter_logs';
        
        $sql="SELECT log_id FROM $table_logs ORDER BY log_id DESC LIMIT 2000";
        $rows = $wpdb->get_results($sql);
        if(is_array($rows) && count($rows)==2000)
        {
            $log_ids = "(";
            foreach($rows as $row)
            {
                $log_ids .= $row->log_id.",";
            }
            $log_ids = rtrim($log_ids, ",");
            $log_ids .= ")";
            
            $sql="DELETE FROM {$table_logs} WHERE log_id NOT IN {$log_ids}";
            $wpdb->query($sql);
        }
        
        return true;
    }
    
    /**
    * 
    * get_shortcodes
    * 
    * @return  array
    */
    private static function get_shortcodes()
    {
        return array('{site_url}', 
                    '{title}', 
                    '{url}', 
                    '{short_url}', 
                    '{manual_excerpt}',
                    '{excerpt}', 
                    '{author}', 
                    '{content_first_words}',
                    '{content}'
        );
    }
    
    /**
    * 
    * get_shortcodes
    * 
    * @return  array
    */
    private static function get_shortcodes_mp()
    {
        return array( 
                    '{title}', 
                    '{url}', 
                    '{short_url}'
        );
    }
    
    /**
    * 
    * @param string $content
    * @param int $length
    * @param string $ending_char
    * @return string
    */
    private static function clean_up_and_shorten_content($content, $length, $ending_char)
    {
        $content = strip_shortcodes($content);
        $content = strip_tags($content);
        $content = preg_replace("|(\r\n)+|", " ", $content);
        $content = preg_replace("|(\t)+|", "", $content);
        $content = preg_replace("|\&nbsp\;|", "", $content);
        $content = substr($content, 0, $length);
        
        if(strlen($content) == $length)
        {
            $content = substr($content, 0, strrpos($content, $ending_char));
        }
        return $content;
    }
    
    /**
    * 
    * @param string $content
    * @param int $length
    * @param string $ending_char
    * @return string
    */
    private static function shorten_content($content, $length, $ending_char)
    {
        $content = strip_shortcodes($content);
        $content = strip_tags($content);
        $content = substr($content, 0, $length);
        
        if(strlen($content) == $length)
        {
            $content = substr($content, 0, strrpos($content, $ending_char)+1);
        }
        return $content;
    }
    
    /**
    * 
    * @param string $title
    * @param int $length
    * @param string $ending_char
    * @return string
    */
    private static function shorten_title($title, $length, $ending_char)
    {
        $title = substr($title, 0, $length);
        
        if(strlen($title) == $length)
        {
            $title = substr($title, 0, strrpos($title, $ending_char));
            $title .= "...";
        }
        return $title;
    }
    
    /**
    * 
    * @param string $content
    * @return string
    */
    private static function clean_up_content($content)
    {
        $content = strip_shortcodes($content);
        $content = strip_tags($content);
        $content = preg_replace("|(\r\n)+|", " ", $content);
        $content = preg_replace("|(\t)+|", "", $content);
        $content = preg_replace("|\&nbsp\;|", "", $content);
        
        return $content;
    }
    
    /**
    * 
    * @param string $content
    * @return string
    */
    private static function strip_shortcodes_and_tags($content)
    {
        $content = strip_shortcodes($content);
        $content = strip_tags($content);
        
        return $content;
    }
    
    /**
    * Shorten long url
    *
    * @param   string  $long_url
    * @return  array
    */
    public static function load_languages()
    {
        $plugin_dir = basename(dirname(__FILE__));
        load_plugin_textdomain('microblog-poster', false, $plugin_dir . '/languages');
    }
    
}

class MicroblogPoster_SupportEnc
{
    /**
    * Encodes the given string
    * 
    * @param string $str
    * @return  string
    */
    public static function enc($str)
    {
        $str = 'microblogposter_'.$str;
        $str = base64_encode($str);
        return $str;
    }
    
    /**
    * Decodes the given string
    * 
    * @param string $str
    * @return  string
    */
    public static function dec($str)
    {
        $str = base64_decode($str);
        $str = str_replace('microblogposter_', '', $str);
        return $str;
    }
    
    /**
    * Checks if enc
    * 
    * @param string $e
    * @return  bool
    */
    public static function is_enc($e)
    {
        $is_raw = true;
        $extra = json_decode($e, true);
        if(is_array($extra))
        {
            $is_raw = (isset($extra['penc']) && $extra['penc'] == 1)?false:true;
        }
        return $is_raw;
    }
}

register_activation_hook(__FILE__, array('MicroblogPoster_Poster', 'activate'));

add_action('plugins_loaded', array('MicroblogPoster_Poster', 'load_languages'));

add_action('publish_post', array('MicroblogPoster_Poster', 'update'));
add_action('save_post', array('MicroblogPoster_Poster', 'schedule'));

$page_mode_name = "microblogposter_page_mode";
$page_mode_value = get_option($page_mode_name, "");
if($page_mode_value)
{
    add_action('publish_page', array('MicroblogPoster_Poster', 'update'));
}

$enabled_custom_types_name = "microblogposter_enabled_custom_types";
$enabled_custom_types_value = get_option($enabled_custom_types_name, "");
$enabled_custom_types = json_decode($enabled_custom_types_value, true);
if(is_array($enabled_custom_types) && !empty($enabled_custom_types))
{
    foreach($enabled_custom_types as $custom_type)
    {
        add_action('publish_' . $custom_type, array('MicroblogPoster_Poster', 'update'));
    }
}

add_action('microblogposter_plg_old_posts_publish', array('MicroblogPoster_Poster', 'handle_old_posts_publish'));

add_filter('cron_schedules', array('MicroblogPoster_Poster', 'add_new_cron_interval'));

//Displays a checkbox that allows users to disable Microblog Poster on a per post basis.
function microblogposter_meta()
{   
    $default_behavior_name = "microblogposter_default_behavior";
    $default_behavior_value = get_option($default_behavior_name, "");
    $default_behavior_update_name = "microblogposter_default_behavior_update";
    $default_behavior_update_value = get_option($default_behavior_update_name, "");
    $default_behavior_cat_driven_name = "microblogposter_default_behavior_cat_driven";
    $default_behavior_cat_driven_value = get_option($default_behavior_cat_driven_name, "");
    
    global $post;
    if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','getScheduledMeta'))
    {
        $scheduledMeta = MicroblogPoster_Poster_Pro::getScheduledMeta(0,$post->ID);
        if(is_array($scheduledMeta))
        {
            $extra = json_decode($scheduledMeta['extra'], true);
            $default_behavior_value = $extra['disabled'];
            $default_behavior_update_value = $extra['disabled'];
            $default_behavior_cat_driven_value = $extra['cdriven'];
        }
    }
    
    $screen = get_current_screen();
    if($screen->action != 'add')
    {
        $default_behavior_value = $default_behavior_update_value;
    }
    ?>
    <input type="checkbox" id="microblogposteroff" name="microblogposteroff" <?php if($default_behavior_value) echo 'checked="checked"';?> /> 
    <label for="microblogposteroff"><?php _e('Disable Microblog Poster this time?', 'microblog-poster');?></label>
    <?php if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven')):?>
    <br />
    <input type="checkbox" id="mbp_microblogposter_category_to_account" name="mbp_microblogposter_category_to_account" <?php if($default_behavior_cat_driven_value) echo 'checked="checked"';?> /> 
    <label for="mbp_microblogposter_category_to_account"><?php _e('Enable Category-Driven Mode?', 'microblog-poster');?></label>
    <?php elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','show_control_dashboard')):?>
    <br />
    <input type="checkbox" id="mbp_microblogposter_category_to_account" name="mbp_microblogposter_category_to_account" <?php if($default_behavior_cat_driven_value) echo 'checked="checked"';?> /> 
    <label for="mbp_microblogposter_category_to_account"><?php _e('Enable Category-Driven Mode?', 'microblog-poster');?></label>
    &nbsp;&nbsp;
    <?php _e('Available with the Enterprise Add-on.', 'microblog-poster');?> <a href="http://efficientscripts.com/support/microblogposter/auto-publish-in-category-driven-mode" target="_blank"><?php _e('More Infos', 'microblog-poster');?></a>
    <?php endif;?>
    
    <?php
    if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','show_control_dashboard'))
    {
        MicroblogPoster_Poster_Pro::show_control_dashboard();
    }
    else
    {
        ?>
        <style>
            #mbp_upgrade_notice_div_microblogposter
            {
                margin-top: 10px;
            }
        </style>
        
        <script>
            function mbp_show_upgrade_notice_microblogposter()
            {
                if(jQuery('#mbp_upgrade_notice_div_microblogposter').is(':visible'))
                {
                    jQuery('#mbp_upgrade_notice_div_microblogposter').hide();
                    jQuery('#mbp_upgrade_notice_lnk_microblogposter').html('<?php _e('Show complete Control Dashboard', 'microblog-poster');?>');
                }
                else
                {
                    jQuery('#mbp_upgrade_notice_div_microblogposter').show();
                    jQuery('#mbp_upgrade_notice_lnk_microblogposter').html('<?php _e('Hide complete Control Dashboard', 'microblog-poster');?>');
                }    
                
            }
        </script>
        &nbsp;<a href="#" id="mbp_upgrade_notice_lnk_microblogposter" onclick="mbp_show_upgrade_notice_microblogposter();return false;" ><?php _e('Show complete Control Dashboard', 'microblog-poster');?></a>
        <div id="mbp_upgrade_notice_div_microblogposter" style="display:none;"><?php _e('Available with the Pro / Enterprise Add-on.', 'microblog-poster');?> <a href="http://efficientscripts.com/web/products/addons" target="_blank"><?php _e('Upgrade Now', 'microblog-poster');?></a></div>
        <?php
    }
    
}

//Displays a checkbox that allows users to disable Microblog Poster on a per page basis.
function microblogposter_pmeta()
{
    $default_pbehavior_name = "microblogposter_default_pbehavior";
    $default_pbehavior_value = get_option($default_pbehavior_name, "");
    $default_pbehavior_update_name = "microblogposter_default_pbehavior_update";
    $default_pbehavior_update_value = get_option($default_pbehavior_update_name, "");
    $default_behavior_cat_driven_name = "microblogposter_default_behavior_cat_driven";
    $default_behavior_cat_driven_value = get_option($default_behavior_cat_driven_name, "");
    
    global $post;
    if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','getScheduledMeta'))
    {
        $scheduledMeta = MicroblogPoster_Poster_Pro::getScheduledMeta(0,$post->ID);
        if(is_array($scheduledMeta))
        {
            $extra = json_decode($scheduledMeta['extra'], true);
            $default_pbehavior_value = $extra['disabled'];
            $default_pbehavior_update_value = $extra['disabled'];
            $default_behavior_cat_driven_value = $extra['cdriven'];
        }
    }
    
    $screen = get_current_screen();
    if($screen->action != 'add')
    {
        $default_pbehavior_value = $default_pbehavior_update_value;
    }
    ?>
    <input type="checkbox" id="microblogposteroff" name="microblogposteroff" <?php if($default_pbehavior_value) echo 'checked="checked"';?> /> 
    <label for="microblogposteroff"><?php _e('Disable Microblog Poster this time?', 'microblog-poster');?></label>
    <?php if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven')):?>
    <br />
    <input type="checkbox" id="mbp_microblogposter_category_to_account" name="mbp_microblogposter_category_to_account" <?php if($default_behavior_cat_driven_value) echo 'checked="checked"';?> /> 
    <label for="mbp_microblogposter_category_to_account"><?php _e('Enable Category-Driven Mode?', 'microblog-poster');?></label>
    <?php elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','show_control_dashboard')):?>
    <br />
    <input type="checkbox" id="mbp_microblogposter_category_to_account" name="mbp_microblogposter_category_to_account" <?php if($default_behavior_cat_driven_value) echo 'checked="checked"';?> /> 
    <label for="mbp_microblogposter_category_to_account"><?php _e('Enable Category-Driven Mode?', 'microblog-poster');?></label>
    &nbsp;&nbsp;
    <?php _e('Available with the Enterprise Add-on.', 'microblog-poster');?> <a href="http://efficientscripts.com/support/microblogposter/auto-publish-in-category-driven-mode" target="_blank"><?php _e('More Infos', 'microblog-poster');?></a>
    <?php endif;?>
    
    <?php
    if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','show_control_dashboard'))
    {
        MicroblogPoster_Poster_Pro::show_control_dashboard();
    }
    else
    {
        ?>
        <style>
            #mbp_upgrade_notice_div_microblogposter
            {
                margin-top: 10px;
            }
        </style>
        
        <script>
            function mbp_show_upgrade_notice_microblogposter()
            {
                if(jQuery('#mbp_upgrade_notice_div_microblogposter').is(':visible'))
                {
                    jQuery('#mbp_upgrade_notice_div_microblogposter').hide();
                    jQuery('#mbp_upgrade_notice_lnk_microblogposter').html('<?php _e('Show complete Control Dashboard', 'microblog-poster');?>');
                }
                else
                {
                    jQuery('#mbp_upgrade_notice_div_microblogposter').show();
                    jQuery('#mbp_upgrade_notice_lnk_microblogposter').html('<?php _e('Hide complete Control Dashboard', 'microblog-poster');?>');
                }    
                
            }
        </script>
        &nbsp;<a href="#" id="mbp_upgrade_notice_lnk_microblogposter" onclick="mbp_show_upgrade_notice_microblogposter();return false;" ><?php _e('Show complete Control Dashboard', 'microblog-poster');?></a>
        <div id="mbp_upgrade_notice_div_microblogposter" style="display:none;"><?php _e('Available with the Pro / Enterprise Add-on.', 'microblog-poster');?> <a href="http://efficientscripts.com/web/products/addons" target="_blank"><?php _e('Upgrade Now', 'microblog-poster');?></a></div>
        <?php
    }
}

//Displays a checkbox that allows users to disable Microblog Poster on a per custom type basis.
function microblogposter_custom_meta($post, $metabox)
{   
    $default_pbehavior_value = false;
    
    $enabled_custom_updates_name = "microblogposter_enabled_custom_updates";
    $enabled_custom_updates_value = get_option($enabled_custom_updates_name, "");
    $enabled_custom_updates = json_decode($enabled_custom_updates_value, true);
    $default_behavior_cat_driven_name = "microblogposter_default_behavior_cat_driven";
    $default_behavior_cat_driven_value = get_option($default_behavior_cat_driven_name, "");
    
    $screen = get_current_screen();
    if($screen->action != 'add')
    {
        if(is_array($enabled_custom_updates) && !empty($enabled_custom_updates))
        {
            if(in_array($metabox['args']['type'], $enabled_custom_updates))
            {
                $default_pbehavior_value = true;
            }
        }
    }
    
    global $post;
    if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','getScheduledMeta'))
    {
        $scheduledMeta = MicroblogPoster_Poster_Pro::getScheduledMeta(0,$post->ID);
        if(is_array($scheduledMeta))
        {
            $extra = json_decode($scheduledMeta['extra'], true);
            $default_pbehavior_value = $extra['disabled'];
            $default_behavior_cat_driven_value = $extra['cdriven'];
        }
    }
    
    ?>
    <input type="checkbox" id="microblogposteroff" name="microblogposteroff" <?php if($default_pbehavior_value) echo 'checked="checked"';?> /> 
    <label for="microblogposteroff"><?php _e('Disable Microblog Poster this time?', 'microblog-poster');?></label>
    <?php if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Enterprise','filter_single_account_cdriven')):?>
    <br />
    <input type="checkbox" id="mbp_microblogposter_category_to_account" name="mbp_microblogposter_category_to_account" <?php if($default_behavior_cat_driven_value) echo 'checked="checked"';?> /> 
    <label for="mbp_microblogposter_category_to_account"><?php _e('Enable Category-Driven Mode?', 'microblog-poster');?></label>
    <?php elseif(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','show_control_dashboard')):?>
    <br />
    <input type="checkbox" id="mbp_microblogposter_category_to_account" name="mbp_microblogposter_category_to_account" <?php if($default_behavior_cat_driven_value) echo 'checked="checked"';?> /> 
    <label for="mbp_microblogposter_category_to_account"><?php _e('Enable Category-Driven Mode?', 'microblog-poster');?></label>
    &nbsp;&nbsp;
    <?php _e('Available with the Enterprise Add-on.', 'microblog-poster');?> <a href="http://efficientscripts.com/support/microblogposter/auto-publish-in-category-driven-mode" target="_blank"><?php _e('More Infos', 'microblog-poster');?></a>
    <?php endif;?>
    
    <?php
    if(MicroblogPoster_Poster::is_method_callable('MicroblogPoster_Poster_Pro','show_control_dashboard'))
    {
        MicroblogPoster_Poster_Pro::show_control_dashboard();
    }
    else
    {
        ?>
        <style>
            #mbp_upgrade_notice_div_microblogposter
            {
                margin-top: 10px;
            }
        </style>
        
        <script>
            function mbp_show_upgrade_notice_microblogposter()
            {
                if(jQuery('#mbp_upgrade_notice_div_microblogposter').is(':visible'))
                {
                    jQuery('#mbp_upgrade_notice_div_microblogposter').hide();
                    jQuery('#mbp_upgrade_notice_lnk_microblogposter').html('<?php _e('Show complete Control Dashboard', 'microblog-poster');?>');
                }
                else
                {
                    jQuery('#mbp_upgrade_notice_div_microblogposter').show();
                    jQuery('#mbp_upgrade_notice_lnk_microblogposter').html('<?php _e('Hide complete Control Dashboard', 'microblog-poster');?>');
                }    
                
            }
        </script>
        &nbsp;<a href="#" id="mbp_upgrade_notice_lnk_microblogposter" onclick="mbp_show_upgrade_notice_microblogposter();return false;" ><?php _e('Show complete Control Dashboard', 'microblog-poster');?></a>
        <div id="mbp_upgrade_notice_div_microblogposter" style="display:none;"><?php _e('Available with the Pro / Enterprise Add-on.', 'microblog-poster');?> <a href="http://efficientscripts.com/web/products/addons" target="_blank"><?php _e('Upgrade Now', 'microblog-poster');?></a></div>
        <?php
    }
}

//Add the checkbox defined above to post/page/custom type edit screen.
function microblogposter_meta_box()
{
    if (MicroblogPoster_Poster::can_current_user_auto_publish())
    {
        add_meta_box('microblogposter_domain','MicroblogPoster','microblogposter_meta','post','advanced','high');
    
        $page_mode_name = "microblogposter_page_mode";
        $page_mode_value = get_option($page_mode_name, "");
        if($page_mode_value)
        {
            add_meta_box('microblogposter_domain','MicroblogPoster','microblogposter_pmeta','page','advanced','high');
        }

        $enabled_custom_types_name = "microblogposter_enabled_custom_types";
        $enabled_custom_types_value = get_option($enabled_custom_types_name, "");
        $enabled_custom_types = json_decode($enabled_custom_types_value, true);
        if(is_array($enabled_custom_types) && !empty($enabled_custom_types))
        {
            foreach($enabled_custom_types as $custom_type)
            {
                add_meta_box('microblogposter_domain','MicroblogPoster','microblogposter_custom_meta',$custom_type,'advanced','high',array('type'=>$custom_type));
            }
        }
    }
}

add_action('admin_menu', 'microblogposter_meta_box');

// Add settings link on plugin page
function microblogposter_plugin_settings_link($links) 
{ 
    $settings_link = '<a href="options-general.php?page=microblogposter.php">Settings</a>'; //get_admin_url()
    array_unshift($links, $settings_link); 
    return $links; 
}

$microblogposter_plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_{$microblogposter_plugin}", 'microblogposter_plugin_settings_link');

//Options

require_once "microblogposter_options.php";


?>