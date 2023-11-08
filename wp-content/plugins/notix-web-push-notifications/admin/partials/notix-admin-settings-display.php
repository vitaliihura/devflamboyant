<?php
    function checkAuth() {
        $url = 'https://notix.io/api/wordpress/auth-check?appId=' . esc_attr(get_option(Notix::$NOTIX_APP_ID_SETTINGS_KEY));

        $args = array(
            'headers' => array(
                'Authorization-Token' => esc_attr(get_option(Notix::$NOTIX_API_TOKEN_SETTINGS_KEY))
            )
        );

        return wp_remote_get($url, $args);
    }

    function drawTooltip($tooltip = '') {
	    if ($tooltip != '') {
		    echo "<div class='notix-tooltip'>
	                    <svg xmlns='http://www.w3.org/2000/svg' height='1em' viewBox='0 0 512 512'><path d='M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM169.8 165.3c7.9-22.3 29.1-37.3 52.8-37.3h58.3c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24V250.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1H222.6c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z'/></svg>
                        <span class='notix-tooltiptext'>" . $tooltip . "</span>
                    </div>";
	    }
    }

    function view_setting($key, $name, $type = 'text', $tooltip = '') {
        $option = get_option($key);
        echo '<div class="notix-options-block">';

        switch ($type) {
            case 'text':
	            echo "<div class='notix-features-block'>";
	            if ($name !== '') {
		            echo "<label for='" . esc_attr($key) . "' class='notix-plugin-label'>" . $name . "</label>";
	            }
	            drawTooltip($tooltip);
                echo "<input class='notix-options-block-input'  id=" . esc_attr($key) . "' size='54' name='" . esc_attr($key) . "' type='text' value='" . sanitize_text_field($option) . "' />";
	            echo '</div>';
                break;
            case 'checkbox':
                $checked = esc_attr(get_option($key)) === 'on' ? 'checked': "";
                echo "<div class='notix-features-block-sub'>";

                echo "<label for='" . esc_attr($key) . "' class='notix-plugin-label'>" . $name . "</label>";
	            drawTooltip($tooltip);
                echo "<input class='notix-options-block-input'  id=" . esc_attr($key) . "' name='" . esc_attr($key) . "' type='checkbox' " . $checked . "/>";

                echo '</div>';
                break;
            default:
                break;
        }

        echo '</div>';
    }

    $checkAuthStatus = -1;
    $responseCheckAuth = checkAuth();
    $isFailedCheckAuth = false;
    $isFailedCheckAuthErrors = [];

    if(is_wp_error($responseCheckAuth)) {
	    $isFailedCheckAuth = true;
	    $isFailedCheckAuthErrors = $responseCheckAuth->get_error_messages();
    } else {
	    $checkAuthStatus = wp_remote_retrieve_response_code($responseCheckAuth);
    }

    $isConnected = $checkAuthStatus === 200;
    $isEmptyConnectionData = esc_attr(get_option(Notix::$NOTIX_APP_ID_SETTINGS_KEY)) === '' && esc_attr(get_option(Notix::$NOTIX_API_TOKEN_SETTINGS_KEY)) === '';

    // Features
    $isEnabledTagsNotify = esc_attr(get_option('notix_tag_notify_feature_enabled_setting')) === 'on';
    $isEnabledAutoSend = esc_attr(get_option('notix_auto_send_feature_enabled_setting')) === 'on';
?>

