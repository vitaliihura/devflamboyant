<?php

/**
 * Post view count in WP meta
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    N24TV
 * @subpackage N24TV/includes
 */

/**
 * Post view count in WP meta
 *
 * This class defines all code necessary to run post view count in post meta based on td
 *
 * @note This class is used for all WPDB retreival!
 *
 * Shamelessly copied from td_booster of Newspaper theme. Since data is already inside!
 *
 * @since      1.0.0
 * @package    N24TV
 * @subpackage N24TV/includes
 * @author     Your Name <email@example.com>
 */
class N24TV_PostView {

    static protected $post_view_counter_key = 'post_views_count';

    //the name of the field where 7 days counter are kept(in a serialized array) for the given post
    static protected $post_view_counter_7_day_array = 'post_views_count_7_day_arr';

    //the name of the field for the total of 7 days
    static protected  $post_view_counter_7_day_total = 'post_views_count_7_day_total';

    static protected $post_view_7days_last_day = 'post_view_7days_last_day'; 

    static public function log($msg, $type = 'DEBUG'){
        N24TV::log('WPDB: PostView: ' . $msg, $type);
    }

    /**
     * Retreive post data from WPDB
     */
    static public function get_post_data($post_id){
        $post = get_post($post_id);
        if (is_object($post)){
            $title = $post->post_title;
            $date = $post->post_date;
            $status = $post->post_status;
            $author = $post->post_author;
            $comments = $post->comment_count;
            $content = substr(strip_tags($post->post_content), 0, 300);
            $permalink = get_permalink($post_id);
            $thumbnail = get_the_post_thumbnail($post_id, 'thumbnail', array('class' => 'img-responsive'));
            $thumbnail_s = get_the_post_thumbnail($post_id, 'n24tv-small-thumbnail', array('class' => 'img-responsive'));
            $thumbnail_m = get_the_post_thumbnail($post_id, 'n24tv-medium-thumbnail', array('class' => 'img-responsive'));
            $thumbnail_ml = get_the_post_thumbnail($post_id, 'n24tv-masonry-large');
            $thumbnail_mm = get_the_post_thumbnail($post_id, 'n24tv-masonry-medium');
            $thumbnail_ms = get_the_post_thumbnail($post_id, 'n24tv-masonry-small');
            $categories = wp_get_post_categories($post_id);
            $category_id = end($categories);
            $cat = get_category($category_id);
            $category_name = $cat->name;
            $category_link = get_category_link($category_id);
            $tags = array();
            $tmp = wp_get_post_tags($post_id);
            if (is_array($tmp)){
                foreach($tmp as $t){
                    $tags[] = $t->slug;
                }
            }
            reset($categories);


            $data = array(
                    'id'            => $post_id,
                    'status'        => $status,
                    'title'         => $title,
                    'date'          => $date, 
                    'author'        => $author,
                    'content'       => $content,
                    'thumbnail'     => $thumbnail,
                    'thumbnail_s'   => $thumbnail_s,
                    'thumbnail_m'   => $thumbnail_m,
                    'thumbnail_ml'  => $thumbnail_ml,
                    'thumbnail_mm'  => $thumbnail_mm,
                    'thumbnail_ms'  => $thumbnail_ms,
                    'permalink'     => $permalink,
                    'content'       => $content, 
                    'comments'      => $comments,
                    'categories'    => implode(',', $categories),
                    'tags'          => implode(',', $tags),
                    'category_name' => $category_name,
                    'category_link' => $category_link,
                    'top_comment_count' => 0,
                );
            return $data;
        }
        return false;
    }

    /**
     * Get categories
     */
    static public function get_categories(){
        $categories = get_categories();
        $children = array();
        $cats = array();
        $ret = array();
        foreach($categories as $cat){
            $cats[$cat->term_id] = array(
                    'id'    => $cat->term_id,
                    'name'  => $cat->name,
                    'slug'  => $cat->slug,
                    'link'  => get_category_link($cat->term_id),
                    'count' => $cat->count,
                    'parent'    => $cat->parent,
                    'children'  => array()
                );
            if ($cat->parent > 0){
                if (!isset($children[$cat->parent])){
                    $children[$cat->parent] = array();
                }
                $children[$cat->parent][] = $cat->term_id;
            }
        }
        foreach($children as $cat_id => $childs){
            $cats[$cat_id]['children'] = $childs;
        }
        return $cats;
    }

