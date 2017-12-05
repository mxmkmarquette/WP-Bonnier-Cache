<?php

namespace Bonnier\WP\Cache\Models;

use Bonnier\WP\Cache\Services\CacheApi;
use Bonnier\WP\Cache\Settings\SettingsPage;

class Post
{
    private static $settings;

    public static function watch_post_changes(SettingsPage $settingsPage)
    {
        self::$settings = $settingsPage;

        add_action('transition_post_status', [__CLASS__, 'post_status_changed'], 10, 3);
    }

    public static function post_status_changed($new_status, $old_status, $post)
    {
        //Ignore deleted draft posts
        if ($old_status === 'draft' && $new_status === 'trash') {
            return;
        }

        if ($new_status === 'publish') {
            self::update_post($post->ID);
        //If post is trashed or drafted
        } elseif ($new_status === 'trash' || $new_status === 'draft') {
            self::delete_post($post->ID);
        }
    }

    public static function update_post($postId)
    {
        return CacheApi::update($postId);
    }

    public static function delete_post($postId)
    {
        return CacheApi::update($postId, true);
    }

    public static function is_published($postId)
    {
        return get_post_status($postId) === 'publish';
    }
}