<script>
    var isEditMode = false;
    var checkAuthStatus = <?php echo wp_kses($checkAuthStatus, '')?>;
    var isFailedAuth = <?php echo wp_kses($isFailedCheckAuth ? 'true' : 'false', '')?>;
    var isConnected = <?php echo wp_kses($isConnected ? 'true' : 'false', '')?>;
    var isEmptyConnectionData = <?php echo $isEmptyConnectionData ? 'true' : 'false'?>;

    document.addEventListener('DOMContentLoaded', () => {
        var connectedStateElement = document.querySelector('.notix-plugin-connected-state');
        var notConnectedErrorText = '';

        if (!isConnected && !isEmptyConnectionData) {
            if (checkAuthStatus === 401) {
                notConnectedErrorText = ' (Invalid API token)';
            } else if (checkAuthStatus === 403){
                notConnectedErrorText = ' (Invalid App ID)';
            }
        }

        if (isFailedAuth) {
            connectedStateElement.innerText = '× Plugin is not connected to your Notix account. \nAuth request failed: ';

	        <?php foreach ($isFailedCheckAuthErrors as $value) { ?>
                connectedStateElement.innerText += '\n(<?php echo esc_attr($value) ?>)';
	        <?php } ?>

        }
        else {
            connectedStateElement.innerText = isConnected ? '✓ Plugin is connected to your Notix account'
                : '× Plugin is not connected to your Notix account' + notConnectedErrorText;
        }


        connectedStateElement.className = isConnected ? 'notix-plugin-connected-state notix-plugin-connected-state-green'
            : 'notix-plugin-connected-state notix-plugin-connected-state-red';


        var changeSettingsButtonElement = document.querySelector('.notix-change-settings-button');

        changeSettingsButtonElement.value = isConnected ? "Change settings" : "Connect account";

        var connectedFieldsElement = document.querySelector('.notix-plugin-connected-fields');

        if (isConnected) {
            connectedFieldsElement.removeAttribute("hidden");
        } else {
            connectedFieldsElement.setAttribute("hidden", "");
        }

    })

    function changeSettingsClick() {
        var connectLabelElement = document.querySelector('.notix-plugin-connect-label');

        var connectElement = document.querySelector('.notix-plugin-connect');
        var settingsFormElement = document.querySelector('.notix-settings-form');

        connectElement.setAttribute("hidden", "");
        settingsFormElement.removeAttribute("hidden");
        connectLabelElement.removeAttribute("hidden");
    }
</script>

