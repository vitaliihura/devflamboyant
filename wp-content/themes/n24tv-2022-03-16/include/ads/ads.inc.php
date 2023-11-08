<?php
/** 
 * Current ads locations
 *
 * FRONT PAGE
 * +----------------------------------------+
 * |         LOGO     [header]              |
 * +-----------------------+----------------+
 * |             [billboard]                |
 * +----------------------------------------+
 * |                MASONRY                 |
 * +------------------------+---------------+
 * |   NOVICE               | [sidebar-top] |
 * |     ...                |   NOVOSTI     |
 * |   [news_bottom]        |   ANKETA      |
 * |                        |  *WIDGETAD*   |
 * |                        | BODI NA TEKO  |
 * |   WORLD                |               |
 * |    ...                 |               |
 * |   [world_bottom]       |               |
 * |       ...              |               |
 * |        [bottom]        |               |
 * +------------------------+---------------+
 * |             FOOTER                     |
 * +----------------------------------------+
 *
 * CATEGORY/TAG/LIST PAGE
 * +----------------------------------------+
 * |         LOGO     [header]              |
 * +-----------------------+----------------+
 * |             [billboard]                |
 * +----------------------------------------+
 * |                MASONRY                 |
 * +------------------------+---------------+
 * |   TITLE                | [sidebar-top] |
 * |     ...                |   ...         |
 * |                        |   ....        |
 * |                        |  ....         |
 * |  ..ARTICLES...         |               |
 * |                        |[sidebar-bottom]|
 * |    ...                 |               |
 * |                        |               |
 * |       ...              |               |
 * |        [bottom]        |               |
 * +------------------------+---------------+
 * |             FOOTER                     |
 * +----------------------------------------+
 *
 *
 * SINGLE PAGE
 * +----------------------------------------+
 * |         LOGO     [header]              |
 * +-----------------------+----------------+
 * |    [single_top]       | [sidebar-top]  |
 * | category_list         |   ...          |
 * |   TITLE               |   ....         |
 * |  sharing              |  ....          |
 * |  thumbnail            |                |
 * |    ARTICLE            |[sidebar-bottom]|
 * |  [single_bottom]      |                |
 * |  RELATED              |                |
 * |        [bottom]       |                |
 * |    COMMENTS           |                |
 * +-----------------------+----------------+
 * |             FOOTER                     |
 * +----------------------------------------+
 *
 * Locations:
 *  - oglasno ozadje    : background
 *  - 728x90 spodaj     : bottom
 *  - billboard_zgoraj  : billboard
 *  - 300x250 desno     : sidebar_top
 *  - 728x90 zgoraj     : up
 *  - banderola         : ??
 *  - 
 *
 *
 */

require(__DIR__ . '/adocean.inc.php');

function n24tv_ad_get_key(){

    global $adoceanConfig;

    static $key = NULL; // cache it

    if ($key === NULL){
        if (is_front_page()){
            $key = 'front-page';
        }
        elseif(is_category()){
            /**
             * Here we search for c:category-slug key
             */
            $category = get_category( get_query_var( 'cat' ) );
            $slug = $category->slug;
            $cat_key = 'c:' . $slug;
            if (isset($adoceanConfig[$cat_key])){
                $key = $cat_key;
            }
        }
        elseif (is_single()) {
            /**
             * Here we'll search for each category found for key c:
             */
            $search_keys = array();
            $categories = get_the_category();

            $cat_keys = array();

            foreach($categories as $cat){
                $cat_keys = array($cat->slug);
                $_cat = $cat;
                while ($_cat->category_parent > 0){
                    $_cat = get_category($_cat->category_parent);
                    $cat_keys[] = $_cat->slug;
                }
                $cat_keys = array_reverse($cat_keys);
                $search_keys[] = 's:' . implode('/', $cat_keys);
            }

            sort($search_keys);
            $search_keys = array_reverse($search_keys);

            foreach($search_keys as $cat_key){
                if (isset($adoceanConfig[$cat_key])){
                    $key = $cat_key;
                    break;
                }
            }
        }
    }

    return ($key ?? 'front-page');
}

function n24tv_adocean_get_master($key, $location){
    global $adoceanConfig;

    static $arrMasters = array(); // keep list of already displayed masters

    if (!isset($adoceanConfig[$key][$location]['master_id'])){
        return false;
    }
    else {
        $master_id = $adoceanConfig[$key][$location]['master_id'];
        if (isset($arrMasters[$master_id])){
            return '';
        }
        else {
            $arrMasters[$master_id] = true;
            return sprintf('<script type="text/javascript">ado.master({id: \'%s\', server: \'si.adocean.pl\' });</script>', $master_id);
        }
    }
}

function n24tv_adocean_get_slave($key, $location){
    global $adoceanConfig;

    if (!isset($adoceanConfig[$key][$location]['slave_id'])){
        return false;
    }
    else {
        $slave_id = $adoceanConfig[$key][$location]['slave_id'];
        $master_id = $adoceanConfig[$key][$location]['master_id'];
        return sprintf('<div id="%s"></div>' . 
                       '<script type="text/javascript">' . 
                       'ado.slave(\'%s\', {myMaster: \'%s\' });' . 
                       '</script>', $slave_id, $slave_id, $master_id);

    }
}

function n24tv_adocean_display($location){
    $key = n24tv_ad_get_key();
    $ret = '<!-- adocean key=' . $key . ' location=' . $location . ' -->';
    $master = n24tv_adocean_get_master($key, $location);
    if ($master === false){
        $master = n24tv_adocean_get_master('front-page', $location);
        if ($master === false){
            return '<!-- No master info found for key: ' . $key . ' location: ' . $location . '-->';
        }
    }
    $ret .= $master;
    $slave = n24tv_adocean_get_slave($key, $location);
    if ($slave === false){
        $slave = n24tv_adocean_get_slave('front-page', $location);
        if ($slave === false){
            return '<!-- No slave info found for key: ' . $key . ' location: ' . $location . ' -->';
        }
    }
    $ret .= $slave;

    return $ret;

}

?>