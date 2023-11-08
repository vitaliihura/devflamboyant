<?php 
// load header.php 
get_header();
?>
<!-- start single -->
<div id="n24tv-main" class="container">
<!-- start master -->

<script type="text/javascript">
/* (c)AdOcean 2003-2021, MASTER: nova24tv.si.nova24tv.si._LANKI.KOLUMNA */
ado.master({id: 'X.WmYa.m8sO.1FT5ycY5kpBjz8JPGo9d03uU0HMJ6Z3.g7', server: 'si.adocean.pl' });
</script>
<!--  end master  -->

    <div class="row">
        <div id="n24tv-content" class="col-md-8" role="main">
            <?php
                /* Start the Loop */
                while ( have_posts() ) : the_post();
            ?>
            <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="post-header">
                    <div class="n24tv-ad">
                        <?php require(__DIR__ . '/include/ads/single_top.php'); ?>
                    </div>
                    <div class="post-category">
                    <?php
                        $categories = get_the_category();
                        if (is_array($categories)){
                            foreach($categories as $category){
                                ?>
                                <span class="label label-n24tv"><a href="<?=get_category_link($category->term_id)?>"><?=$category->cat_name?></a></span>
                                <?php
                            }
                        }
                    ?>
                    </div>
                    <?php the_title('<h1>', '</h1>'); ?>
                    <div class="post-meta">
                        <p class="text-muted pull-left post-date">
                                <?= get_the_date(); ?>
                        </p>
                        <p class="text-muted pull-right post-comments-count">
                            <i class="fa fa-comments-o"></i> <?= get_comments_number(); ?>
                        </p>
                        <div class="clearfix"></div>
                    </div>
                    <?= n24tv_render_single_post_share(get_the_permalink(), get_the_title()); ?>
                </div>
                <div class="post-content">
                    <?php 
                    /**
                     * Check the post thumbnail
                     */
                    if (has_post_thumbnail()){
                        ?>
                        <div class="post-thumbnail wp-caption">
                            <?php the_post_thumbnail(null, array('class' => 'img-responsive')); ?>
                            <p class="wp-caption-text">
                            <?php echo get_post(get_post_thumbnail_id())->post_excerpt; ?>
                            </p>
                        </div>
                        <?php
                    }
                    ?>
                    <?php the_content(); ?>
                </div>
                <!-- post footer -->
                <div class="post-footer">
                    <div class="n24tv-ad">
                        <?php require(__DIR__ . '/include/ads/single_bottom.php'); ?>
                    </div>
                    <ul class="nav nav-pills post-tags">
                    <?php
                        $tag_slugs = array(); // for related serach
                        $tags = get_the_tags();
                        if (is_array($tags)){
                    ?>
                            <li><a><div class="label label-n24tv">Oznake</div></a></li>
                            <?php
                            foreach($tags as $tag){
                                $tag_slugs[] = $tag->slug;
                                ?>
                                <li><a href="<?= get_tag_link($tag->term_id)?>"><?=$tag->name?></a></li>
                                <?php
                            }
                        }
                    ?>
                    </ul>

                    <?= n24tv_render_single_post_share(get_the_permalink(), get_the_title()); ?>

                    <!-- related content -->
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
                                n24tv_render_related_articles(
                                    n24tv_get_related_posts_by_tags($tag_slugs, get_the_ID(), 3)
                                );
                            ?>
                                </div>
                            </div>
                            <!-- Related by Author and Tags -->
                            <div role="tabpanel" class="tab-pane" id="author">
                                <div class="row n24tv-post-related-items">
                                <?=
                                    n24tv_render_related_articles(
                                        n24tv_get_related_posts_by_author(get_the_author_meta('ID'), get_the_ID(), $tag_slugs, 3)
                                    );
                                ?>
                                </div>                             
                            </div>
                        </div>

                    </div>
                    <!-- end related content -->


                </div>
                <!-- end post footer -->
            </div> <!-- end div.post -->
            <?php
                /* The the Loop */
                endwhile;
            ?>
            <div class="n24tv-ad">
                <div data-contentexchange-widget="ADiryxqmuh7qBmJZ3"></div>
            </div>
            <div class="n24tv-ad">
                <?php require(__DIR__ . '/include/ads/single_comment.php'); ?>
            </div>
            <!-- comments template -->
            <?php n24tv_comments_template(); ?>
            <!-- end comments template -->
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
