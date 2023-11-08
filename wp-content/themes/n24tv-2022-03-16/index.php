<?php 
if (!isset($post)) {
    $post = [];
}

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
                while ( have_posts() ) : the_post();
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
?>