<div class="notix-wrap">
    <div class="notix-admin-header">
        <div class="notix-admin-logo icon32">
            <svg width="120" height="38" viewBox="0 0 120 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M51.6401 7.19076C49.5801 6.07999 47.1801 5.49538 44.5201 5.49538C41.8201 5.49538 39.4001 6.0605 37.3401 7.19076C35.2801 8.32102 33.6601 9.87999 32.5401 11.8482C31.4201 13.7969 30.8601 16.0574 30.8601 18.5128C30.8601 20.9877 31.4201 23.2287 32.5401 25.1774C33.6601 27.1456 35.2801 28.7046 37.3401 29.8349C39.4001 30.9456 41.8201 31.5302 44.5201 31.5302C47.1801 31.5302 49.5801 30.9651 51.6401 29.8349C53.7001 28.7046 55.3201 27.1456 56.4401 25.1774C57.5601 23.2287 58.1201 20.9682 58.1201 18.5128C58.1201 16.0379 57.5601 13.7969 56.4401 11.8482C55.3201 9.87999 53.7001 8.30153 51.6401 7.19076ZM48.3201 24.8072C47.2201 25.4502 45.9401 25.7815 44.5201 25.7815C43.0801 25.7815 41.8001 25.4502 40.7201 24.8072C39.6201 24.1641 38.7601 23.2677 38.1601 22.1764C37.5601 21.0851 37.2401 19.8379 37.2401 18.4933C37.2401 17.1487 37.5401 15.921 38.1601 14.8102C38.7601 13.719 39.6201 12.8226 40.7201 12.1795C41.8201 11.5364 43.1001 11.2051 44.5201 11.2051C45.9401 11.2051 47.2401 11.5364 48.3201 12.1795C49.4201 12.8226 50.2601 13.719 50.8401 14.8102C51.4201 15.921 51.7201 17.1487 51.7201 18.4933C51.7201 19.8379 51.4201 21.0656 50.8401 22.1764C50.2601 23.2677 49.4001 24.1641 48.3201 24.8072Z" fill="#005A2D"/>
                <path d="M76.8401 25.2359C75.8201 25.7036 74.8401 25.9374 73.8801 25.9374C72.8401 25.9374 72.0801 25.6646 71.5801 25.08C71.0601 24.4954 70.8201 23.6185 70.8201 22.4882V11.6728H77.9401V6.23589H70.8201V0.77948H64.4601V6.23589H63.1601C61.0601 6.23589 59.3401 7.8923 59.3401 9.95794V11.6923H64.4401V22.4882C64.4401 25.4113 65.1801 27.6718 66.6601 29.2113C68.1401 30.7508 70.2601 31.5302 72.9801 31.5302C74.0401 31.5302 75.0201 31.4328 75.9001 31.2574C76.7801 31.082 77.7201 30.8092 78.7201 30.361L78.9401 30.2636L77.1601 25.0605L76.8401 25.2359Z" fill="#005A2D"/>
                <path d="M89.38 6.2359H83.02V31.0236H89.38V6.2359Z" fill="#005A2D"/>
                <path d="M110.1 18.24L118.66 6.2359H111.2L107.58 11.4195L106 13.8359L104.46 11.4L100.84 6.2359H93.2801L101.84 18.24L92.7401 31.0236H100.14L103.6 26.1713L106 22.6441L108.32 26.1713L111.64 31.0236H119.2L110.1 18.24Z" fill="#005A2D"/>
                <path d="M13.4 5.49538H13.18C6.36005 5.49538 0.800049 10.9128 0.800049 17.5579V31.5108H1.18005C4.70005 31.5108 7.16005 28.802 7.16005 24.9046V17.5579C7.16005 14.7128 9.58005 11.6728 13.2 11.6728H13.4C16.9401 11.6728 19.4 14.7713 19.4 17.5579V24.9046C19.4 28.802 21.88 31.5108 25.4 31.5108H25.78V17.5579C25.78 10.9128 20.24 5.49538 13.4 5.49538Z" fill="#005A2D"/>
                <path d="M13.3001 33.2646C11.8001 33.2646 10.3201 33.1867 8.86011 33.0502C8.94011 35.3692 10.9001 37.2205 13.3001 37.2205C15.7001 37.2205 17.6601 35.3692 17.7401 33.0502C16.2801 33.1867 14.8001 33.2646 13.3001 33.2646Z" fill="#005A2D"/>
            </svg>
        </div>
    </div>

    <div class="notix-plugin-display">
        <p class="notix-plugin-connect-label" hidden>Connect your Notix account</p>
        <?php settings_errors(); ?>
        <p class="notix-plugin-text">
            To use this plugin you have to be registered at
            <a href="https://app.notix.co/" target="_blank">https://app.notix.co/</a>
            , have at least one verified source and an added tag.
            <a href="https://help.notix.co/en/collections/2826264-wordpress-plugin" target="_blank"> Read more about the plugin</a>
        </p>

        <div class="notix-plugin-connect">
            <p class="notix-plugin-connected-state"></p>

            <div class="notix-plugin-connected-fields" hidden>
                <p class="notix-field-label">App ID</p>
                <?php
                    echo esc_attr(get_option(Notix::$NOTIX_APP_ID_SETTINGS_KEY))
                ?>

                <p class="notix-field-label">API token</p>
                <?php
                    echo esc_attr(get_option(Notix::$NOTIX_API_TOKEN_SETTINGS_KEY))
                ?>

                <p class="notix-field-label" style="display:none">Features</p>
                <div class="notix-features-block" style="display:none">
                    <div class="notix-features-block-sub">
                        <label for="notix_auto_send_feature" class="notix-plugin-label">Automatic push sending for new posts</label>
                        <input type="checkbox" id="notix_auto_send_feature" disabled name="notix_auto_send_feature" <?php echo $isEnabledAutoSend ? 'checked' : ''?> />
                    </div>
                </div>
            </div>

            <input class="button button-primary notix-button notix-change-settings-button" type="button" onclick="changeSettingsClick()">
        </div>



        <form method="POST" action="options.php" class="notix-settings-form" hidden>
            <?php
            settings_fields( 'notix_general_settings' );
            ?>

            <p class="notix-field-label">App ID</p>
            <?php
            view_setting(Notix::$NOTIX_APP_ID_SETTINGS_KEY, 'Notix App ID');
            ?>
            <p class="notix-field-hint">Find on your tag’s page</p>

            <p class="notix-field-label">API token</p>
            <?php
            view_setting(Notix::$NOTIX_API_TOKEN_SETTINGS_KEY, 'Notix API Token');
            ?>

            <p class="notix-field-hint">Find in <a href="https://app.notix.co/auth/apiAccess" target="_blank">API section</a></p>

            <p class="notix-field-label">Features</p>
            <div class="notix-features-block">
	            <?php
	            view_setting(Notix::$NOTIX_AUTO_SEND_FEATURE_ENABLED, 'Automatic push sending for new posts', 'checkbox');
	            ?>

	            <?php
	            view_setting(Notix::$NOTIX_ROOT_SW_FETURE_ENABLED, 'Service worker uploaded in site root directory', 'checkbox', 'I downloaded sw.enot.js file from my dashboard and uploaded to the root directory');
	            ?>
            </div>

            <input name="submit" class="button button-primary notix-button notix-save-button" type="submit" value="<?php esc_attr_e( 'Save settings' ); ?>" />
        </form>
    </div>

</div>
