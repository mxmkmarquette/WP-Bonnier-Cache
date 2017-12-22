<?php

namespace Bonnier\WP\Cache\Services;

use Bonnier\WP\Cache\Settings\SettingsPage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class CacheApi
{
    const CACHE_ADD = '/api/v1/add';
    const CACHE_UPDATE = '/api/v1/update';
    const CACHE_DELETE = '/api/v1/delete';

    const ACF_CATEGORY_ID = 'field_58e39a7118284';

    protected static $settings;
    /** @var Client $client */
    protected static $client;

    public static function bootstrap(SettingsPage $settings)
    {
        self::$settings = $settings;
        $host_url = self::$settings->get_setting_value('host_url');
        if (!empty($host_url)) {
            self::$client = new Client([
                'base_uri' => $host_url,
            ]);
        }
    }

    /**
     * @param int $postID
     * @return bool
     * @internal param bool $delete
     */
    public static function update($postID)
    {
        if (wp_is_post_revision($postID) || wp_is_post_autosave($postID)) {
            return;
        }

        $post = get_post($postID);

        $newCat = get_term($_REQUEST['acf'][static::ACF_CATEGORY_ID]);
        $categoryLink = get_category_link($newCat->term_id);
        $contentUrl = $categoryLink.'/'.$post->post_name;

        return self::post(self::CACHE_UPDATE, $contentUrl);
    }

    /**
     * @param $postID
     * @return bool|void
     */
    public static function add($postID)
    {
        $post = get_post($postID);

        if (wp_is_post_revision($postID) || wp_is_post_autosave($postID)) {
            return;
        }

        $acfCategory = isset($_REQUEST['acf'][static::ACF_CATEGORY_ID])
            ? $_REQUEST['acf'][static::ACF_CATEGORY_ID]
            : false;

        if ($acfCategory) {
            $postCategory = get_term($acfCategory);
        } else {
            $postTerms = get_the_category($postID);
            if (isset($postTerms[0]) && $postTerms[0] instanceof \WP_Term) {
                $postCategory = $postTerms[0];
            }
        }

        $categoryLink = get_category_link($postCategory->term_id);
        $contentUrl = $categoryLink.'/'.$post->post_name;

        return self::post(self::CACHE_ADD, $contentUrl);
    }

    /**
     * @param $postID
     * @return bool|void
     */
    public static function delete($postID)
    {
        if (wp_is_post_revision($postID) || wp_is_post_autosave($postID)) {
            return;
        }

        global $post;
        $postTerms = get_the_category($postID);

        //Fix delete permalink.
        if (isset($postTerms[0]) && $postTerms[0] instanceof \WP_Term) {
            $postCategory = $postTerms[0];
            $categoryLink = get_category_link($postCategory->term_id);
            $contentUrl = $categoryLink.'/'.$post->post_name;
        }

        return self::post(self::CACHE_DELETE, $contentUrl);
    }

    /**
     * @param $uri
     * @param $url
     * @return bool
     */
    public static function post($uri, $url)
    {
        if (is_null(self::$client)) {
            return false;
        }

        try {
            $response = self::$client->post($uri, ['json' => ['url' => $url]]);
        } catch (ClientException $e) {
            return false;
        }

        if (200 === $response->getStatusCode()) {
            $result = \json_decode($response->getBody());
            return isset($result->status) && 200 == $result->status;
        }

        return false;
    }
}
