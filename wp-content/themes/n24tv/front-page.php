<?php 
// load header.php 
get_header();

/**
 * Skip everything on home_news (Novice)
 */
$skip_post_ids = [];
foreach (N24TV::get_featured_posts('home_news', 5) as $p) {
    //N24TV::__log('featured_posts: gor: ' . print_r($p, true));
    if (isset($p['id']) && $p['id'] > 0) {
        $skip_post_ids[$p['id']] = $p['id'];
    }
}
foreach (N24TV::get_featured_posts('home', 4) as $p) {
    //N24TV::__log('featured_posts: gor: ' . print_r($p, true));
    if (isset($p['id']) && $p['id'] > 0) {
        $skip_post_ids[$p['id']] = $p['id'];
    }
}
//N24TV::__log('skip_post_ids: ' . print_r($skip_post_ids, true), 'ERROR');


$arrPanels = array(
    'Novice'    =>
        array(
            'category_ids'    => array('home_news'),
            'skip_post_ids' => $skip_post_ids,
            'count'           => 5,
            'ad_location'     => 'front-page-novice',
            'ahref'     => '/rubrika/slovenija/',
        ),
    'Lokalno'   => 
        array(
            'category_ids'  => array(25164),
            'skip_post_ids' => $skip_post_ids,
            'count'         => 6, 
            'layout'        => 'small-left',
            'ahref'     => '/rubrika/lokalno/',
        ),
    'Svet'      => 
        array(
            /*'category_ids'  => array(11, -2, -8151, -11239),*/
            'category_ids'  => array(11),
            'skip_post_ids' => $skip_post_ids,
            'count'         => 5,
            'ad_location'   => 'front-page-svet',
            'ahref'     => '/rubrika/svet/',
        ),
    'Sproščeno' => 
        array(
            /*'category_ids'    => array(-2, -12, 17, 21, 41, 43, 268, -8158, -11239),*/
            'category_ids'      => array(17),
            'skip_post_ids' => $skip_post_ids,
            'count'           => 5,
            'ahref'     => '/rubrika/sprosceno/',
        ),
    'Kolumna'   => 
        array(
            'category_ids'  => array(-2, 269, -8158, -11239),
            'skip_post_ids' => $skip_post_ids,
            'count'         => 6,
            'layout'        => 'small-left',
            'ahref'     => '/rubrika/kolumna/',
        ),
    );

?>

<div id="n24tv-main" class="container">
    <div class="row">
        <div class="col-md-8" role="main">
            <?php
            foreach($arrPanels as $title => $config){
                echo n24tv_render_front_panel($config, $title);
            }
            ?>
<!--
            <div class="n24tv-ad">
                <div data-contentexchange-widget="DmyuiyFNXCdvJXjcL"></div>
            </div>
-->
            <div class="n24tv-ad hidden-xs">
                <?php require(__DIR__ . '/include/ads/bottom.php'); ?>
            </div>
        </div>
        <div id="n24tv-sidebar" class="col-md-4">
            <?php get_sidebar(); ?>
        </div>
    </div>
</div>

<?php
// load footer.php 
get_footer();
?>
