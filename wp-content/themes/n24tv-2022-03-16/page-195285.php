<?php 
/**
 * =============================================
 *
 * TESTING PAGE
 *
 * =============================================
 */
// load header.php 
get_header();
?>
<style>
@media (max-width: 768px) { 
    .n24tv-post-related .n24tv-article-thumbnail {

        float: left;
        width: 140px;
        padding-right: 10px;

    }
}
</style>
<?php
function new_n24tv_render_related_articles($posts, $limits = array()){

    $ret = '';
    foreach($posts as $post){
        $ret .= '<div class="col-sm-4 clearfix">' . n24tv_render_small_article_redis($post, $limits) . '</div>';
    }
    return $ret;

}

?>

<div id="n24tv-main" class="container">
    <div class="row">
        <div class="col-lg-8 col-md-9" role="main">

            <?php echo new_n24tv_render_masonry(); ?>

            <?php
                /* Start the Loop */
                while ( have_posts() ) : the_post();
            ?>
            <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="post-header">

                    <?php the_title('<h1>', '</h1>'); ?>

                </div>
                <div class="post-content">
                    <?php 
                    /**
                     * Check the post thumbnail
                     */
                    if (has_post_thumbnail()){
                        ?>
                        <div class="post-thumbnail">
                            <?php the_post_thumbnail(null, array('class' => 'img-responsive')); ?>
                        </div>
                        <?php
                    }
                    ?>
                    <?php the_content(); ?>
                </div>

                <div class="post-footer">

                    <!-- related content break: 768px -->
                    <div class="n24tv-post-related">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs n24tv-nav-tabs" role="tablist">
                            <li role="presentation" class="active"><a href="#related" aria-controls="related" role="tab" data-toggle="tab">Sorodni prispevki</a></li>
                            <li role="presentation"><a href="#author" aria-controls="author" role="tab" data-toggle="tab">Ostali prispevki avtorja</a></li>

                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="related">
                                <div class="row n24tv-post-related-items">
                            <?=
                                new_n24tv_render_related_articles(
                                    n24tv_get_related_posts_by_tags($tag_slugs, get_the_ID(), 3)
                                );
                            ?>
                                </div>
                            </div>
                            <!-- Related by Author and Tags -->
                            <div role="tabpanel" class="tab-pane" id="author">
                                <div class="row n24tv-post-related-items">
                                <?=
                                    new_n24tv_render_related_articles(
                                        n24tv_get_related_posts_by_author(get_the_author_meta('ID'), get_the_ID(), $tag_slugs, 3)
                                    );
                                ?>
                                </div>                             
                            </div>
                        </div>

                    </div>
                    <!-- end related content -->

                    <!-- related content break: 768px -->
                    <div class="n24tv-post-related">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs n24tv-nav-tabs" role="tablist">
                            <li role="presentation" class="active"><a href="#related" aria-controls="related" role="tab" data-toggle="tab">Sorodni prispevki</a></li>
                            <li role="presentation"><a href="#author" aria-controls="author" role="tab" data-toggle="tab">Ostali prispevki avtorja</a></li>

                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="related">
                                <div class="row n24tv-post-related-items">
                            <?=
                                new_n24tv_render_related_articles(
                                    n24tv_get_related_posts_by_tags($tag_slugs, get_the_ID(), 3)
                                );
                            ?>
                                </div>
                            </div>
                            <!-- Related by Author and Tags -->
                            <div role="tabpanel" class="tab-pane" id="author">
                                <div class="row n24tv-post-related-items">
                                <?=
                                    new_n24tv_render_related_articles(
                                        n24tv_get_related_posts_by_author(get_the_author_meta('ID'), get_the_ID(), $tag_slugs, 3)
                                    );
                                ?>
                                </div>                             
                            </div>
                        </div>

                    </div>
                    <!-- end related content -->

                </div>
            </div> <!-- end div.post -->
            <?php
                /* The the Loop */
                endwhile;
            ?>
        </div>
        <div id="n24tv-sidebar" class="col-lg-4 col-md-3">
            <?php get_sidebar(); ?>
        </div>
    </div>
</div>
<?php
// load footer.php 
get_footer();
?>