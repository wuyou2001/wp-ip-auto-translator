<?php
/**
 * 前端渲染、模板重写调度与脚本资产注入
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Translator_Frontend {

    private $main;

    public function __construct($main) {
        $this->main = $main;

        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_footer', array($this, 'auto_render_switcher'));
        add_shortcode('wp_translator_switcher', array($this, 'shortcode_switcher'));

        // 挂载至原生导航菜单
        add_filter('wp_nav_menu_items', array($this, 'filter_nav_menu_items'), 10, 2);
    }

    /**
     * 加载前端 CSS 与 JS
     */
    public function enqueue_frontend_assets() {
        $options = $this->main->settings->get_options();
        $plugin_url = plugin_dir_url(dirname(__FILE__));

        // 1. 注册与引入切换器核心 CSS
        wp_enqueue_style(
            'wp-ip-translator-switcher',
            $plugin_url . 'assets/css/switcher.css',
            array(),
            '1.0.1'
        );

        // 动态注入自定义样式变量 (CSS Variables) 与用户自定义 CSS
        $offset_x = isset($options['offset_x']) ? intval($options['offset_x']) : 25;
        $offset_y = isset($options['offset_y']) ? intval($options['offset_y']) : 25;

        $custom_css = "
        :root {
            --wpit-theme-color: " . esc_attr($options['theme_color']) . ";
            --wpit-bg-color: " . esc_attr($options['bg_color']) . ";
            --wpit-text-color: " . esc_attr($options['text_color']) . ";
            --wpit-radius: " . intval($options['border_radius']) . "px;
            --wpit-offset-x: " . $offset_x . "px;
            --wpit-offset-y: " . $offset_y . "px;
        }
        " . $options['custom_css'];

        wp_add_inline_style('wp-ip-translator-switcher', $custom_css);

        // 2. 引入切换器行为 JS
        wp_enqueue_script(
            'wp-ip-translator-switcher',
            $plugin_url . 'assets/js/switcher.js',
            array(),
            '1.0.1',
            true
        );

        // 3. 引入 Google 翻译客户端加载脚本
        wp_enqueue_script(
            'wp-ip-translator-google',
            $plugin_url . 'assets/js/google-translate-loader.js',
            array('wp-ip-translator-switcher'),
            '1.0.1',
            true
        );

        // 预判当前语言与 IP
        $current_lang = $this->get_current_active_language();

        // 传递数据到前端
        wp_localize_script('wp-ip-translator-switcher', 'wpTranslatorData', array(
            'ajaxUrl'           => admin_url('admin-ajax.php'),
            'nonce'             => wp_create_nonce('wp_translator_nonce'),
            'sourceLang'        => $options['source_language'],
            'currentLang'       => $current_lang,
            'enableAutoIp'      => (bool)$options['enable_auto_ip'],
            'rememberDays'      => intval($options['remember_days']),
            'availableLangs'    => $options['target_languages'],
            'visitorCountry'    => $this->main->geo->detect_visitor_country(),
        ));
    }

    /**
     * 判定当前生效的语言
     */
    public function get_current_active_language() {
        $options = $this->main->settings->get_options();
        $source_lang = $options['source_language'];

        // 1. 如果用户手动切换过 (检查 Cookie)
        if (!empty($_COOKIE['wp_translator_manual_lang'])) {
            $cookie_lang = sanitize_text_field($_COOKIE['wp_translator_manual_lang']);
            if (in_array($cookie_lang, $options['target_languages'], true)) {
                return $cookie_lang;
            }
        }

        // 2. 如果开启 IP 自动匹配，检测访客 IP 对应语言
        if (!empty($options['enable_auto_ip'])) {
            $country = $this->main->geo->detect_visitor_country();
            if (!empty($country)) {
                $matched_lang = $this->main->geo->get_language_by_country($country, $options['target_languages'], $source_lang);
                if (!empty($matched_lang)) {
                    return $matched_lang;
                }
            }
        }

        return $source_lang;
    }

    /**
     * 获取经过过滤的激活语言列表
     */
    public function get_active_languages() {
        $options = $this->main->settings->get_options();
        $pool = WP_Translator_Settings::$languages_pool;
        $targets = (array)$options['target_languages'];

        $languages = array();
        foreach ($targets as $code) {
            if (isset($pool[$code])) {
                $languages[$code] = $pool[$code];
            }
        }

        // 允许自建主题通过 Filter 动态过滤或重排序
        return apply_filters('wp_ip_translator_languages', $languages);
    }

    /**
     * 定位模板文件（优先检索自建主题目录）
     */
    public function locate_template($template_name) {
        $filename = 'switcher-' . $template_name . '.php';

        // 1. 查找子主题/父主题的 wp-ip-translator/ 目录
        $theme_file = locate_template(array(
            'wp-ip-translator/' . $filename,
            $filename
        ));

        if (!empty($theme_file)) {
            return $theme_file;
        }

        // 2. 回退到插件自带的 templates/ 目录
        $plugin_file = dirname(dirname(__FILE__)) . '/templates/' . $filename;
        if (file_exists($plugin_file)) {
            return $plugin_file;
        }

        // 默认保底悬浮球模版
        return dirname(dirname(__FILE__)) . '/templates/switcher-floating.php';
    }

    /**
     * 核心渲染函数
     */
    public function render_switcher($args = array()) {
        $options = $this->main->settings->get_options();
        $template = !empty($args['template']) ? sanitize_text_field($args['template']) : $options['switcher_template'];
        $position = !empty($args['position']) ? sanitize_text_field($args['position']) : $options['floating_position'];
        $custom_class = !empty($args['class']) ? sanitize_text_field($args['class']) : '';

        $languages = $this->get_active_languages();
        $current_lang = $this->get_current_active_language();
        $show_flag = !empty($options['show_flag']);
        $all_pool = WP_Translator_Settings::$languages_pool;

        $template_file = $this->locate_template($template);

        ob_start();
        include $template_file;
        return ob_get_clean();
    }

    /**
     * 自动挂载到 wp_footer（若是 auto 模式且为全站浮动/Bar）
     */
    public function auto_render_switcher() {
        $options = $this->main->settings->get_options();
        if ($options['placement_mode'] === 'auto') {
            echo $this->render_switcher(array(
                'template' => $options['switcher_template'],
                'position' => $options['floating_position'],
            ));
        }

        // 隐式注入 Google 翻译隐藏承载容器
        echo '<div id="google_translate_element" style="display:none !important; visibility:hidden !important; position:absolute; left:-9999px;"></div>';
    }

    /**
     * 短代码输出支持 [wp_translator_switcher]
     */
    public function shortcode_switcher($atts) {
        $args = shortcode_atts(array(
            'template' => '',
            'position' => '',
            'class'    => '',
        ), $atts, 'wp_translator_switcher');

        return $this->render_switcher($args);
    }

    /**
     * 追加到 WordPress 原生导航菜单
     */
    public function filter_nav_menu_items($items, $args) {
        $options = $this->main->settings->get_options();
        if (empty($options['append_to_menu'])) {
            return $items;
        }

        // 如果指定了特定菜单 ID 则过滤
        if (!empty($options['menu_id'])) {
            $menu_term_id = isset($args->menu->term_id) ? $args->menu->term_id : 0;
            if ($menu_term_id && intval($menu_term_id) !== intval($options['menu_id'])) {
                return $items;
            }
        }

        $dropdown_html = $this->render_switcher(array(
            'template' => 'dropdown',
            'class'    => 'wpit-menu-item-switcher'
        ));

        $menu_item = '<li class="menu-item menu-item-wpit-translator">' . $dropdown_html . '</li>';
        return $items . $menu_item;
    }
}
