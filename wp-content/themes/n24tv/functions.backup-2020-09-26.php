<?php
/**
 * n24tv theme
 */

/**
 * Some notes
 *
 * @see n24tv_article_render
 * n24tv-SIZE-article
 *  <div class="n24tv-SIZE-article">                                        |< Start
 *      <div class="n24tv-article-thumbnail">                               |  < Link and thumbnail container
 *          <a href=PERMALINK>                                              :
 *              <img THUMBNAIL_SIZE>                                        | Link and Thumbnail
 *          </a>                                                            :
 *          <div class="n24tv-article-overlay-bottom">                      | Start of overlay that goes over thumbnail with category
 *              <span class="label label-n24tv">                            :
 *                  <a href="CATEGORY_LINK">CATEGORY_NAME</a>               | Link and label for post category
 *              </span>                                                     :
 *          </div>                                                          :
 *      </div>                                                              |  < end of link and thumbnail container
 *      <div class="n24tv-article-title">                                   |  < Start of article title
 *          <a href=PERMALINK>                                              :
 *              <h3>TITLE</h3>                                              | Link and article title
 *          </a>                                                            :
 *      </div>                                                              |  < End of article title
 *      <div class="n24tv-article-meta">                                    | Optional article meta
 *          <p class="article-meta-date">DATE</p>                           :  Article date
 *          <p class=" article-meta-comments-count">                        : 
 *              <i class="fa fa-comments-o"></i>COMMENT_COUNT               :  Article comments count
 *          </p>                                                            :
 *          <div class="clearfix"></div>                                    : Reset the above floats.
 *      </div>                                                              :
 *      <p class="n24tv-article-content">CONTENT</p>                        |  < Article content 
 *  </div>                                                                  |< End
 *
 *
 * n24tv-small-article
 *  - uses n24tv-small-thubmnail (220x150 crop) (redis: thumbnail_s)
 *
 * n24tv-medium-article
 *  - uses n24tv-medium-thumbnail (360x180 crop) (redis: thumbnail-m)

/**
 * Default DateTime format
 * We receive DateTime object from Redis objects and we need to convert it.
 */
define('N24TV_DATETIME_FORMAT', 'd. m. Y, H:i');

/**
 * Modify WP Menu walker to create Bootstrap3 ready navbar
 */
require(__DIR__ . '/include/wp_bootstrap_navwalker.php');
/**
 * Get the ADS
 */
require(__DIR__ . '/include/ads/ads.inc.php');

/**
 * Remove SQL_CALC_FOUND_ROWS from WP Queries.
 */
require(__DIR__ . '/include/sql_calc_found_rows.fix.php');

/**
 * Declare that we support post thumbnails
 */
add_theme_support( 'post-thumbnails' );

/**
 * Add support for widgets
 */

/**
 * Register our sidebars and widgetized areas.
 *
 */
function n24tv_widgets_init() {

    register_sidebar( array(
        'name'          => 'N24TV Sidebar',
        'id'            => 'n24tv-sidebar',
        'before_widget' => '    <div class="row n24tv-sidebar-item center-block">
        <div class="col-xs-12 n24tv-sidebar-panel">
            <div class="panel panel-n24tv">',
        'after_widget'  => '                </div>
            </div>
        </div>
    </div>',
        'before_title'  => '                <div class="panel-heading">
                    <h3 class="panel-title"><span>',
        'after_title'   => '</span></h3>
                </div>
                <div class="panel-body">',
    ) );

}
add_action( 'widgets_init', 'n24tv_widgets_init' );

/**
 * After setup theme hook
 */
add_action( 'after_setup_theme', 'n24tv_theme_setup' );

/**
 * Register custom RSS feeds
 */
add_action('init', 'n24tv_custom_rss');

/**
 * If you add another feed, make sure to run $wp_rewrite->flush_rules() once.
 */
function n24tv_custom_rss(){
        add_feed('najdisi', 'n24tv_najdisi_rss');
        add_feed('xmltv', 'n24tv_xmltv_rss');
        add_feed('xmltv2', 'n24tv_xmltv2_rss');
}