    /**
     * Get top posts from database
     *
     * @note This is the slowest possible option to retreive top posts! 
     */
    static public function get_top_posts($count = 5){
        global $wpdb;

        $DT = new DateTime('-2 days');
        
        self::log('get_top_posts: count=' . $count);

        $SQL = "SELECT " . 
               "p.ID " . 
               "FROM " . 
               $wpdb->postmeta . " AS pm " . 
               "LEFT JOIN " . 
               $wpdb->posts . " AS p ON p.ID = pm.post_id " . 
               "WHERE " . 
               // "p.post_date_gmt >= '" . $DT->format('Y-m-d H:i:s') . "' AND " . 
               "p.post_date_gmt >= '" . $DT->format('Y-m-d H:i:s') . "' " . 
               // "pm.meta_key = 'post_views_count_7_day_total' " . 
               "ORDER BY " . 
               "CAST(pm.meta_value AS UNSIGNED) DESC " . 
               "LIMIT " . $count;

        /*
        $SQL = "select " . 
               "post_id " . 
               "from " . 
               $prefix . "postmeta " . 
               "where " . 
               " meta_key = 'post_views_count_7_day_total' " . 
               "order by " . 
               "cast(meta_value as unsigned) desc " . 
               "limit " . $count;
        */
        $data = $wpdb->get_results($SQL, ARRAY_A);
        $ret = array();
        foreach($data as $rr){
            $ret[] = $rr['ID'];
        }
        // self::log('get_top_posts: returning: ' . print_r($ret, true), 'ERROR');
        // self::log('get_top_posts: time: ' . $DT->format('Y-m-d H:i:s'), 'ERROR');
        // self::log('get_top_posts: returning: ' . json_encode($data), 'ERROR');
        // self::log('get_top_posts: count=' . count($ret), 'ERROR');
        return $ret;
    }


    static public function get_post_view_count($post_id){
        global $wpdb;

        $ret = 0;

        $post_id = (int)$post_id;
        if ($post_id > 0){
            $SQL = "SELECT meta_value FROM " . $wpdb->postmeta . " WHERE `post_id` = " . $post_id . " AND `meta_key` = '" . self::$post_view_counter_key . "'";
            $data = $wpdb->get_results($SQL, ARRAY_A);
            if (isset($data[0]['meta_value'])){
                $ret = (int)$data[0]['meta_value'];
            }
        }
        return $ret;
    }

    static public function get_latest_posts_category($category_id = 0, $count = 5){
        $recent_posts = wp_get_recent_posts(
                            array(
                                    'numberposts'   => $count,
                                    'post_status'   => 'publish',
                                    'category'      => $category_id
                                ),
                                ARRAY_A
                        );
        $ret = array();
        foreach($recent_posts as $post){
            $ret[] = $post['ID'];
        }
        return $ret;
    }

    static public function get_latest_posts($count = 10){
        $categories = get_categories();
        $category_ids = array(0);
        foreach($categories as $cat){
            $category_ids[] = $cat->term_id;
        }
        $ret = array();
        foreach($category_ids as $cat_id){
            self::log('Fetching latests posts for category_id:' . $cat_id);
            $ret[$cat_id] = self::get_latest_posts_category($cat_id, $count);
        }
        return $ret;
    }

    static public function get_top_comments_posts(){
        global $wpdb;

        $DT = new DateTime('-1 days');
        /*
        $SQL = "SELECT comment_post_ID AS ID, COUNT(comment_ID) AS CommentCount " . 
               "FROM " . $wpdb->comments . " " . 
               "WHERE " . 
               "comment_date_gmt >= '" . $DT->format('Y-m-d H:i:s') . "' AND " . 
               "comment_approved = '1' " . 
               "GROUP BY ID " . 
               "ORDER BY CommentCount DESC " . 
               "LIMIT 10";
        */
        
        $SQL = "SELECT ID, COUNT(comment_ID) AS CommentCount " . 
               "FROM " . $wpdb->posts . " " . 
               "LEFT JOIN " . $wpdb->comments . " ON " . 
               $wpdb->comments . ".comment_post_ID = " . $wpdb->posts . ".ID " . 
               "WHERE " . 
               "post_type = 'post' AND post_status = 'publish' AND " . 
               "post_date >= '" . $DT->format('Y-m-d H:i:s') . "' " . 
               "GROUP BY ID " . 
               "ORDER BY COUNT(comment_ID) DESC " . 
               "LIMIT 10";
        
        /*
        $SQL = "SELECT ID FROM " . $wpdb->posts . " WHERE  ". 
               "post_type = 'post' AND post_status = 'publish' AND " . 
               "post_date >= '" . $DT->format('Y-m-d H:i:s') . "' " . 
               "ORDER BY comment_count DESC " . 
               "LIMIT 5";
        */
        $data = $wpdb->get_results($SQL, ARRAY_A);
        self::log('data: ' . print_r($data, true));
        $ret = array();
        foreach($data as $rr){
            $ret[] = $rr;
        }
        self::log('return: ' . print_r($ret, true));
        return $ret;
    }

