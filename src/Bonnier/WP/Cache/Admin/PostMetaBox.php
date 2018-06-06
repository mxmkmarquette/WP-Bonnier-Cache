<?php

namespace Bonnier\WP\Cache\Admin;

use Bonnier\WP\Cache\Services\CacheApi;
use Bonnier\WP\Cache\WpBonnierCache;
use Carbon\Carbon;

/**
 * Class PostMetaBox
 *
 * @package \Bonnier\WP\Cache\Admin
 */
class PostMetaBox
{
    const MANUEL_TRIGGER_BUTTON = 'wp_bonnier_cache_meta_box_manuel_trigger_btn';
    const CACHE_VENDORS = [
        'Frontend',
        'CloudFlare',
        'Facebook',
        'Cxense'
    ];

    public static function register()
    {
        add_action('do_meta_boxes', function () {
            add_meta_box(
                'bp_wp_bonnier_cache',
                'Bonnier Cache',
                [__CLASS__, 'meta_box_content'],
                get_post_types(),
                'side'
            );
        });
        add_action('save_post', [__CLASS__, 'save_meta_box_settings']);
    }

    public static function meta_box_content($post)
    {
        $status = CacheApi::post(CacheApi::CACHE_STATUS, get_permalink($post->ID), true);
        foreach (self::CACHE_VENDORS as $vendor) {
            $val = strtolower($vendor) . '_called_at';
            static::printClearTime($vendor, isset($status->{$val}) ? $status->{$val} : null);
        }
        static::printManuelTriggerButton();
    }
    public static function save_meta_box_settings($post)
    {
        if (isset($_POST[static::MANUEL_TRIGGER_BUTTON])) {
            CacheApi::post(CacheApi::CACHE_UPDATE, get_permalink($post->ID), true);
        }
    }

    private static function printClearTime($cacheVendor, $clearTime)
    {
        $clearString = $clearTime ? static::dateTimeToDiff($clearTime) : 'Not cleared yet';
        printf("<strong>%s:</strong> %s <br>", $cacheVendor, $clearString);
    }

    private static function printManuelTriggerButton()
    {
        printf(
            '<br>
            <input class="button-secondary" type="submit" name="%s" value="Clear cache">
            <input class="button-secondary" type="button" onclick="window.location.reload()" value="Refresh">
            <br>
            <span>Warning this will refresh the page so save your work</span>',
            static::MANUEL_TRIGGER_BUTTON
        );
    }

    private static function dateTimeToDiff($clearTime)
    {
        if (class_exists(Carbon::class)) {
            return Carbon::parse($clearTime)->diffForHumans();
        }
        return $clearTime;
    }
}
