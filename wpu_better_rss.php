<?php
/*
Plugin Name: WPU Better RSS
Plugin URI: https://github.com/WordPressUtilities/wpu_better_rss
Update URI: https://github.com/WordPressUtilities/wpu_better_rss
Description: Better RSS feeds
Version: 0.1.0
Author: Darklg
Author URI: https://darklg.me/
Text Domain: wpu_better_rss
Domain Path: /lang
Requires at least: 6.2
Requires PHP: 8.0
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUBetterRSS {
    private $plugin_version = '0.1.0';
    private $plugin_settings = array(
        'id' => 'wpu_better_rss',
        'name' => 'WPU Better RSS'
    );
    private $basetoolbox;
    private $plugin_description;

    public function __construct() {
        add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
        add_action('plugins_loaded', array(&$this, 'rss_features'));
    }

    public function plugins_loaded() {
        # TRANSLATION
        if (!load_plugin_textdomain('wpu_better_rss', false, dirname(plugin_basename(__FILE__)) . '/lang/')) {
            load_muplugin_textdomain('wpu_better_rss', dirname(plugin_basename(__FILE__)) . '/lang/');
        }
        $this->plugin_description = __('Better RSS feeds', 'wpu_better_rss');
        # TOOLBOX
        require_once dirname(__FILE__) . '/inc/WPUBaseToolbox/WPUBaseToolbox.php';
        $this->basetoolbox = new \wpu_better_rss\WPUBaseToolbox();
    }

    /* ----------------------------------------------------------
      Features
    ---------------------------------------------------------- */

    function rss_features() {
        if (apply_filters('wpu_better_rss__display_thumbnail_before_content__enable', false)) {
            $use_excerpt = get_option('rss_use_excerpt');
            if ($use_excerpt) {
                add_filter('the_excerpt_rss', array(&$this, 'display_thumbnail_before_content'), 10, 1);
            } else {
                add_filter('the_content_feed', array(&$this, 'display_thumbnail_before_content'), 10, 1);
            }
        }
        if (apply_filters('wpu_better_rss__add_tracking_to_links__enable', false)) {
            add_filter('the_permalink_rss', array(&$this, 'add_tracking_to_links'));
        }
        if (apply_filters('wpu_better_rss__add_enclosure_field__enable', false)) {
            add_action('rss2_item', array(&$this, 'add_enclosure_field'));
        }
        if (apply_filters('wpu_better_rss__force_sitename_as_author__enable', false)) {
            add_filter('the_author', array(&$this, 'force_sitename_as_author'), 40);
        }
    }

    /* ----------------------------------------------------------
      Display thumb before content
    ---------------------------------------------------------- */

    function display_thumbnail_before_content($text) {
        add_filter('max_srcset_image_width', function () {
            return 1;
        });
        $thumb = '';
        if (get_post_type() == 'post') {
            $image = wp_get_attachment_image(get_post_thumbnail_id(get_the_ID()), 'medium');
            $thumb = wpautop($image);
        }
        return $thumb . $text;
    }

    /* ----------------------------------------------------------
      Add tracking to links
    ---------------------------------------------------------- */

    function add_tracking_to_links($permalink) {
        $utm_params = array(
            'utm_source' => 'rss',
            'utm_medium' => 'rss',
            'utm_campaign' => 'rss_feed_campaign'
        );
        return add_query_arg($utm_params, $permalink);
    }

    /* ----------------------------------------------------------
      Display enclosure field
      Thanks to https://github.com/kasparsd/feed-image-enclosure/blob/master/feed-image-enclosure.php
    ---------------------------------------------------------- */

    function add_enclosure_field() {
        if (!has_post_thumbnail()) {
            return;
        }

        $thumbnail_id = get_post_thumbnail_id(get_the_ID());
        $thumbnail = image_get_intermediate_size($thumbnail_id, 'medium');

        if (empty($thumbnail)) {
            return;
        }

        $filepath = get_attached_file($thumbnail_id);
        $filesize = 0;
        if (file_exists($filepath)) {
            $filesize = filesize($filepath);
        }

        printf(
            '<enclosure url="%s" length="%s" type="%s" />',
            $thumbnail['url'],
            $filesize,
            get_post_mime_type($thumbnail_id)
        );
    }

    /* ----------------------------------------------------------
      Force sitename as author
    ---------------------------------------------------------- */

    function force_sitename_as_author($display_name) {
        if (is_feed()) {
            return get_bloginfo('name');
        }
        return $display_name;
    }

}

$WPUBetterRSS = new WPUBetterRSS();
