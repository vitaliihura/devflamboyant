<?php 
// get current page from query
$current_page = max( 1, get_query_var('paged') );

// Use custom query
$my_query = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 16, 'post_status' => 'publish', 'cat' => 18995, 'paged' => $current_page ) );
// switch it to global
$wp_query = $my_query;


// load header.php 
get_header();
?>
<!-- start index -->
<div id="n24tv-main" class="container">
    <div class="row">
        <div id="n24tv-content" class="col-md-8 n24tv-taxonomy-articles" role="main">
            <?php
                /* Start the Loop */
                $post_no = 1;
                while ( $my_query->have_posts() ) : $my_query->the_post();
                    if ($post_no % 2 > 0){
                        ?>
                        <div class="row">
                        <?php
                    }
                    ++$post_no;
            ?>
            <div class="col-xs-6">
                <?= n24tv_render_medium_article_loop(); ?>
            </div>
            <?php
                /* End the Loop */
                    if ($post_no % 2 > 0){
                        ?>
                        </div> <!-- end row -->
                        <?php
                    }
                endwhile;


                // if we have odd number of posts, we need to end <div>
                if ($post_no % 2 == 0){
                    echo "</div> <!-- end row -->\n";
                }
            ?>
            <div class="n24tv-pagination">
            <?= n24tv_render_pagination(); ?>
            </div>
            <div class="n24tv-ad">
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

                wp_reset_postdata();

?>