function n24tv_najdisi_rss(){
    get_template_part('rss', 'najdisi');
}

function n24tv_xmltv_rss(){
    get_template_part('rss', 'xmltv');
}

function n24tv_xmltv2_rss(){
    get_template_part('rss', 'xmltv2');
}

/**
 * Attempt to change login screen
 */
function my_login_logo() { ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(//nova24tv.si/wp-content/uploads/2015/07/logo-lezeci-300x67.png);
        height:65px;
        width:320px;
        background-size: 320px 65px;
        background-repeat: no-repeat;
            padding-bottom: 30px;
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'my_login_logo' );
function my_login_stylesheet() {
    wp_enqueue_style( 'custom-login', get_stylesheet_directory_uri() . '/style-login.css' );
}
add_action( 'login_enqueue_scripts', 'my_login_stylesheet' );
// end change login screen


/**
 * If we're not in admin, enqueue our scripts and styles
 */
if (!is_admin()){
    add_action("wp_enqueue_scripts", "n24tv_enqueue_scripts", 11);
}

/**
 * Remove Emojis
 */
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script' );
remove_action('admin_print_styles', 'print_emoji_styles' );

/**
 * == FUNCTIONS ==
 */

/**
 * Setup our theme
 */
function n24tv_theme_setup(){
    // add_image_size( string $name, int $width, int $height, bool|array $crop = false )
    // for medium article display (category, index, ...)
    add_image_size('n24tv-medium-thumbnail', 360, 180, true);
    // for small article display (related, top, sidebar, ...)
    add_image_size('n24tv-small-thumbnail', 220, 150, true);

    // for Masonry
    add_image_size('n24tv-masonry-large', 550, 400, true);
    add_image_size('n24tv-masonry-medium', 550, 200, true);
    add_image_size('n24tv-masonry-small', 275, 200, true);

    // for najdi.si
    add_image_size('najdi-si-feed', 160, 120, true);

    // for xmltv
    add_image_size('xmltv-feed', 1280, 720, true);
}

/**
 * Enqueue our JS scripts and CSS files (only in non-admin mode)
 */
function n24tv_enqueue_scripts(){
    $template_uri = get_template_directory_uri();

    wp_enqueue_style('boostrap', $template_uri . '/css/bootstrap.min.css');
    wp_enqueue_style('font-awesome', $template_uri . '/css/font-awesome.min.css');
    wp_enqueue_style('n24tv', get_stylesheet_uri(), 'bootstrap');
    wp_enqueue_script('jquery');

    // if we need bootstrap JS plugins, load this
    wp_enqueue_script('bootstrap', $template_uri . '/js/bootstrap.min.js', ['jquery'], null, true);
    // we use n24tv plugin script
    //wp_enqueue_script('n24tv', $template_uri . '/js/n24tv.js', ['jquery'], null, true);
}

/**
 * Remove the WP style: width: * from post images, as we'll create responsive width
 */
add_filter( 'img_caption_shortcode', 'n24tv_caption_html', 10, 3 );
function n24tv_caption_html( $current_html, $attr, $content ) {
    extract(shortcode_atts(array(
        'id'    => '',
        'align' => 'alignnone',
        'width' => '',
        'caption' => ''
    ), $attr));
    if ( 1 > (int) $width || empty($caption) )
        return $content;

    if ( $id ) $id = 'id="' . esc_attr($id) . '" ';

    return '<div ' . $id . 'class="wp-caption ' . esc_attr($align) . '">'
        . do_shortcode( $content ) . '<p class="wp-caption-text">' . $caption . '</p></div>';
}

function n24tv_comments_template(){
    global $post;
    $disqus_template = WP_PLUGIN_DIR . '/disqus-comment-system/comments.php';
    if (file_exists($disqus_template)){
        require($disqus_template);
    }
    else {
        comments_template('', true);   
    }
}

/**
 * Replace oversized text and append "..."
 */
function n24tv_ellipsis($text, $max = 70, $append = '&hellip;'){
    if (mb_strlen($text) <= $max) return $text;
    $out = mb_substr($text,0,$max);
    if (mb_strpos($text,' ') === FALSE) return $out.$append;
    return mb_ereg_replace('/\w+$/','',$out).$append;
}

function n24tv_render_pagination($args = array()){
    $ret = '';
    if ( $GLOBALS['wp_query']->max_num_pages > 1 ) {
        $args = wp_parse_args( $args, array(
                            'mid_size'           => 1,
                            'prev_text'          => __( 'Previous' ),
                            'next_text'          => __( 'Next' ),
                            'screen_reader_text' => __( 'Posts navigation' ),
        ) );
        /*
        // Make sure we get a string back. Plain is the next best thing.
        if ( isset( $args['type'] ) && 'array' == $args['type'] ) {
            $args['type'] = 'plain';
        }
        */
        $args['type'] = 'list';
        $links = paginate_links( $args );
        $links = str_replace('page-numbers', 'pagination pagination-sm', $links);
        $ret = '<nav aria-label="Page navigation">' . $links . '</nav>';
    }
    return $ret;
}

/**
 * Render share buttons for post
 */
function n24tv_render_single_post_share($url, $title){
    return 
'<div class="post-share">
    <div class="btn-group btn-group-sm" role="group">
        <a class="btn btn-facebook" href="https://www.facebook.com/sharer.php?u=' . urlencode($url) . '" onClick="window.open(this.href, \'fbWin\',\'left=50,top=50,width=600,height=350,toolbar=0\'); return false;">
            <i class="fa fa-facebook-official"></i> Facebook
        </a>
        <a class="btn btn-twitter" href="https://twitter.com/intent/tweet?text=' . urlencode(html_entity_decode($title, ENT_COMPAT, 'UTF-8')) . '&url=' . urlencode($url) . '&via=Nova24TV" onClick="window.open(this.href, \'twWin\',\'left=50,top=50,width=600,height=350,toolbar=0\'); return false;"">
            <i class="fa fa-twitter-square"></i> Twitter
        </a>
    </div>
</div>';
}

function new_n24tv_render_masonry($category_id = 0){

    $arrSizes = array(
            '',
            'thumbnail_ml',
            'thumbnail_mm',
            'thumbnail_mm',
            'thumbnail_mm'
        );

    $o = [];

    $o[] = '<div class="container">';

    $o[] = '<div class="n24tv-m2-wrap">';

    $posts = N24TV::get_featured_posts($category_id, 4);

    $i = 1;
    foreach ($posts as $_post) {

        $o[] = '<div class="n24tv-m2-post p' . $i . '">';

        $o[] = '<a href="' . $_post['permalink'] . '">';

        $o[] = '<div class="wrap">';

        $o[] = '<div class="image">';

        //$o[] = $_post[$arrSizes[$i]];
        $o[] = get_the_post_thumbnail($_post['id'], 'post-thumbnail');
        //$o[] = '<img class="lazy" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="' . get_the_post_thumbnail_url($_post['id'], 'n24tv-masonry-large') . '">';

        $o[] = '</div> <!-- image -->';

        $o[] = '</div> <!-- wrap -->';

        $o[] = '<div class="info">';

        $o[] = '<div class="cat">';
        $o[] = '<span class="label label-n24tv">' . $_post['category_name'] . '</span>';
        $o[] = '</div>';

        $o[] = '<h2>' . $_post['title'] . '</h2>';

        $o[] = '</div>';

        $o[] = '</a>';

        $o[] = '</div> <!-- p' . $i . ' -->';

        ++$i;
    }

    $o[] = '</div>';

    $o[] = '</div> <!-- div.container -->';

    return implode("\n", $o);
}

function n24tv_render_masonry($category_id = 0){

    $posts = N24TV::get_featured_posts($category_id, 4);

    $arrSizes = array(
            'thumbnail_ml',
            'thumbnail_mm',
            'thumbnail_ms',
            'thumbnail_ms'
        );

    $arrBS = array(
        'col-md-6 col-sm-12 col-xs-12',
        'col-md-6 col-sm-12 col-xs-12',
        'col-md-3 col-sm-6 col-xs-6',
        'col-md-3 col-sm-6 col-xs-6'
    );

    $arrClasses = array(
            'masonry-grid-item-large',
            'masonry-grid-item-medium',
            'masonry-grid-item-small',
            'masonry-grid-item-small'
        );
    $o = array();

    $o[] = '<div class="container" id="n24tv-masonry"><div class="">';
    $o[] = '<div class="masonry-grid">';

    $o[] = '<div class="masonry-grid-sizer col-xs-6 col-sm-6 col-md-3"></div> <!-- sizer -->';

    $post_no = 0;
    foreach($posts as $_post){
        $o[] = '<div class="masonry-grid-item ' . $arrBS[$post_no] . '">';
        $o[] = '<div class="' . $arrClasses[$post_no] . ' ' . $arrSizes[$post_no] . '">';

        $o[] = '<div class="n24tv-article-thumbnail">';
        $o[] = '<a href="' . $_post['permalink'] . '">';
        $o[] = $_post[$arrSizes[$post_no]];
        $o[] = '</a>';
        $o[] = '</div>';


        $o[] = '<div class="n24tv-article-overlay-top">';

        $o[] = '<span class="label label-n24tv">' . $_post['category_name'] . '</span>';
        $o[] = '<h3>' . $_post['title'] . '</h3>';


        $o[] = '</div>';

        $o[] = '</div> <!-- masonry-grid-item-* -->';
        $o[] = '</div> <!-- masonry grid item -->';
        ++$post_no;
    }

    $o[] = '</div> <!-- masonry grid -->';
    $o[] = '</div></div> <!-- container -->';

    return implode("\n", $o);

}


/**
 * Our base function to render all n24tv-*-article(s)
 *
 * If size is array, first element must be the "official" size, all ther elements are additional classes.
 *
 * @var $size string|array "small|medium"
 * @var $permalink string URL of post/article
 * @var $title string Article title
 * @var $limits array Array of limits in size of fields (eg title, content, ...) for ellipsis
 */
function n24tv_render_article(
    $size, $permalink, $title, $thumbnail, 
    $category_link = null, $category_name = null, 
    $date = null, $comment_count = null, 
    $content = null,
    $limits = array(),
    $_options = array()){

    $options = array_merge(
        $default_options = array(
            'meta'              => true,
            'content'           => false,
        ),
        $_options
    );

    $content = (isset($limits['content']) ? n24tv_ellipsis($content, $limits['content']) : $content);
    $_title = (isset($limits['title']) ? n24tv_ellipsis($title, $limits['title']) : $title);

    $addl_classes = array();
    if (is_array($size)){
        $tmp = $size;
        $size = array_shift($tmp);
        foreach($tmp as $addl_class_name){
            $addl_classes[] = $addl_class_name;
        }
    }

    $o = array('<div class="n24tv-' . $size . '-article ' . implode(' ', $addl_classes) . '">');

    // thumbnail and overlay
    $o[] = '<div class="n24tv-article-thumbnail">';
    $o[] = '<a title="' . $_title . '" href="' . $permalink . '">' . $thumbnail . '</a>';

    // check if we need overlay with category
    if ($category_name !== NULL && $category_link !== NULL){
        $o[] = '<div class="n24tv-article-overlay-bottom"><span class="label label-n24tv"><a href="' . $category_link . '">' . $category_name . '</a></span></div>';
    }
    $o[] = '</div> <!-- n24tv-article-thumbnail -->';

    // article title
    $o[] = '<div class="n24tv-article-title"><a title="' . $_title . '" href="' . $permalink . '"><h3>' . $_title . '</h3></a></div>';

    // check if meta (date and comment count are provided)
    if ($options['meta'] === true && $date !== null && $comment_count !== null){
        $o[] = '<div class="n24tv-article-meta">';
        $o[] = '<p class="n24tv-article-date">' . $date . '</p>';
        // $o[] = '<p class="n24tv-article-comment-count"><i class="fa fa-comments-o"></i>&nbsp;&nbsp;</p>';
        $o[] = '<div class="clearfix"></div>';
        $o[] = '</div>';
    }
    if ($options['content'] === true && $content !== null){
        $o[] = '<p class="n24tv-article-content">' . $content . '</p>';
    }

    $o[] = '</div> <!-- end n24tv-SIZE-article -->';
    return implode("\n", $o);
}

/**
 * Render small article with data from Redis
 */
function n24tv_render_small_article_redis($post, $limits = array(), $_options = array()){

    $options = array_merge(
        $default_options = array(
            'thumbnail_size'    => 's',
            'meta'              => true,
            'content'           => false,
        ),
        $_options
    );

    $thumbnail_size = $options['thumbnail_size'];
    $thumbnail_key = 'thumbnail' . ($thumbnail_size == 'n' ? '' : '_' . $thumbnail_size);

    return n24tv_render_article(
        'small', 
        $post['permalink'], 
        $post['title'], 
        $post[$thumbnail_key], 
        $post['category_link'], 
        $post['category_name'],
        null,   // date
        null,   // comment count
        null,   // content
        $limits,
        $options
    );
}

/**
 * Add n24tv-article-thumbnail-left to thumbnail container class
 */
function n24tv_render_left_small_article_redis($post, $limits = array(), $_options = array()){

    $options = array_merge(
        $default_options = array(
            'thumbnail_size'    => 's',
            'meta'              => true,
            'content'           => false,
        ),
        $_options
    );

    $thumbnail_size = $options['thumbnail_size'];
    $thumbnail_key = 'thumbnail' . ($thumbnail_size == 'n' ? '' : '_' . $thumbnail_size);

    if (!isset($post['DateTime'])){
    print_r($post);
    die();
}

    return n24tv_render_article(
            array('small', 'n24tv-article-thumbnail-left'), 
            $post['permalink'], 
            $post['title'], 
            $post[$thumbnail_key], 
            $post['category_link'], 
            $post['category_name'],
            $post['DateTime']->format(N24TV_DATETIME_FORMAT),   // date
            $post['comments'],   // comment count
            $post['content'],   // content
            $limits,
            $options
    );
}

/**
 * Render medium article with data from Redis
 */
function n24tv_render_medium_article_redis($post, $limits = array(), $_options = array()){

    $options = array_merge(
        $default_options = array(
            'thumbnail_size'    => 'm',
            'meta'              => true,
            'content'           => true,
        ),
        $_options
    );

    $thumbnail_size = $options['thumbnail_size'];
    $thumbnail_key = 'thumbnail' . ($thumbnail_size == 'n' ? '' : '_' . $thumbnail_size);

    return n24tv_render_article(
        'medium', 
        $post['permalink'], 
        $post['title'],
        $post[$thumbnail_key], 
        $post['category_link'],
        $post['category_name'],
        $post['DateTime']->format(N24TV_DATETIME_FORMAT),
        $post['comments'],
        $post['content'],
        $limits,
        $options
    );
}

function n24tv_render_left_medium_article_redis($post, $limits = array(), $_options = array()){

    $options = array_merge(
        $default_options = array(
            'thumbnail_size'    => 'm',
            'meta'              => true,
            'content'           => true,
        ),
        $_options
    );

    $thumbnail_size = $options['thumbnail_size'];
    $thumbnail_key = 'thumbnail' . ($thumbnail_size == 'n' ? '' : '_' . $thumbnail_size);

    return n24tv_render_article(
        array('medium', 'n24tv-article-thumbnail-left'), 
        $post['permalink'], 
        $post['title'],
        $post[$thumbnail_key], 
        $post['category_link'],
        $post['category_name'],
        $post['DateTime']->format(N24TV_DATETIME_FORMAT),
        $post['comments'],
        $post['content'],
        $limits,
        $options
    );
}

/**
 * Render medium article within a WP loop
 */
function n24tv_render_medium_article_loop($limits = array(), $_options = array()){
    
    $options = array_merge(
        $default_options = array(
            'thumbnail_size'    => 'm',
            'meta'              => true,
            'content'           => false,
        ),
        $_options
    );

    $thumbnail_size = $options['thumbnail_size'];
    switch($thumbnail_size){
        case 'm':
            $thumbnail_key = 'n24tv-medium-thumbnail';
            break;
        case 's':
            $thumbnail_key = 'n24tv-small-thumbnail';
            break;
        default:
            $thumbnail_key = 'thumbnail';
    }

    // gather information
    $size = 'medium';
    $permalink = get_the_permalink();
    $title = get_the_title();
    $thumbnail = (has_post_thumbnail() ? get_the_post_thumbnail(NULL, $thumbnail_key, array('class' => 'img-responsive')) : NULL);

    $category_name = $category_link = null;

    $categories = get_the_category();
    if (is_array($categories) && count($categories) > 0){
        $category = reset($categories);
        $category_link = get_category_link($category->term_id);
        $category_name = $category->cat_name;
    }
    $date = get_the_date();
    $comments = get_comments_number();

    return n24tv_render_article(
        $size, 
        $permalink,
        $title,
        $thumbnail, 
        $category_link,
        $category_name,
        $date,
        $comments,
        null,       // content
        $limits 
    );
}





function n24tv_render_sidebar_articles($posts, $limits = array()){
    $ret = '<div class="row">';
    foreach($posts as $post){
        $ret .= '<div class="col-xs-6" style="min-height: 190px;">' . n24tv_render_small_article_redis($post, $limits) . '</div>';
    }
    $ret .= '</div> <!-- end render_sidebar_articles -->';
    return $ret;
}

function n24tv_render_related_articles($posts, $limits = array()){

    $ret = '';
    foreach($posts as $post){
        $ret .= '<div class="col-sm-4 clearfix">' . n24tv_render_small_article_redis($post, $limits) . '</div>';
    }
    return $ret;

}


function n24tv_render_submenu($cat, $allCats){
    $name = strtolower($cat['name']);
    $name = preg_replace('/[^0-9a-z]+/', '_', $name);
    $id = 'n24tv_submenu_' . $name;

    $all_cats_to_display = array($cat['id']);

    $o = array('<ul class="n24tv-submenu" id="' . $id . '" style="display: none; list-style-type: none;">');

    $o[] = '<li><div class="container-fluid">';
    $o[] = '<div class="row">';
    $m = array();
    if (!empty($cat['children'])){
        $m[] = '<div class="list-group"><a href="' . $cat['link'] . '" class="list-group-item" data-id="' . $cat['id'] . '">Vsi prispevki</a>';
        foreach($cat['children'] as $cat_id){
            $subcat = ($allCats[$cat_id] ?? NULL);
            if ($subcat !== NULL){
                $all_cats_to_display[] = $subcat['id'];
                $m[] = '<a href="#" class="list-group-item" data-id="' . $subcat['id'] . '">' . $subcat['name'] . '</a>';
            }
        }
        $m[] = '</div>';
    }
    $m_col = 0;
    if (count($m) > 0){
        $o[] = '<div class="col-xs-3 n24tv-submenu-categories">' . implode("\n", $m) . '</div>';
        $m_col = 3;
    }
    $c_col = 12 - $m_col;
    $o[] = '<div class="col-xs-' . $c_col . ' n24tv-submenu-content">';

    $c = array();

    foreach($all_cats_to_display as $cat_id){
        $c[] = '<div class="n24tv-submenu-posts n24tv-submenu-posts-' . $cat_id . '" style="display: none;">';
        $c[] = '<div class"row">';
        foreach(N24TV::get_latest_posts($cat_id, 4) as $post){
            $c[] = '<div class="col-xs-3">' . n24tv_render_medium_article_redis($post, array('title' => 757), array('meta' => false, 'content' => false, 'thumbnail_size' => 's')) . '</div>';
        }
        $c[] = '</div>';
        $c[] = '</div>';
    }

    $o[] = implode("\n", $c);



    $o[] = '</div>';
    $o[] = '</div>';
    $o[] = '</div></li>';


    $o[] = '</ul>';
    return implode("\n", $o);
}

function n24tv_render_submenu_search(){

    $o = ['<ul class="n24tv-submenu n24tv-submenu-search" id="n24tv_submenu_search" style="display: none; list-style-type: none;">'];

    $o[] = '<li><div class="container-fluid">';

    $o[] = '<form class="form-inline" method="get" action="/">';

    $o[] = '<div class="form-group" style="padding-top: 30px;">';
    $o[] = '<div class="input-group">';
    $o[] = '<label class="sr-only" for="searchInput">Iskanje</label>';
    $o[] = '<input class="form-control" type="text" name="s" value="' . ($_GET['s'] ?? '') . '" id="searchInput" placeholder="Iskanje ...">';
    $o[] = '<span class="input-group-btn"><button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button></span>';
    $o[] = '</div>';
    $o[] = '</div>';

    $o[] = '</form>';

    $o[] = '</div></li></ul>';

    return implode("\n", $o);
}

/**
 * This function will render submenus, that will be then loaded by navbar.
 *
 * We can always movee this to AJAX.
 */
function n24tv_render_submenus(){

    $arrCategoryIds = array(
                            13246,  // video
                            49,     // slovenia
                            11,     // svet
                            17,     // sprosceno
                            45,     // panorama
                            269,    // kolumna
                            18995,  // news
                    );

    $arrCategories = N24TV::get_categories();

    $ret = '';

    foreach($arrCategoryIds as $cat_id){
        $cat_data = ($arrCategories[$cat_id] ?? NULL);
        if ($cat_data === NULL){
            continue;
        }

        $ret .= n24tv_render_submenu($cat_data, $arrCategories);
    }

    $ret .= n24tv_render_submenu_search();

    return $ret;

}












/**
 * ==== DATA RETRIEVAL FUNCTIONS ====
 */

function n24tv_get_top_posts($count = 4){
    $data = N24TV::get_top_posts();
    $posts = array();
    $total = 0;
    foreach($data as $post){
        ++$total;
        $post['DateTime'] = new DateTime($post['date']);
        $posts[] = $post;
        if ($total >= $count)
            break;
    }
    return $posts;
}

function n24tv_get_top_comments_posts($count = 3){
    $data = N24TV::get_top_comments_posts();
    $posts = array();
    $total = 0;
    foreach($data as $post){
        ++$total;
        $posts[] = $post;
        if ($total >= $count)
            break;
    }
    return $posts;
}


function n24tv_get_front_posts($config){
    if (!class_exists('N24TV'))
        return array();

    $category_ids = (isset($config['category_ids']) ? $config['category_ids'] : array(0));
    $count = (isset($config['count']) ? $config['count'] : 5);
    $skip_post_ids = (isset($config['skip_post_ids']) && is_array($config['skip_post_ids']) ? $config['skip_post_ids'] : []);

    $first_category_id = reset($category_ids);
    if (count($category_ids) == 1 && !is_numeric($first_category_id)){
        // custom fetured posts
//        N24TV::__log('calling: get_featured_posts: ' . $first_category_id . ', ' . $count);
        $ret = N24TV::get_featured_posts($first_category_id, $count);
//        N24TV::__log('returning: ' . print_r($ret, true));
        return $ret;
    }
    else {
        $skip_category_ids = array();
        $found_posts = array();
        foreach($category_ids as $category_id){
            if (!is_numeric($category_id)){
                continue;
            }
            if ($category_id < 0){
                $skip_category_id = abs($category_id);
                $skip_category_ids[$skip_category_id] = $skip_category_id;
            }
            else {
                $posts = N24TV::get_featured_posts($category_id, $count*2); // we need more, as we are removing some
                foreach($posts as $post){
                    $post['DateTime'] = new DateTime($post['date']);
                    /*$post['categories'] = explode(',', $post['categories']);*/
                    $found_posts[$post['id']] = $post;
                }
            }
        }
        // remove posts that are in skip_category_ids
        foreach($found_posts as $post_id => $post){
            foreach($skip_category_ids as $skip_category_id){
                if (in_array($skip_category_id, $post['categories'])){
                    unset($found_posts[$post_id]);
                }
            }
            if (in_array($post_id, $skip_post_ids)) {
                unset($found_posts[$post_id]);
            }
        }
        // sort posts by date
        uasort($found_posts, function($a, $b){
            if ($a['DateTime'] == $b['DateTime']) return 0;
            return ($a['DateTime'] > $b['DateTime'] ? - 1 : 1);
        });

        $posts = array_slice($found_posts, 0, $count, true);
        return $posts;
    }
}

function n24tv_render_front_panel($config, $title = 'Panel'){
    $posts = n24tv_get_front_posts($config);
    $ahref = isset($config['ahref']) ? $config['ahref'] : '#';

    if (count($posts) == 0){
        return '';
    }
    $layout = ($config['layout'] ?? 'default');
    $ad_location = ($config['ad_location'] ?? null);

    $o = array();
    $o[] = '<div class="panel panel-n24tv n24tv-front-articles">' . 
           '<div class="panel-heading">' . 
           '<a href="' . $ahref . '"><h3 class="panel-title"><span>' . $title . '</span></h3></a>' .
           '</div>' . 
           '<div class="panel-body">';

    switch($layout){
        case 'small-left':
            $post_no = 1;
            foreach($posts as $_post){
                if ($post_no % 2 > 0){
                    $o[] = '<div class="row">';
                }
                ++$post_no;

                $o[] = '<div class="col-sm-6">' . n24tv_render_left_small_article_redis($_post, array('title' => 100)) . '</div>';

                if ($post_no % 2 > 0){
                    $o[] = '</div>';
                }
            }
            if ($post_no % 2 == 0){
                $o[] = '</div>';
            }
            break;
        default:
            $first_post = array_shift($posts);
            $o[] = '<div class="row">';
            $o[] = '<div class="col-sm-6">';
            $o[] = n24tv_render_medium_article_redis($first_post, array('content' => 200, 'title' => 80));
            $o[] = '</div>';

            $o[] = '<div class="col-sm-6 n24tv-front-small-articles">';

            foreach($posts as $_post){
                $o[] = '<div class="row">';
                $o[] = n24tv_render_left_small_article_redis($_post, array('title' => 100));
                $o[] = '</div>';
            }

            $o[] = '</div></div>';
            break;
    }

    if ($ad_location !== NULL){
        $o[] = '<div class="row" style="padding-top: 10px;">' . 
               '<div class="col-xs-12 n24tv-ad">' . 
                n24tv_adocean_display($ad_location) .
               '</div>' . 
               '</div>';
    }
    $o[] = '</div></div>';

    return implode("\n", $o);
}


function n24tv_get_latest_posts($count = 4){
    return (class_exists('N24TV') ? N24TV::get_latest_posts(0, $count) : array());
}

/**
 * tags: Tags to search for
 * skip_post_id: Post Id to skip
 * count: how many results to return
 */
function n24tv_get_related_posts_by_tags($tags, $skip_post_id, $count = 3){
    return (class_exists('N24TV') ? N24TV::get_related_posts_by_tags($tags, $skip_post_id, $count) : array());
}

function n24tv_get_related_posts_by_author($author_id, $skip_post_id, $tags, $count){
    return (class_exists('N24TV') ? N24TV::get_related_posts_by_author($author_id, $skip_post_id, $tags, $count) : array());
}