    /**
     * Retreive posts information for related matching
     *
     * We should retreive (returned keys):
     *  - tags => (post_id, post_id, ..)
     *  - categories => ...
     *  - authors => ...
     */
    static public function get_posts_taxonomy_data(){
        global $wpdb;

        $DateTime = new DateTime('-3 months');

        $SQL = "SELECT " . 
               "p.ID, " . 
               "p.post_author, " . 
               "tt.taxonomy, " . 
               "t.slug " . 
               "FROM " . 
               $wpdb->posts . " AS  p " . 
               "LEFT JOIN " . 
               $wpdb->term_relationships . " AS tr ON tr.object_id = p.ID " . 
               "LEFT JOIN " . 
               $wpdb->term_taxonomy  . " AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id " . 
               "LEFT JOIN " . 
               $wpdb->terms . " AS t ON t.term_id = tt.term_id " . 
               "WHERE " . 
               "p.post_type = 'post' AND " . 
               "p.post_status = 'publish' AND " . 
               "p.post_date > '" . $DateTime->format('Y-m-d H:i:s') . "' AND " . 
               "tt.taxonomy IN ('post_tag', 'post_category')";
        $data = $wpdb->get_results($SQL, ARRAY_A);
        $ret = array(
            'tags'  => array(),
            'authors'   => array(),
            'categories'  => array(),
            );
        foreach($data as $tt){
            $id = $tt['ID'];
            $author = $tt['post_author'];
            $slug = $tt['slug'];
            $taxonomy = $tt['taxonomy'];

            if (!isset($ret['authors'][$author])){
                $ret['authors'][$author] = array();
            }
            $ret['authors'][$author][$id] = $id;

            switch($taxonomy){
                case 'post_tag':
                    if (!isset($ret['tags'][$slug])){
                        $ret['tags'][$slug] = array();
                    }
                    $ret['tags'][$slug][$id] = $id;
                    break;
                case 'post_category':
                    if (!isset($ret['categories'][$slug])){
                        $ret['categories'][$slug] = array();
                    }
                    $ret['categories'][$slug][$id] = $id;
                    break;
                default:
                    self::log('get_posts_taxonomy_data: Unknown taxonomy: ' . $taxonomy, 'ERROR');
            }
        }
        return $ret;
    }
    /**
     * Update multiple post counts with data
     *
     * @var array $data Data array: [post_id => count, ...]
     * @var int $time_limit Limit the update time. 
     * @return array Array of post_id's successfuly updated in database
     */
    static public function update_multiple($data, $time_limit = 5){
        $ret = array();
        $start = microtime(true);
        self::log('Updating multiple post counts for ' . count($data) . ' post(s) with time limit: ' . $time_limit);
        foreach($data as $post_id => $count){

            self::update_single($post_id, $count);
            $ret[] = $post_id;

            if (microtime(true) - $start > $time_limit){
                self::log('update multiple: Reached time limit after updating: ' . count($ret) . ' post(s): ' . $time_limit . 's', 'ERROR');
                break;
            }
        }
        self::log('Updated ' . count($ret) . ' out of ' . count($data) . ' in ' . number_format(microtime(true) - $start, 5) . ' second(s)');
        return $ret;
    }

