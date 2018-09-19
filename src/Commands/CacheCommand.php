<?php

namespace Bonnier\WP\Cache\Commands;

use Bonnier\WP\Cache\Services\CacheApi;
use Bonnier\WP\ContentHub\Editor\Models\WpComposite;
use Exception;
use Illuminate\Support\Collection;
use WP_CLI;
use WP_CLI_Command;

class CacheCommand extends WP_CLI_Command
{
    const NAMESPACE = 'bonnier-cache';

    public static function register()
    {
        try {
            WP_CLI::add_command(self::NAMESPACE, __CLASS__);
        } catch (Exception $exception) {
            WP_CLI::line($exception->getMessage());
        }
    }

    /**
     * Sends all urls to be updated in the CacheManager.
     *
     * ## EXAMPLES
     *     wp bonnier-cache update
     */
    public function update()
    {
        $this->updateContent('page');
        $this->updateContent(WpComposite::POST_TYPE);
        $this->updateContent('category');
        $this->updateContent('post_tag');
        WP_CLI::success('All content has been sent to CacheService');
    }

    private function updateContent($type)
    {
        WP_CLI::line(sprintf('Updating %s...', $type));
        if (in_array($type, ['page', 'contenthub_composite'])) {
            $content = get_posts([
                'post_type' => $type,
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ]);
        } else {
            $content = get_terms([
                'taxonomy' => $type,
                'hide_empty' => false,
            ]);
        }

        if (empty($content)) {
            WP_CLI::warning(sprintf('No content found for \'%s\'', $type));
            return;
        }

        $urls = collect($content)->map(function ($content) use ($type) {
            if (in_array($type, ['page', 'contenthub_composite'])) {
                return get_permalink($content);
            } elseif ($type === 'category') {
                return get_category_link($content);
            } elseif ($type === 'post_tag') {
                return get_tag_link($content);
            }
            return null;
        })->reject(function ($url) {
            return is_null($url);
        });

        $this->postUpdate($urls->chunk(1000), $urls->count(), $type);
    }

    private function postUpdate(Collection $chunks, int $total, string $type)
    {
        $processed = 0;
        $chunks->each(function (Collection $urls) use (&$processed, $total, $type) {
            if (CacheApi::post('/api/v1/update', $urls->toArray())) {
                $processed += $urls->count();
                WP_CLI::line(sprintf(
                    'Sent %s of %s %s urls to be updated...',
                    $processed,
                    $total,
                    $type
                ));
            } else {
                WP_CLI::warning(sprintf(
                    'An error occured sending %s urls to cacheservice.',
                    $type
                ));
            }
        });
    }
}
