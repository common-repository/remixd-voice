<?php
/**
 * Plugin Name: Remixd Voice
 * Plugin URI: https://www.remixd.com/
 * Description: Remixd’s Audicles&trade; utilize publisher text and converts it into playable audio for easy and convenient on page consumption.
 * Version: 2.0.1
 * Author: Remixd
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 **/

class Remixd
{
    private $remixd_settings_page;
    private $remixd_player;

    public function __construct() {

        $this->defineConstants();

        require_once REMIXD_PLUGIN_DIR . 'lib/settings.php';
        require_once REMIXD_PLUGIN_DIR . 'lib/remixd-player.php';
        if (is_admin()) {
            $this->remixd_settings_page = new Remixd_Settings();
        }
        $this->remixd_player = new Remixd_Player();
    }

    public function defineConstants() {
        if (!defined('ABSPATH')) {
            exit; // Exit if accessed directly
        }
        if (!defined('REMIXD_PLUGIN_URL')) {
            define('REMIXD_PLUGIN_URL', plugin_dir_url(__FILE__));
        }
        if (!defined('REMIXD_PLUGIN_DIR')) {
            define('REMIXD_PLUGIN_DIR', dirname(__FILE__) . '/');
        }
        if (!defined('REMIXD_ROUTE_ACTIVATE_PLAYER')) {
            define('REMIXD_ROUTE_ACTIVATE_PLAYER',
                'https://dashboard-api.remixd.com/plugins/activate?activationKey=');
        }
        if (!defined('REMIXD_ROUTE_GET_TAG')) {
            define('REMIXD_ROUTE_GET_TAG',
                'https://dashboard-api.remixd.com/plugins/player-tag?activationKey=');
        }
    }
}

$remixd = new Remixd();