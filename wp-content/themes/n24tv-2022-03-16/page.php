<?php 
// load header.php 
get_header();
?>

<div id="n24tv-main" class="container">
    <div class="row">
        <div class="col-lg-8 col-md-9" role="main">

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