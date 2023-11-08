<?php
require(__DIR__ . '/schedule.inc.php');

header('Content-Type: application/json');

try {
    $op = ($_GET['op'] ?? NULL);
    if (empty($op)){
        throw new Exception('Operacija ni podana');
    }

    $ret_data = null;

    switch($op){
        case 'genre':
            $category = ($_GET['category'] ?? NULL);
            if (empty($category)){
                $ret_data = [];
                break;
            }
            $ret_data = ($arrCategories[$category] ?? ['']);
            break;
        case 'media_search':
            $search = ($_GET['search'] ?? '');
            $limit = 20; 
            $offset = 0;
            $query_images_args = array(
                'post_type'      => 'attachment',
                'post_mime_type' => 'image',
                'post_status'    => 'inherit',
                'posts_per_page' => $limit,
                'offset'        => $offset,
                's'             => $search
            );

            $query_images = new WP_Query( $query_images_args );
            if (count($query_images->posts) == 0){
                $ret_data = [];
                break;
            }
            foreach($query_images->posts as $image){
                $id = $image->ID;
                $full_path = get_attached_file($id);
                
                @list( $width, $height ) = getimagesize($full_path);

                $image_suitable = ($width >= 1280 && $height >= 720);

                $thumbnail = 'https://via.placeholder.com/150x150';
                $tmp = wp_get_attachment_image_src($id, 'thumbnail');
                if ($tmp !== false){
                    $thumbnail = $tmp[0];
                }

                $ret_data[] = [
                    'id'        => $id,
                    'title'     => $image->post_title,
                    'excerpt'   => $image->post_excerpt,
                    'mime'      => $image->post_mime_type,
                    'thumbnail' => $thumbnail,
                    'width'     => $width,
                    'height'    => $height,
                    'suitable'  => $image_suitable
                ];
            }
            break;
        default:
            throw new Exception('Neveljavna operacija');
    }
    echo json_encode($ret_data);
} catch (Exception $e){
    http_response_code(500);
    echo json_encode($e->getMessage());
}
?>