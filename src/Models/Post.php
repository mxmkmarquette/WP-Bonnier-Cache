<?php

namespace Bonnier\WP\Cache\Models;

use Bonnier\Willow\MuPlugins\LanguageProvider;
use Bonnier\WP\Cache\Services\CacheApi;
use Bonnier\WP\Cache\Settings\SettingsPage;
use Bonnier\WP\ContentHub\Editor\Models\WpComposite;
use WP_Post;

class Post
{
    private static $settings;

    const POST_TYPES = ['review', 'contenthub_composite', 'post', 'page'];

    public static function watch_post_changes(SettingsPage $settingsPage)
    {
        self::$settings = $settingsPage;

        // triggers when post status changes to trash
        add_action('wp_trash_post', [__CLASS__, 'remove_post'], 10, 1);
        add_action('publish_to_draft', [__CLASS__, 'remove_post'], 10, 1);

        // publish post
        if (defined('SLUG_CHANGE_HOOK')) {
            // Only trigger if ContentHub plugin is added
            add_action(WpComposite::SLUG_CHANGE_HOOK, [__CLASS__, 'url_changed'], 10, 3);
        }
        add_action('publish_to_publish', [__CLASS__, 'update_post'], 10, 1);

        add_action('draft_to_publish', [__CLASS__, 'publish_post'], 10, 1);
        add_action('untrashed_post', [__CLASS__, 'publish_post'], 10, 1);
    }

    public static function publish_post($publishedPostID)
    {
        $publishedPost = get_post($publishedPostID);

        if (!in_array($publishedPost->post_type, static::POST_TYPES) || $publishedPost->post_status !== 'publish') {
            return;
        }

        CacheApi::add($publishedPost->ID);
    }

    public static function update_post($postId)
    {
        $post = get_post($postId);
        if (!in_array($post->post_type, static::POST_TYPES) || $post->post_status !== 'publish') {
            return;
        }

        CacheApi::post(CacheApi::CACHE_UPDATE, get_permalink()); // Remove old url
        CacheApi::post(CacheApi::CACHE_UPDATE, get_permalink($postId)); // Add new

        if ($postId instanceof WP_Post) {
            $postId = $postId->ID;
        }

        $current_language_on_post = LanguageProvider::getPostLanguage($postId);
        $translations = LanguageProvider::getPostTranslations($postId);

        // Unset the current one, due to we just updated it above
        unset($translations[$current_language_on_post]);

        // Call update on all other articles
        foreach ($translations as $translation) {
            CacheApi::post(CacheApi::CACHE_UPDATE, get_permalink($translation)); // Add new
        }
    }

    public static function url_changed($changedPostID, $oldLink, $newLink)
    {
        $changedPost = get_post($changedPostID);

        if (!in_array($changedPost->post_type, static::POST_TYPES) || $changedPost->post_status !== 'publish') {
            return;
        }

        CacheApi::post(CacheApi::CACHE_UPDATE, $oldLink); // Remove old url
        CacheApi::post(CacheApi::CACHE_UPDATE, $newLink); // Add new
    }

    public static function remove_post($postID)
    {
        $changedPost = get_post($postID);

        if (!in_array($changedPost->post_type, static::POST_TYPES)) {
            return;
        }

        // if the post we try to trash is current published,

        CacheApi::update($postID);
    }

    /**
     * @param $postId
     * @return bool|void
     * @deprecated
     */
    public static function delete_post($postId)
    {
        CacheApi::update($postId);
    }

    public static function is_published($postId)
    {
        return get_post_status($postId) === 'publish';
    }
}
