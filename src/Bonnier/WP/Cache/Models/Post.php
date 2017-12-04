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

    public static function post_status_changed($new_status, $old_status, $other_post)
    {
        if ($old_status === 'draft' && $new_status === 'trash') {
            return;
        }

        global $post;

        // If it's a new post it can be null!
        if ($post === null) {
            return;
        }

        // Clear the old post id
        if (get_permalink($post) !== get_permalink($other_post)) {
            self::update_post($post->ID);
        }

        if ($new_status === 'publish') {
            self::update_post($other_post->ID);
        } elseif ($new_status === 'trash') {
            self::delete_post($other_post->ID);
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
