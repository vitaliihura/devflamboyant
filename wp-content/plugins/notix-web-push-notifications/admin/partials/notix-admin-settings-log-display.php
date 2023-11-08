<div class="notix-logs-wrap">
    <div class="notix-admin-header">

        <div class="notix-admin-configuration-logo icon32">
            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                 viewBox="0 0 32 32" style="enable-background:new 0 0 32 32;" xml:space="preserve">
            <style type="text/css">.st0{fill:#00582B;}</style>
                <g>
                    <g>
                        <path class="st0" d="M16.1,0.5h-0.2C9.3,0.5,4,5.8,4,12.3v13.6h0.4c3.4,0,5.7-2.7,5.7-6.5v-7.2c0-2.8,2.3-5.8,5.8-5.8h0.2
                        c3.4,0,5.8,3,5.8,5.8v7.2c0,3.8,2.4,6.5,5.8,6.5H28V12.3C28,5.8,22.7,0.5,16.1,0.5z"/>
                    </g>
                    <path class="st0" d="M16,27.6c-1.4,0-2.9-0.1-4.3-0.2c0.1,2.3,2,4.1,4.3,4.1c2.3,0,4.2-1.8,4.3-4.1C18.9,27.6,17.4,27.6,16,27.6z"
                    />
                </g>
            </svg>
        </div>

        <h2>NOTIX LOGS</h2>
    </div>

    <?php
        $options = get_option('notix_error_notices');

        if (isset($options)
            && isset($options['postId'])
            && isset($options['request'])
            && isset($options['response'])) { ?>
            <div class="log">
                <div class="log_post_id"> post_id: <?php echo esc_attr($options['postId'])?> </div>
                <div class="log_request"> request: <?php echo esc_attr($options['request'])?> </div>
                <div class="log_response"> response: <?php echo esc_attr($options['response'])?> </div>
            </div>
        <?php } ?>
</div>
