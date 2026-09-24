<?php
/**
 * Plugin Name: WordPress 智能 IP 多语言自动翻译器
 * Plugin URI: https://github.com/wuyou2001/wp-ip-auto-translator
 * Description: 智能 IP 访客国家识别与全站自动/手动多语言翻译器。支持 Cloudflare CDN 与全球 GeoIP 探测、Google 免费网页翻译引擎、多套开箱即用现代模版、自定义外观样式，并深度支持自建主题与 WooCommerce。
 * Version: 1.0.4
 * Author: Wwnine
 * Author URI: https://github.com/wuyou2001
 * Text Domain: wp-ip-auto-translator
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// 自动静默更新器集成
if (is_admin()) {
    require_once __DIR__ . '/includes/class-github-updater.php';
    new WP_GitHub_Plugin_Updater(__FILE__, '', 'wuyou2001/wp-ip-auto-translator');
}

// 加载核心组件类
require_once __DIR__ . '/includes/class-translator-geo.php';
require_once __DIR__ . '/includes/class-translator-settings.php';
require_once __DIR__ . '/includes/class-translator-frontend.php';

/**
 * 插件主调度单例
 */
class WP_IP_Auto_Translator {
    private static $instance = null;
    public $geo;
    public $settings;
    public $frontend;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->geo = new WP_Translator_Geo();
        $this->settings = new WP_Translator_Settings();
        $this->frontend = new WP_Translator_Frontend($this);
    }
}

// 实例化主插件
function wp_ip_auto_translator() {
    return WP_IP_Auto_Translator::get_instance();
}
add_action('plugins_loaded', 'wp_ip_auto_translator');

/**
 * 自建主题开发友好函数：输出语言切换器 HTML
 *
 * @param array $args 覆盖参数 (如 template => 'dropdown' | 'floating' | 'bar')
 * @param bool $echo 是否直接输出
 * @return string|void
 */
function wp_ip_translator_switcher($args = array(), $echo = true) {
    $translator = WP_IP_Auto_Translator::get_instance();
    if (!$translator || !$translator->frontend) {
        return '';
    }
    $html = $translator->frontend->render_switcher($args);
    if ($echo) {
        echo $html;
    } else {
        return $html;
    }
}

/**
 * 注册自建主题动作锚点: do_action('wp_ip_translator_switcher', $args);
 */
add_action('wp_ip_translator_switcher', 'wp_ip_translator_switcher', 10, 1);
