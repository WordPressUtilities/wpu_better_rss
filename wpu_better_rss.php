<?php
defined('ABSPATH') || die;
/*
Plugin Name: WPU Better RSS
Plugin URI: https://github.com/WordPressUtilities/wpu_better_rss
Update URI: https://github.com/WordPressUtilities/wpu_better_rss
Description: Better RSS feeds
Version: 0.3.0
Author: Darklg
Author URI: https://darklg.me/
Text Domain: wpu_better_rss
Domain Path: /lang
Requires at least: 6.2
Requires PHP: 8.0
Network: Optional
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUBetterRSS {
    private $plugin_version = '0.3.0';
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
        require_once __DIR__ . '/inc/WPUBaseToolbox/WPUBaseToolbox.php';
        $this->basetoolbox = new \wpu_better_rss\WPUBaseToolbox(array(
            'need_form_js' => false
        ));
    }

    /* ----------------------------------------------------------
      Features
    ---------------------------------------------------------- */

    function rss_features() {
        $hook_content = get_option('rss_use_excerpt') ? 'the_excerpt_rss' : 'the_content_feed';
        if (apply_filters('wpu_better_rss__display_thumbnail_before_content__enable', false)) {
            add_filter($hook_content, array(&$this, 'display_thumbnail_before_content'), 20, 1);
        }
        if (apply_filters('wpu_better_rss__add_enclosure_field__enable', false)) {
            add_action('rss2_item', array(&$this, 'add_enclosure_field'));
        }
        if (apply_filters('wpu_better_rss__force_sitename_as_author__enable', false)) {
            add_filter('the_author', array(&$this, 'force_sitename_as_author'), 40);
        }
        if (apply_filters('wpu_better_rss__add_copyright_in_feed__enable', false)) {
            add_filter($hook_content, array(&$this, 'add_copyright_in_feed'), 50, 1);
        }
        if (apply_filters('wpu_better_rss__add_tracking_to_links__enable', true)) {
            add_filter('the_permalink_rss', array(&$this, 'add_tracking_to_links'));
        }
    }

    /* ----------------------------------------------------------
      Display thumb before content
    ---------------------------------------------------------- */

    function display_thumbnail_before_content($text) {
        add_filter('max_srcset_image_width', function () {
            return 1;
        });
        $thumbnail = '';
        $thumbnail_size = apply_filters('wpu_better_rss__display_thumbnail_before_content__thumbnail_size', 'medium');
        if (get_post_type() == 'post') {
            $image = wp_get_attachment_image(get_post_thumbnail_id(get_the_ID()), $thumbnail_size);
            $thumbnail = wpautop($image);
        }
        return $thumbnail . $text;
    }

    /* ----------------------------------------------------------
      Add tracking to links
    ---------------------------------------------------------- */

    function add_tracking_to_links($permalink) {
        $utm_params = array();
        if (apply_filters('wpu_better_rss__add_tracking_to_links__enable', false)) {
            $utm_params = array(
                'utm_source' => 'rss',
                'utm_medium' => 'rss',
                'utm_campaign' => 'rss_feed_campaign'
            );
        }
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
        return is_feed() ? get_bloginfo('name') : $display_name;
    }

    /* ----------------------------------------------------------
      Add copyright in feed
    ---------------------------------------------------------- */

    function add_copyright_in_feed($content) {
        $copyright_before = apply_filters('wpu_better_rss__add_copyright_in_feed__before_content', '<hr /><p>');
        $copyright_after = apply_filters('wpu_better_rss__add_copyright_in_feed__after_content', '</p>');
        $copyright_parts = apply_filters('wpu_better_rss__add_copyright_in_feed__copyright_parts', array(
            'copy' => '&copy; ' . date('Y') . ' ' . get_bloginfo('name'),
            'title' => '<a href="' . $this->add_tracking_to_links(get_permalink()) . '">' . get_the_title() . '</a>'
        ));

        return $content . $copyright_before . implode(' - ', $copyright_parts) . $copyright_after;
    }

}

$WPUBetterRSS = new WPUBetterRSS();
