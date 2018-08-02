<?php

namespace Bonnier\WP\Cache\Settings;

use Bonnier\Willow\MuPlugins\Helpers\AbstractSettingsPage;

class SettingsPage extends AbstractSettingsPage
{
    protected $settingsKey = 'wp_cache_settings';
    protected $settingsGroup = 'wp_cache_settings_group';
    protected $settingsSection = 'wp_cache_settings_section';
    protected $settingsPage = 'wp_cache_settings_page';
    protected $toolbarName = 'Bonnier Cache';
    protected $title = 'WP Bonnier Cache settings:';
    protected $noticePrefix = 'WP Bonnier Cache:';

    protected $settingsFields = [
        'host_url' => [
            'type' => 'text',
            'name' => 'Cache Host URL',
        ]
    ];

    public function getCacheHost()
    {
        return $this->getSettingValue('host_url') ?: '';
    }
}
