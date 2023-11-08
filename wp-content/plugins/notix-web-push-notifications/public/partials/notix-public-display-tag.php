<script id="script">
    const ntxs = document.createElement("script");
    ntxs.src = "https://notix.io/ent/current/enot.min.js";
    ntxs.onload = (sdk) => {
        sdk.startInstall({
            <?php
                if (esc_attr(get_option(Notix::$NOTIX_ROOT_SW_FETURE_ENABLED)) != "on") { ?>
sw: {
                url: "<?php echo plugins_url( 'sw.enot.js', __DIR__) ?>"
            },
            <?php
}
            ?>
appId: "<?php echo esc_attr(get_option(Notix::$NOTIX_APP_ID_SETTINGS_KEY))?>",
            loadSettings: true
        })
    };
    document.head.append(ntxs);
</script>