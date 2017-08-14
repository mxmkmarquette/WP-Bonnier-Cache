<?php

namespace Bonnier\WP\Cache\Services;

use Bonnier\WP\Cache\Models\Post;
use Bonnier\WP\Cache\Settings\SettingsPage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class CacheApi
{
    const CACHE_ADD = '/api/v1/add';
    const CACHE_UPDATE = '/api/v1/update';
    const CACHE_DELETE = '/api/v1/delete';

    protected static $settings;
    /** @var Client $client */
    protected static $client;

    public static function bootstrap(SettingsPage $settings)
    {
        self::$settings = $settings;
        self::$client = new Client([
            'base_uri' => self::$settings->get_setting_value('host_url'),
        ]);
    }

    /**
     * @param int $postID
     *
     * @return bool
     */
    public static function update($postID, $delete = false)
    {
        if(!wp_is_post_revision($postID) && !wp_is_post_autosave($postID)) {
            $contentUrl = is_numeric($postID) ? get_permalink($postID) : '';

            $uri = $delete || !Post::is_published($postID) ? self::CACHE_DELETE : self::CACHE_UPDATE;

            return self::post($uri, $contentUrl);
        }

        return null;
    }

    private static function post($uri, $url)
    {
        try {
            $response = self::$client->post($uri, ['json' => ['url' => $url]]);
        } catch(ClientException $e) {
            var_dump($e->getMessage());
            exit;
            return false;
        }

        if(200 === $response->getStatusCode()) {
            $result = \json_decode($response->getBody());
            var_dump("SUCCESS: ".$uri);
            exit;
            return isset($result->status) && 200 == $result->status;
        }

        return false;
    }
}