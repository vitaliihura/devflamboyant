<?php

class Notix_Public
{
    private $notix;
    private $version;

    public function __construct($notix, $version)
    {
        $this->notix = $notix;
        $this->version = $version;

	    if (esc_attr(get_option(Notix::$NOTIX_APP_ID_SETTINGS_KEY)) !== '' && esc_attr(get_option(Notix::$NOTIX_API_TOKEN_SETTINGS_KEY)) !== '') {
		    add_action( "wp_head", array( $this, 'injectNotixTag' ) );
	    }
    }

	function injectNotixTag()
	{
	    require_once 'partials/' . $this->notix . '-public-display-tag.php';
	}

	function injectNotixNotifyTagSubscribers()
	{
	    require_once 'partials/' . $this->notix . '-public-display-notify-tag-subscribers.php';
	}

	function injectNotixNotifyTagSubscribersPopup()
	{
		require_once 'partials/' . $this->notix . '-public-display-notify-tag-subscribers-popup.php';
	}

    public function enqueue_styles()
    {
        wp_enqueue_style($this->notix, plugin_dir_url(__FILE__) . 'css/notix-public.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script($this->notix, plugin_dir_url(__FILE__) . 'js/notix-public.js', array('jquery'), $this->version, false);
    }

}
