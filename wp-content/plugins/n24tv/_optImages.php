#!/usr/bin/php
<?php
/**
 * Optimize and remove unused image dimensions.
 */
if ( !defined('ABSPATH') ) {
    /** Set up WordPress environment */
    require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );
}

include( ABSPATH . 'wp-admin/includes/image.php' );

define('_OPT_STATE_FILE', __DIR__ . '/state.dat');

ob_end_flush(); // so we see the output in CLI

echo " - Starting ...\n";

function read_state_file(){
    if (file_exists(_OPT_STATE_FILE)){
        $ret = unserialize(file_get_contents(_OPT_STATE_FILE));
        if (is_array($ret) && isset($ret['offset']) && isset($ret['limit'])){
            return $ret;
        }
        fwrite(STDERR, "WARNING: State file is corrupted: " . _OPT_STATE_FILE);
        unlink(_OPT_STATE_FILE);
    }
    return null;
}

function write_state_file($offset, $limit){
    $ret = ['offset' => (int)$offset, 'limit' => (int)$limit];
    file_put_contents(_OPT_STATE_FILE, serialize($ret));
}

$state = read_state_file();

if (is_array($state)){
    $offset = $state['offset'];
    $limit = $state['limit'];
    echo " -- starting from state file: offset=$offset limit=$limit\n";
}
else {
    $offset = 0;
    $limit = 5;
}

$run = true;

function _optimize_image($ext, $file){
    switch($ext){
        case 'jpg':
        case 'jpeg':
            $cmd = '/usr/bin/jpegtran -copy none -optimize -outfile ' . $file . ' ' . $file;
            echo " \t- Optimizing JPEG image: " . basename($file) . "\n";
            $arrOutput = array();
            $ret = NULL;
            exec($cmd, $arrOutput, $ret);
            if ($ret != 0){
                fwrite(STDERR, 'WARNING: Optimize JPEG: Command: ' . $cmd . ' returned non-0 value: ' . $ret . "\n");
            }
            break;
        case 'png':
            $cmd = '/usr/bin/optipng -fix -o2 -quiet -strip all ' . $file;
            echo " \t- Optimizing PNG image: " . basename($file) . "\n";
            $arrOutput = array();
            $ret = NULL;
            exec($cmd, $arrOutput, $ret);
            if ($ret != 0){
                fwrite(STDERR, 'WARNING: Optimize PNG: Command: ' . $cmd . ' returned non-0 value: ' . $ret . "\n");
            }
            break;
        case 'gif':
            fwrite(STDERR, "WARNING: Not optimizing GIF images ...\n");
            break;
        default:
            fwrite(STDERR, "WARNING: Unknown extension: " . $ext . "\n");
            die();
            break;
    }
}

do {
    echo " query: offset: $offset limit: $limit\n";
    
    write_state_file($offset, $limit);

    $query_images_args = array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'posts_per_page' => $limit,
        'offset'        => $offset
    );

    $query_images = new WP_Query( $query_images_args );

    if (count($query_images->posts) == 0){
        $run = false;
    }

    foreach($query_images->posts as $image){
        echo " - ID: " . $image->ID . " title: " . $image->post_title . "\n";
        $full_path = get_attached_file($image->ID);
        //echo " - Full path: $full_path\n";
        if (!file_exists($full_path)){
            fwrite(STDERR, "WARNING: Image not found @ $full_path\n");
            continue;
        }
        if ($full_path === false || strlen($full_path) == 0){
            fwrite(STDERR, "WARNING: Could not get full path for image: " . $image->ID . "\n");
            continue;
        }

        /**
         * Find thumbnails of this image
         */
        $file_info = pathinfo($full_path);
        $file_info['filename'] .= '-';

        /**
         * First, optimize the original image @ full_path
         */
        if (isset($file_info['extension'])){
            _optimize_image($file_info['extension'], $full_path);
        }
        else {
            fwrite(STDERR, "WARNING: No extension defined in file_info for image: " . $image->ID);
            continue;
        }

        $files = array();
        $path = opendir($file_info['dirname']);

        if ( false !== $path ) {
            while (false !== ($thumb = readdir($path))) {
                if (!(strrpos($thumb, $file_info['filename']) === false)) {
                    $files[] = $thumb;
                }
            }
            closedir($path);
            sort($files);
        }

        echo " \t- Removing thumbnails: ";
        foreach ($files as $thumb) {
            $thumb_fullpath = $file_info['dirname'] . DIRECTORY_SEPARATOR . $thumb;
            $thumb_info = pathinfo($thumb_fullpath);
            $valid_thumb = explode($file_info['filename'], $thumb_info['filename']);
            if ($valid_thumb[0] == "") {
                $dimension_thumb = explode('x', $valid_thumb[1]);
                if (count($dimension_thumb) == 2) {
                    if (is_numeric($dimension_thumb[0]) && is_numeric($dimension_thumb[1])) {
                        unlink($thumb_fullpath);
                        if (!file_exists($thumb_fullpath)) {
                            $thumb_deleted[] = sprintf("%sx%s", $dimension_thumb[0], $dimension_thumb[1]);
                            echo "o";
                        } else {
                            $thumb_error[] = sprintf("%sx%s", $dimension_thumb[0], $dimension_thumb[1]);
                            echo "x";
                        }
                    }
                }
            }
        }
        echo " Done.\n";

        echo " \t- Regenerate thumbnails ...";
        $metadata = wp_generate_attachment_metadata($image->ID, $full_path);

        if (is_wp_error($metadata)){
            fwrite(STDERR, "WARNING: Generate metadata: " . $metadata->get_error_message() . "\n");
        }
        elseif(empty($metadata)){
            fwrite(STDERR, "WARNING: Generate metadata: Unknown failure\n");
        }
        echo " OK.\n";
        echo " \t- Optimize thumbnails ... \n";
        foreach($metadata['sizes'] as $thumb){
            $file = $file_info['dirname'] . '/' . $thumb['file'];
            if (file_exists($file)){
                _optimize_image($file_info['extension'], $file);
            }
        }

        echo " \t- Update metadata ... ";
        wp_update_attachment_metadata($image->ID, $metadata);
        echo "OK.\n";
    }
    $offset += $limit;
} while($run);

?>