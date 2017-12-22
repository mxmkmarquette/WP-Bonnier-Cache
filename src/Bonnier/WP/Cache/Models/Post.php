<?php

namespace Bonnier\WP\Cache\Models;

use Bonnier\WP\Cache\Services\CacheApi;
use Bonnier\WP\Cache\Settings\SettingsPage;

class Post
{
    private static $settings;
    // Figure out a way to dynamically fetch this variable - otherwise
    // REMEMBER to update this field whenever ACF field keys are updated
    // if they ever are.
    const ACF_CATEGORY_ID = 'field_58e39a7118284';

    public static function watch_post_changes(SettingsPage $settingsPage)
    {
        self::$settings = $settingsPage;

        // triggers when post status changes to trash
        add_action('wp_trash_post', [__CLASS__, 'remove_post'], 10, 1);
        add_action('publish_to_draft', [__CLASS__, 'remove_post'], 10, 1);

        // publish post
        add_action('publish_to_publish', [__CLASS__, 'update_posts'], 10, 1);

        add_action('draft_to_publish', [__CLASS__, 'publish_post'], 10, 1);
        add_action('untrashed_post', [__CLASS__, 'publish_post'], 10, 1);
    }

    public static function publish_post($publishedPostID)
    {
        $publishedPost = get_post($publishedPostID);

        // Skip update on attachment & inherit
        if (! ('publish' === $publishedPost->post_status
                || ('attachment' === get_post_type($publishedPost) && 'inherit' === $publishedPost->post_status))
            || is_post_type_hierarchical($publishedPost->post_type)
        ) {
            return;
        }

        CacheApi::add($publishedPost->ID);
    }

    public static function update_posts($changedPostID)
    {
        $changedPost = get_post($changedPostID);

        global $post;
        $deleteOldFlag = false;

        if (!('publish' === $changedPost->post_status
                || ('attachment' === get_post_type($changedPost)
                    && 'inherit' === $changedPost->post_status)
            ) || is_post_type_hierarchical($changedPost->post_type)
        ) {
            return;
        }

        // Post name has changed. Clean old URL
        if ($changedPost->post_name != $post->post_name) {
            $deleteOldFlag = true;
        }

        // Check if the category has changed. If so, clean old URL
        $newPostCategory = get_term($_REQUEST['acf'][static::ACF_CATEGORY_ID]);
        $oldPostCategory = get_the_category($post->ID)[0];
        if ($newPostCategory->term_id != $oldPostCategory->term_id) {
            $deleteOldFlag = true;
        }

        // Delete if flagged
        if ($deleteOldFlag) {
            CacheApi::delete($changedPost->ID);

            // If delete is triggered add(!) the new one.
            CacheApi::add($changedPost->ID);
            return;
        }

        CacheApi::update($changedPost->ID);
    }

    public static function remove_post($postID)
    {

        // if the post we try to trash is current published,

        CacheApi::delete($postID);
    }

    /**
     * @param $postId
     * @return bool
     * @deprecated
     */
    public static function update_post($postId)
    {
        return CacheApi::update($postId);
    }

    /**
     * @param $postId
     * @return bool|void
     * @deprecated
     */
    public static function delete_post($postId)
    {
        return CacheApi::delete($postId);
    }

    public static function is_published($postId)
    {
        return get_post_status($postId) === 'publish';
    }
}
