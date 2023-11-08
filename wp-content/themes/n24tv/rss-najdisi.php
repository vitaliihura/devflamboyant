<?php
/**
 * Template Name: Custom RSS Template - Feedname
 */
function getDateTime($t){
    $DT = new DateTime($t);
    $DT->setTimeZone(new DateTimeZone('Europe/Ljubljana'));
    return $DT->format('r');
}

/**
 * Najdi SI Kategorije
 *
 * - Vroče zgodbe
 * - Slovenija
 * - Regionalne novice
 * - Svet
 * - Gospodarstvo
 * - Šport
 * - Kronika
 * - Zanimivosti
 * - Avtomobilizem
 * - Znanost in IT
 * - Kultura
 * - Lepota in zdravje
 */
function getCategory($arrCats){
    $arrTmp = array();
    foreach($arrCats as $cat){
        $name = $cat->name;
        $arrTmp[] = $name;
        switch($name){
            case 'Slovenia':
            case 'Gospodarstvo':
            case 'Svet':
            case 'Šport':
            case 'Kronika':
            case 'Zanimivosti':
            case 'Kultura':
            case 'Okolje':
            case 'Zdravje':
            return $name;
        }
    }
    N24TV::log(' Najdi.SI RSS Feed: No such category mapping: ' . implode(',', $arrTmp));
    return 'Slovenija';
}

function isNews($arrCats){
    foreach($arrCats as $cat){
        if ($cat->name == 'News'){
            return true;
        }
    }
    return false;
}

header('Content-Type: '.feed_content_type('rss-http').'; charset='.get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>';
?>
<rss version="2.0"
     xmlns:xsi="http://www.w3.org/2001/XMLSchemainstance" 
     xsi:noNamespaceSchemaLocation="http://www.thearchitect.co.uk/schemas/rss-2_0.xsd"
    <?php do_action('rss2_ns'); ?>>
<channel>
        <title>Nova24TV</title>
        <link><?php bloginfo_rss('url') ?></link>
        <description><?php bloginfo_rss('description') ?></description>
        <language>SL</language>
        <?php while(have_posts()) : the_post(); ?>
        <?php 
        if (isNews(get_the_category())){
            continue;
        }
        $thumb_url = get_the_post_thumbnail_url( NULL, 'najdi-si-feed'); 
        $thumb_id = get_post_thumbnail_id();
        $thumb_type = get_post_mime_type($thumb_id);
        $thumb_file = get_attached_file($thumb_id);
        $thumb_size = filesize($thumb_file);
        ?>
                <item>
                        <title><?php the_title_rss(); ?></title>
                        <link><?php the_permalink_rss(); ?></link>
                        <description><![CDATA[<?php echo n24tv_ellipsis(strip_tags(get_the_content()), 247, '...') ?>]]></description>
                        <category><?php echo getCategory(get_the_category()) ?></category>
                        <pubDate><?php echo getDateTime(get_post_time('Y-m-d H:i:s', true)) ?></pubDate>
                        <enclosure url="<?php echo dirname($thumb_url) . '/' . urlencode(basename($thumb_url)); ?>" length="<?php echo $thumb_size; ?>" type="<?php echo $thumb_type; ?>" /> 
                        <?php rss_enclosure(); ?>
                        <?php do_action('rss2_item'); ?>
                </item>
        <?php endwhile; ?>
</channel>
</rss>
