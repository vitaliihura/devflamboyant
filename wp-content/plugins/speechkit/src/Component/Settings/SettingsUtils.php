<?php

declare(strict_types=1);

namespace Beyondwords\Wordpress\Component\Settings;

use Beyondwords\Wordpress\Compatibility\Elementor\Elementor;
use Beyondwords\Wordpress\Component\Settings\PlayerVersion\PlayerVersion;
use Beyondwords\Wordpress\Core\CoreUtils;

/**
 * BeyondWords Settings Utilities.
 *
 * @package    Beyondwords
 * @subpackage Beyondwords/includes
 * @author     Stuart McAlpine <stu@beyondwords.io>
 * @since      3.5.0
 */
class SettingsUtils
{
    /**
     * Get the post types which are forbidden for use with BeyondWords.
     *
     * We DO NOT support most of the default WordPress post types. many would not work
     * correctly with BeyondWords.
     *
     * @since 3.7.0
     *
     * @static
     *
     * @return string[] Array of post type names.
     **/
    public static function getForbiddenPostTypes()
    {
        return [
            'attachment',
            'custom_css',
            'customize_changeset',
            'nav_menu_item',
            'oembed_cache',
            'revision',
            'user_request',
            'wp_block',
            'wp_template',
            'wp_template_part',
            'wp_global_styles',
            'wp_navigation',
        ];
    }

    /**
     * Get the allowed BeyondWords post types.
     *
     * These are the post types which are "allowed" (i.e. not "Forbidden") to be processed
     * by BeyondWords.
     *
     * @since 3.7.0
     *
     * @static
     *
     * @return string[] Array of post type names.
     **/
    public static function getAllowedPostTypes()
    {
        $postTypes = get_post_types();

        $forbidden = SettingsUtils::getForbiddenPostTypes();

        // Filter the array, removing unsupported/forbidden post types
        return array_values(array_diff($postTypes, $forbidden));
    }

    /**
     * Get the post types which BeyondWords supports.
     *
     * Primarily, any post type which does not have 'custom-fields' in the
     * 'supports' array will not work with BeyondWords.
     *
     * We also DO NOT support most default WordPress post types other than 'post'
     * and 'page' e.g. we don't support 'attachment', 'revision' and 'wp_template'.
     *
     * @since 3.0.0
     * @since 3.2.0 Removed $output parameter to always output names, never objects.
     * @since 3.2.0 Added `beyondwords_post_types` filter.
     * @since 3.5.0 Moved from Core\Utils to Component\Settings\SettingsUtils
     * @since 3.7.0 Refactored forbidden/allowed/supported post type methods to improve site health debugging info.
     *
     * @static
     *
     * @return string[] Array of post type names.
     **/
    public static function getSupportedPostTypes()
    {
        $allowedPostTypes = SettingsUtils::getAllowedPostTypes();

        /**
         * Filters the post types supported by BeyondWords.
         *
         * This defaults to all registered post types with 'custom-fields' in their 'supports' array.
         *
         * If any of the supplied post types do not support custom fields then they will be removed
         * from the array.
         *
         * @since 3.3.3
         *
         * @param string[] The post types supported by BeyondWords.
         */
        $supportedPostTypes = apply_filters('beyondwords_post_types', $allowedPostTypes);

        // Filter the array, removing unsupported post types
        $supportedPostTypes = array_filter($supportedPostTypes, function ($postType) {
            if (! post_type_supports($postType, 'custom-fields')) {
                return false;
            }

            return true;
        });

        return array_values($supportedPostTypes);
    }

    /**
     * Should we use the Legacy player version?
     *
     * @since 4.0.0
     *
     * @return boolean
     */
    public static function useLegacyPlayer()
    {
        // Use "Legacy" player for Elementor
        if (Elementor::isElementorActivated()) {
            return true;
        }

        // Use "Latest" player for all other admin screens
        if (is_admin()) {
            return false;
        }

        $playerVersion = get_option('beyondwords_player_version');

        return $playerVersion === PlayerVersion::LEGACY_VERSION;
    }

    /**
     * Do we have both a BeyondWords API key and Project ID?
     * We need both to call the BeyondWords API.
     *
     * @since  3.0.0
     * @since  4.0.0 Moved from Settings to SettingsUtils
     * @static
     *
     * @return boolean
     */
    public static function hasApiSettings()
    {
        return boolval(get_option('beyondwords_valid_api_connection'));
    }
}