    /**
     * Update single post count
     */
    static public function update_single($post_id, $count){
        $post_id = (int)$post_id;
        $count = (int)$count;

        if ($post_id > 0 && $count > 0){
            /**********************************/
            /** update post_view_counter_key **/
            /**********************************/

            $start = microtime(true);

            self::log('update(' . $post_id . '): ' . self::$post_view_counter_key . ': New count: ' . $count);

            // get current value
            $current_count = (int) get_post_meta($post_id, self::$post_view_counter_key, true);
            self::log('update(' . $post_id . '): Current count: ' . $current_count);
            if ($current_count > 0){
                // value is stored
                $current_count += $count;
            }
            else {
                // key does not exist yet
                $current_count = $count;
            }
            self::log('update(' . $post_id . '): ' . self::$post_view_counter_key . ': New count: ' . $current_count);
            update_post_meta($post_id, self::$post_view_counter_key, $current_count);

            /**************************
             ** Update 7 day counter **
             **************************/

            $current_day = date('N') - 1;

            $count_7_day_array = get_post_meta($post_id, self::$post_view_counter_7_day_array, true);

            self::log('update(' . $post_id . '): ' . self::$post_view_counter_7_day_array . ': Updating 7 day counter data. Data: ' . print_r($count_7_day_array, true));

            if (is_array($count_7_day_array)){
                // array data is available
                if (isset($count_7_day_array[$current_day])) { // check to see if the current day is defined - if it's not defined it's not ok.

                    if (get_post_meta($post_id, self::$post_view_7days_last_day, true) == $current_day) {
                        // the day was not changed since the last update
                        $count_7_day_array[$current_day] += $count;
                    } else {
                        // the day was changed since the last update - reset the current day
                        $count_7_day_array[$current_day] = $count;

                        //update last day with the current day
                        update_post_meta($post_id, self::$post_view_7days_last_day, $current_day);
                    }

                    // update the array
                    update_post_meta($post_id, self::$post_view_counter_7_day_array, $count_7_day_array);

                    // update the 7days sum
                    update_post_meta($post_id, self::$post_view_counter_7_day_total, array_sum($count_7_day_array));
                }
                else {
                    self::log('update(' . $post_id . '): No current day data in array?!', 'ERROR');
                }
            }
            else {
                // array is not initialized
                $count_7_day_array = array(0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0);
                $count_7_day_array[$current_day] = $count; // add one view on the current day

                // update the array
                update_post_meta($post_id, self::$post_view_counter_7_day_array, $count_7_day_array);

                // update last day with the current day
                update_post_meta($post_id, self::$post_view_7days_last_day, $current_day);

                // update the 7 days total - 1 view :)
                update_post_meta($post_id, self::$post_view_counter_7_day_total, 1);
            }
            self::log('update(' . $post_id . '): Done in ' . number_format(microtime(true) - $start, 5) . ' second(s)');
        } // if post_id > 0 && $count > 0
    }

    static function _ORIGINAL_update_page_views($post_id) {
        if (td_util::get_option('tds_p_show_views') == 'hide') {
            return;
        }

        global $page;

        //$page == 1 - fix for yoast
        if (is_single() and (empty($page) or $page == 1)) {  //do not update the counter only on single posts that are on the first page of the post
            //use general single page count only when `ajax_post_view_count` is disabled
            if(td_util::get_option('tds_ajax_post_view_count') != 'enabled') {
                //used for general count
                $count = get_post_meta($postID, self::$post_view_counter_key, true);
                if ($count == ''){
                    update_post_meta($postID, self::$post_view_counter_key, 1);
                } else {
                    $count++;
                    update_post_meta($postID, self::$post_view_counter_key, $count);
                }
            }

            //stop here if
            if (td_util::get_option('tds_p_enable_7_days_count') != 'enabled') {
                return;
            }

            //used for 7 day count array
            $current_day = date("N") - 1;  //get the current day
            $count_7_day_array = get_post_meta($postID, self::$post_view_counter_7_day_array, true);  // get the array with day of week -> count


            if (is_array($count_7_day_array)) {


                if (isset($count_7_day_array[$current_day])) { // check to see if the current day is defined - if it's not defined it's not ok.

                    if (get_post_meta($postID, self::$post_view_7days_last_day, true) == $current_day) {
                        // the day was not changed since the last update
                        $count_7_day_array[$current_day]++;
                    } else {
                        // the day was changed since the last update - reset the current day
                        $count_7_day_array[$current_day] = 1;

                        //update last day with the current day
                        update_post_meta($postID, self::$post_view_7days_last_day, $current_day);
                    }

                    // update the array
                    update_post_meta($postID, self::$post_view_counter_7_day_array, $count_7_day_array);

                    // update the 7days sum
                    update_post_meta($postID, self::$post_view_counter_7_day_total, array_sum($count_7_day_array));
                }

            } else {
                // the array is not initialized
                $count_7_day_array = array(0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0);
                $count_7_day_array[$current_day] = 1; // add one view on the current day

                // update the array
                update_post_meta($postID, self::$post_view_counter_7_day_array, $count_7_day_array);

                // update last day with the current day
                update_post_meta($postID, self::$post_view_7days_last_day, $current_day);

                // update the 7 days total - 1 view :)
                update_post_meta($postID, self::$post_view_counter_7_day_total, 1);
            }


            /*
            $count_7_day_array = get_post_meta($postID, self::$post_view_counter_7_day_array, true);
            $count_7_day_total = get_post_meta($postID, self::$post_view_counter_7_day_total, true);
            $count_7_day_total_all = get_post_meta($postID, self::$post_view_counter_key, true);

            $count_7_day_lastday = get_post_meta($postID, self::$post_view_7days_last_day, true);

            echo '<br>';
            print_r($count_7_day_array);
            echo "<br>total week: " . $count_7_day_total;
            echo "<br>total all time: " . $count_7_day_total_all;
            echo '<br>last day: ' . $count_7_day_lastday;
            */

        }
    }

}
