<?php

class td_external_scripts {

    private static $scripts_loaded = array();




    /**
	 * Render a script
     * @param string $script_src
     * @return string
     */
    static function render_script( $script_src, $tag_id = '' ) {

        if( !in_array( $script_src, self::$scripts_loaded ) ) {
            self::$scripts_loaded[] = $script_src;

            return '<script' . ( $tag_id != '' ? ' id="' . $tag_id . '"' : '' ) . ' type="text/javascript" src="' . $script_src . '"></script>';
        }

        return '';

    }

}