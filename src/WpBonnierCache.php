<?php

namespace Bonnier\WP\Cache;

use Bonnier\WP\Cache\Admin\PostMetaBox;
use Bonnier\WP\Cache\Models\Post;
use Bonnier\WP\Cache\Services\CacheApi;
use Bonnier\WP\Cache\Settings\SettingsPage;

class WpBonnierCache
{
    const TEXT_DOMAIN = 'wp-bonnier-cache';

    const CLASS_DIR = 'src';

    private static $instance;

    public $settings;

    public $file;

    public $basename;

    public $pluginDir;

    public $pluginUrl;

    private function __construct()
    {
        $this->file = __DIR__;
        $this->basename = plugin_basename($this->file);
        $this->pluginDir = plugin_dir_path($this->file);
        $this->pluginUrl = plugin_dir_url($this->file);

        load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname($this->basename.'/languages'));

        $this->settings = new SettingsPage();

        $this->bootstrap();
    }

    private function bootstrap()
    {
        Post::watch_post_changes($this->settings);
        CacheApi::bootstrap($this->settings);
        PostMetaBox::register();
    }

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();

            do_action('wp_bonnier_cache_loaded');
        }

        return self::$instance;
    }
}
