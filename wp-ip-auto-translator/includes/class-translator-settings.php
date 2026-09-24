<?php
/**
 * 插件设置管理与高定版现代化后台面板
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Translator_Settings {

    private $option_name = 'wp_ip_translator_options';
    private $options = null;

    /**
     * 系统支持的全球主要语言库 (代码 => [名称, 原生名, 对应国家旗帜 emoji])
     */
    public static $languages_pool = array(
        'zh-CN' => array('name' => 'Chinese (Simplified)', 'native' => '简体中文', 'flag' => '🇨🇳'),
        'zh-TW' => array('name' => 'Chinese (Traditional)', 'native' => '繁體中文', 'flag' => '🇭🇰'),
        'en'    => array('name' => 'English', 'native' => 'English', 'flag' => '🇺🇸'),
        'ja'    => array('name' => 'Japanese', 'native' => '日本語', 'flag' => '🇯🇵'),
        'ko'    => array('name' => 'Korean', 'native' => '한국어', 'flag' => '🇰🇷'),
        'es'    => array('name' => 'Spanish', 'native' => 'Español', 'flag' => '🇪🇸'),
        'fr'    => array('name' => 'French', 'native' => 'Français', 'flag' => '🇫🇷'),
        'de'    => array('name' => 'German', 'native' => 'Deutsch', 'flag' => '🇩🇪'),
        'it'    => array('name' => 'Italian', 'native' => 'Italiano', 'flag' => '🇮🇹'),
        'pt'    => array('name' => 'Portuguese', 'native' => 'Português', 'flag' => '🇧🇷'),
        'ru'    => array('name' => 'Russian', 'native' => 'Русский', 'flag' => '🇷🇺'),
        'ar'    => array('name' => 'Arabic', 'native' => 'العربية', 'flag' => '🇸🇦'),
        'vi'    => array('name' => 'Vietnamese', 'native' => 'Tiếng Việt', 'flag' => '🇻🇳'),
        'th'    => array('name' => 'Thai', 'native' => 'ไทย', 'flag' => '🇹🇭'),
        'id'    => array('name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'flag' => '🇮🇩'),
        'nl'    => array('name' => 'Dutch', 'native' => 'Nederlands', 'flag' => '🇳🇱'),
        'pl'    => array('name' => 'Polish', 'native' => 'Polski', 'flag' => '🇵🇱'),
        'tr'    => array('name' => 'Turkish', 'native' => 'Türkçe', 'flag' => '🇹🇷'),
    );

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // 插件列表中增加快速设置直达链接
        add_filter('plugin_action_links_' . plugin_basename(dirname(dirname(__FILE__)) . '/wp-ip-auto-translator.php'), array($this, 'add_action_links'));
    }

    /**
     * 快速设置链接
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=wp-ip-auto-translator')) . '">⚙️ 翻译器设置</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * 后台资源加载
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wp-ip-auto-translator') === false) {
            return;
        }

        $plugin_url = plugin_dir_url(dirname(__FILE__));
        wp_enqueue_style(
            'wpit-admin-style',
            $plugin_url . 'assets/css/admin.css',
            array(),
            '1.0.1'
        );

        wp_enqueue_script(
            'wpit-admin-script',
            $plugin_url . 'assets/js/admin.js',
            array('jquery'),
            '1.0.1',
            true
        );
    }

    /**
     * 获取全部设置项（自动填充默认值）
     */
    public function get_options() {
        if ($this->options !== null) {
            return $this->options;
        }

        $defaults = array(
            'enable_auto_ip'      => 1,           // 是否开启基于 IP 自动检测并切换
            'source_language'     => 'zh-CN',     // 站点原始语言
            'target_languages'    => array('zh-CN', 'zh-TW', 'en', 'ja', 'ko', 'es', 'fr', 'de'),
            'switcher_template'   => 'floating',  // floating (悬浮球) | dropdown (下拉菜单) | bar (悬浮条)
            'placement_mode'      => 'auto',      // auto (自动插入前端) | manual (自建主题 hook/短代码/函数)
            'floating_position'   => 'bottom-right', // bottom-right | bottom-left | top-right | top-left
            'offset_x'            => 25,          // 边距：水平边距 (px)
            'offset_y'            => 25,          // 边距：垂直边距 (px)
            'append_to_menu'      => 0,           // 是否追加到主导航菜单
            'menu_id'             => '',          // 指定的导航菜单 ID
            'theme_color'         => '#2563eb',   // 主题强调色
            'bg_color'            => '#ffffff',   // 切换器背景色
            'text_color'          => '#1e293b',   // 文本色
            'border_radius'       => '10',        // 圆角 (px)
            'show_flag'           => 1,           // 是否显示国旗 Emoji
            'remember_days'       => 30,          // 用户手动选择后的 Cookie 记忆天数
            'custom_css'          => '',          // 自定义 CSS 样式
        );

        $saved = get_option($this->option_name, array());
        $this->options = wp_parse_args($saved, $defaults);

        return $this->options;
    }

    /**
     * 注册后台管理菜单
     */
    public function add_admin_menu() {
        add_options_page(
            '智能 IP 多语言翻译器设置',
            'IP 翻译器',
            'manage_options',
            'wp-ip-auto-translator',
            array($this, 'render_settings_page')
        );
    }

    /**
     * 注册设置白名单
     */
    public function register_settings() {
        register_setting($this->option_name, $this->option_name, array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));
    }

    /**
     * 校验并安全清洗配置数据
     */
    public function sanitize_options($input) {
        $sanitized = array();
        $sanitized['enable_auto_ip'] = !empty($input['enable_auto_ip']) ? 1 : 0;
        $sanitized['source_language'] = !empty($input['source_language']) ? sanitize_text_field($input['source_language']) : 'zh-CN';

        if (!empty($input['target_languages']) && is_array($input['target_languages'])) {
            $sanitized['target_languages'] = array_map('sanitize_text_field', $input['target_languages']);
        } else {
            $sanitized['target_languages'] = array('en', 'zh-CN');
        }

        // 确保源语言包含在目标语言池中
        if (!in_array($sanitized['source_language'], $sanitized['target_languages'], true)) {
            array_unshift($sanitized['target_languages'], $sanitized['source_language']);
        }

        $sanitized['switcher_template'] = in_array($input['switcher_template'], array('floating', 'dropdown', 'bar'), true) ? $input['switcher_template'] : 'floating';
        $sanitized['placement_mode']    = in_array($input['placement_mode'], array('auto', 'manual'), true) ? $input['placement_mode'] : 'auto';
        $sanitized['floating_position'] = in_array($input['floating_position'], array('bottom-right', 'bottom-left', 'top-right', 'top-left'), true) ? $input['floating_position'] : 'bottom-right';

        $sanitized['offset_x']          = isset($input['offset_x']) ? absint($input['offset_x']) : 25;
        $sanitized['offset_y']          = isset($input['offset_y']) ? absint($input['offset_y']) : 25;

        $sanitized['append_to_menu']    = !empty($input['append_to_menu']) ? 1 : 0;
        $sanitized['menu_id']           = sanitize_text_field($input['menu_id'] ?? '');

        $sanitized['theme_color']       = sanitize_hex_color($input['theme_color'] ?? '#2563eb') ?: '#2563eb';
        $sanitized['bg_color']          = sanitize_hex_color($input['bg_color'] ?? '#ffffff') ?: '#ffffff';
        $sanitized['text_color']        = sanitize_hex_color($input['text_color'] ?? '#1e293b') ?: '#1e293b';
        $sanitized['border_radius']     = absint($input['border_radius'] ?? 10);
        $sanitized['show_flag']         = !empty($input['show_flag']) ? 1 : 0;
        $sanitized['remember_days']     = max(1, absint($input['remember_days'] ?? 30));
        $sanitized['custom_css']        = wp_strip_all_tags($input['custom_css'] ?? '');

        return $sanitized;
    }

    /**
     * 渲染后台高定版视觉页面
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $options = $this->get_options();
        $nav_menus = wp_get_nav_menus();
        ?>
        <div class="wrap wp-ip-translator-admin">
            
            <!-- 头部品牌面板 -->
            <div class="wpit-header-card">
                <div class="wpit-header-left">
                    <h1>🌐 WordPress 智能 IP 多语言自动翻译器</h1>
                    <p>全球访客 IP 自动匹配切换目标语言，零门槛多套精美切换模版，深度支持自建主题与 WooCommerce 电商架构。</p>
                </div>
                <div class="wpit-badges-group">
                    <span class="wpit-badge wpit-badge-status">● 翻译引擎已就绪</span>
                    <span class="wpit-badge wpit-badge-version">v1.0.1</span>
                </div>
            </div>

            <!-- 实时交互预览视窗 (Live Preview Stage) -->
            <div class="wpit-preview-container">
                <div class="wpit-preview-top">
                    <div class="wpit-preview-title">
                        <span>👁️ 前端切换器实时样式预览</span>
                    </div>
                    <span class="wpit-preview-tip">修改下方模版与配色时即时联动更新</span>
                </div>
                <div class="wpit-preview-stage" id="wpit-preview-stage">
                    <!-- 由 JavaScript 动态同步注入 -->
                </div>
            </div>

            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php settings_fields($this->option_name); ?>

                <!-- 现代选项卡标签栏 -->
                <div class="wpit-tabs-nav">
                    <button type="button" class="wpit-tab-btn active" data-tab="tab-general">
                        ⚙️ 核心翻译与 IP 设置
                    </button>
                    <button type="button" class="wpit-tab-btn" data-tab="tab-appearance">
                        🎨 模版外观与样式定制
                    </button>
                    <button type="button" class="wpit-tab-btn" data-tab="tab-theme">
                        💻 自建主题与集成指南
                    </button>
                </div>

                <!-- 1. 核心翻译与 IP 设置面板 -->
                <div id="tab-general" class="wpit-tab-panel active">
                    <div class="wpit-card">
                        <h3 class="wpit-card-title">🌍 访客定位与智能切换</h3>
                        <p class="wpit-card-desc">配置访客首次访问站点时的 IP 国家检测逻辑与默认语言映射策略。</p>

                        <div class="wpit-field-row">
                            <div class="wpit-field-label">IP 智能自动切换</div>
                            <div class="wpit-field-content">
                                <label class="wpit-switch-label">
                                    <div class="wpit-switch">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[enable_auto_ip]" value="1" <?php checked(1, $options['enable_auto_ip']); ?> />
                                        <span class="wpit-slider"></span>
                                    </div>
                                    <span class="wpit-switch-text">开启基于 IP 地理位置的自动识别与切换</span>
                                </label>
                                <div class="wpit-field-help">优先直读 Cloudflare `CF-IPCountry` 请求头，无 CDN 时回退免费的高速 GeoIP 服务，并建立 7 天内存缓存。</div>
                            </div>
                        </div>

                        <div class="wpit-field-row">
                            <div class="wpit-field-label">站点原始语言</div>
                            <div class="wpit-field-content">
                                <select name="<?php echo esc_attr($this->option_name); ?>[source_language]" style="min-width:240px; padding:6px 10px; border-radius:6px;">
                                    <?php foreach (self::$languages_pool as $code => $info) : ?>
                                        <option value="<?php echo esc_attr($code); ?>" <?php selected($code, $options['source_language']); ?>>
                                            <?php echo esc_html($info['flag'] . ' ' . $info['native'] . ' (' . $info['name'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="wpit-field-help">站点的基础创作语言。当切换回该语言时将完全还原原始 DOM，不走翻译通道。</div>
                            </div>
                        </div>

                        <div class="wpit-field-row">
                            <div class="wpit-field-label">激活的目标语言池</div>
                            <div class="wpit-field-content">
                                <div class="wpit-lang-grid">
                                    <?php foreach (self::$languages_pool as $code => $info) : ?>
                                        <label class="wpit-lang-item">
                                            <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[target_languages][]" value="<?php echo esc_attr($code); ?>" <?php checked(in_array($code, (array)$options['target_languages'], true)); ?> />
                                            <span class="wpit-flag"><?php echo esc_html($info['flag']); ?></span>
                                            <span class="wpit-lang-name"><?php echo esc_html($info['native']); ?></span>
                                            <small><?php echo esc_html($code); ?></small>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="wpit-field-help">勾选的目标语言将呈现在前端切换菜单中，并接受 IP 自动匹配。</div>
                            </div>
                        </div>

                        <div class="wpit-field-row">
                            <div class="wpit-field-label">手动偏好记忆周期</div>
                            <div class="wpit-field-content">
                                <input type="number" name="<?php echo esc_attr($this->option_name); ?>[remember_days]" value="<?php echo esc_attr($options['remember_days']); ?>" min="1" max="365" style="width:90px; padding:6px; border-radius:6px;" /> 天
                                <div class="wpit-field-help">当访客主动点击切换语言后，在设定期限内维持用户选择，绝不重复被 IP 自动重置。</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. 模版外观与样式面板 -->
                <div id="tab-appearance" class="wpit-tab-panel">
                    <div class="wpit-card">
                        <h3 class="wpit-card-title">✨ 切换器默认模版</h3>
                        <p class="wpit-card-desc">从内置的 3 款专业现代模版中挑选最适合您站点视觉风格的展现形式。</p>

                        <div class="wpit-template-selector">
                            <label class="wpit-tmpl-card <?php echo $options['switcher_template'] === 'floating' ? 'active' : ''; ?>">
                                <input type="radio" name="<?php echo esc_attr($this->option_name); ?>[switcher_template]" value="floating" <?php checked('floating', $options['switcher_template']); ?> />
                                <div class="tmpl-preview">
                                    <div class="circle-badge">🌐 EN</div>
                                </div>
                                <strong>浮动悬浮球 (推荐)</strong>
                                <span class="tmpl-desc">右下/左下紧凑按钮，点击平滑展开弹出卡片面板，全平台自适应。</span>
                            </label>

                            <label class="wpit-tmpl-card <?php echo $options['switcher_template'] === 'dropdown' ? 'active' : ''; ?>">
                                <input type="radio" name="<?php echo esc_attr($this->option_name); ?>[switcher_template]" value="dropdown" <?php checked('dropdown', $options['switcher_template']); ?> />
                                <div class="tmpl-preview">
                                    <div class="select-box">🇺🇸 English ▾</div>
                                </div>
                                <strong>紧凑下拉菜单</strong>
                                <span class="tmpl-desc">适合直接置入自建主题 Header、导航栏、WooCommerce 结账栏或页脚。</span>
                            </label>

                            <label class="wpit-tmpl-card <?php echo $options['switcher_template'] === 'bar' ? 'active' : ''; ?>">
                                <input type="radio" name="<?php echo esc_attr($this->option_name); ?>[switcher_template]" value="bar" <?php checked('bar', $options['switcher_template']); ?> />
                                <div class="tmpl-preview">
                                    <div class="bar-line">🌐 选择语言：[中] [EN] [日本語]</div>
                                </div>
                                <strong>悬浮横条栏 (Bar)</strong>
                                <span class="tmpl-desc">屏幕顶部或底部的全宽横向平铺栏条，在移动端具备绝佳的触控体验。</span>
                            </label>
                        </div>
                    </div>

                    <div class="wpit-card">
                        <h3 class="wpit-card-title">📐 挂载与边距定位</h3>
                        <p class="wpit-card-desc">定义语言切换器在前端页面的呈现方式、停靠方位及屏幕边缘距离。</p>

                        <div class="wpit-field-row">
                            <div class="wpit-field-label">前端挂载模式</div>
                            <div class="wpit-field-content">
                                <label style="margin-right:24px; font-weight:500;">
                                    <input type="radio" name="<?php echo esc_attr($this->option_name); ?>[placement_mode]" value="auto" <?php checked('auto', $options['placement_mode']); ?> />
                                    全站全局自动浮动（开箱即用，无需任何代码改动）
                                </label>
                                <label style="font-weight:500;">
                                    <input type="radio" name="<?php echo esc_attr($this->option_name); ?>[placement_mode]" value="manual" <?php checked('manual', $options['placement_mode']); ?> />
                                    完全手工精准挂载（仅由自建主题 PHP 钩子、函数或短代码输出）
                                </label>
                            </div>
                        </div>

                        <div class="wpit-field-row wpit-floating-opt">
                            <div class="wpit-field-label">浮动方位定位</div>
                            <div class="wpit-field-content">
                                <select name="<?php echo esc_attr($this->option_name); ?>[floating_position]" style="min-width:200px; padding:6px 10px; border-radius:6px;">
                                    <option value="bottom-right" <?php selected('bottom-right', $options['floating_position']); ?>>右下角 (默认推荐)</option>
                                    <option value="bottom-left" <?php selected('bottom-left', $options['floating_position']); ?>>左下角</option>
                                    <option value="top-right" <?php selected('top-right', $options['floating_position']); ?>>右上角</option>
                                    <option value="top-left" <?php selected('top-left', $options['floating_position']); ?>>左上角</option>
                                </select>
                            </div>
                        </div>

                        <div class="wpit-field-row wpit-floating-opt">
                            <div class="wpit-field-label">屏幕边缘边距</div>
                            <div class="wpit-field-content">
                                <div style="display:flex; align-items:center; gap:20px;">
                                    <label style="display:flex; align-items:center; gap:8px; font-weight:500;">
                                        水平边距 (X轴):
                                        <input type="number" name="<?php echo esc_attr($this->option_name); ?>[offset_x]" value="<?php echo esc_attr($options['offset_x']); ?>" min="0" max="300" style="width:75px; padding:6px; border-radius:6px;" /> px
                                    </label>
                                    <label style="display:flex; align-items:center; gap:8px; font-weight:500;">
                                        垂直边距 (Y轴):
                                        <input type="number" name="<?php echo esc_attr($this->option_name); ?>[offset_y]" value="<?php echo esc_attr($options['offset_y']); ?>" min="0" max="300" style="width:75px; padding:6px; border-radius:6px;" /> px
                                    </label>
                                </div>
                                <div class="wpit-field-help">控制悬浮按钮距离屏幕左右边缘及上下边缘的像素间距，避免遮挡右下角在线客服、回到顶部或 WhatsApp 挂件。</div>
                            </div>
                        </div>

                        <div class="wpit-field-row">
                            <div class="wpit-field-label">注入到 WP 导航菜单</div>
                            <div class="wpit-field-content">
                                <label class="wpit-switch-label">
                                    <div class="wpit-switch">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[append_to_menu]" value="1" <?php checked(1, $options['append_to_menu']); ?> />
                                        <span class="wpit-slider"></span>
                                    </div>
                                    <span class="wpit-switch-text">在自建主题的原生菜单项尾部追加语言下拉项</span>
                                </label>

                                <?php if (!empty($nav_menus)) : ?>
                                    <div style="margin-top:10px;">
                                        <select name="<?php echo esc_attr($this->option_name); ?>[menu_id]" style="min-width:260px; padding:6px 10px; border-radius:6px;">
                                            <option value="">-- 选择目标导航菜单 (留空则追加至第一个生效菜单) --</option>
                                            <?php foreach ($nav_menus as $menu) : ?>
                                                <option value="<?php echo esc_attr($menu->term_id); ?>" <?php selected($menu->term_id, $options['menu_id']); ?>>
                                                    <?php echo esc_html($menu->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="wpit-card">
                        <h3 class="wpit-card-title">🎨 配色与视觉细节</h3>
                        <p class="wpit-card-desc">无需写任何 CSS，即可直接调配完全契合您企业 VI 与主题风格的色彩。</p>

                        <div class="wpit-colors-grid">
                            <div class="wpit-color-box">
                                <span>主题强调色</span>
                                <div class="wpit-color-control">
                                    <input type="color" name="<?php echo esc_attr($this->option_name); ?>[theme_color]" value="<?php echo esc_attr($options['theme_color']); ?>" />
                                    <input type="text" value="<?php echo esc_attr($options['theme_color']); ?>" />
                                </div>
                            </div>

                            <div class="wpit-color-box">
                                <span>背景底色</span>
                                <div class="wpit-color-control">
                                    <input type="color" name="<?php echo esc_attr($this->option_name); ?>[bg_color]" value="<?php echo esc_attr($options['bg_color']); ?>" />
                                    <input type="text" value="<?php echo esc_attr($options['bg_color']); ?>" />
                                </div>
                            </div>

                            <div class="wpit-color-box">
                                <span>文字颜色</span>
                                <div class="wpit-color-control">
                                    <input type="color" name="<?php echo esc_attr($this->option_name); ?>[text_color]" value="<?php echo esc_attr($options['text_color']); ?>" />
                                    <input type="text" value="<?php echo esc_attr($options['text_color']); ?>" />
                                </div>
                            </div>

                            <div class="wpit-color-box">
                                <span>边框圆角 (px)</span>
                                <div class="wpit-color-control">
                                    <input type="number" name="<?php echo esc_attr($this->option_name); ?>[border_radius]" value="<?php echo esc_attr($options['border_radius']); ?>" min="0" max="60" style="width:80px; padding:6px; border-radius:6px;" />
                                </div>
                            </div>

                            <div class="wpit-color-box" style="justify-content:center;">
                                <span>国旗标识</span>
                                <label style="display:flex; align-items:center; gap:6px; margin-top:6px; font-weight:500;">
                                    <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[show_flag]" value="1" <?php checked(1, $options['show_flag']); ?> />
                                    显示对应国家 Emoji 国旗
                                </label>
                            </div>
                        </div>

                        <div class="wpit-field-row" style="margin-top:20px;">
                            <div class="wpit-field-label">自定义 CSS 代码</div>
                            <div class="wpit-field-content">
                                <textarea name="<?php echo esc_attr($this->option_name); ?>[custom_css]" rows="5" class="large-text code" style="border-radius:8px; font-family:monospace; font-size:12.5px;" placeholder="/* 在此注入您的自定义 CSS 规则，优先级极高 */"><?php echo esc_textarea($options['custom_css']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. 自建主题开发集成面板 -->
                <div id="tab-theme" class="wpit-tab-panel">
                    <div class="wpit-card">
                        <h3 class="wpit-card-title">🛠️ 自建主题开发者无缝对接规范 (Custom Theme Architecture)</h3>
                        <p class="wpit-card-desc">插件完全遵循 WooCommerce 与 WordPress 顶级工业级规范设计，自建主题拥有最高级别的重写控制权。</p>

                        <div class="wpit-theme-guide">
                            <div class="wpit-guide-card">
                                <h4>1. 零侵入模板重写 (Template Override)</h4>
                                <p>在您的自建主题或子主题目录下创建目录 <code>wp-ip-translator/</code>，将插件 <code>templates/</code> 目录中的同名文件拷贝进去，即可完全接管前端 DOM 渲染，插件更新时不受任何影响：</p>
                                <div class="wpit-code-box">
themes/your-custom-theme/wp-ip-translator/switcher-floating.php  // 覆盖悬浮球模版
themes/your-custom-theme/wp-ip-translator/switcher-dropdown.php  // 覆盖下拉菜单模版
themes/your-custom-theme/wp-ip-translator/switcher-bar.php       // 覆盖底部横条模版
                                </div>
                            </div>

                            <div class="wpit-guide-card">
                                <h4>2. 在自建主题 PHP 模板中直接调用</h4>
                                <p>适合将语言切换组件精准放置于自定义 Header 导航栏、Topbar、WooCommerce Mini-cart 侧滑栏或 Footer 中：</p>
                                <div class="wpit-code-box">
&lt;?php
// 直接输出默认设置的语言切换器
wp_ip_translator_switcher();

// 强制指定渲染下拉模式，并追加自定义 CSS 类名
wp_ip_translator_switcher(array(
    'template' => 'dropdown',
    'class'    => 'my-header-custom-switcher'
));
?&gt;
                                </div>
                            </div>

                            <div class="wpit-guide-card">
                                <h4>3. 动作锚点钩子集成 (Action Hooks)</h4>
                                <p>如果您的主题支持 Hook 解耦架构，可在任意钩子位置监听挂载：</p>
                                <div class="wpit-code-box">
&lt;?php
// 在主题 header.php 相应位置触发
do_action('wp_ip_translator_switcher');
?&gt;
                                </div>
                            </div>

                            <div class="wpit-guide-card">
                                <h4>4. 页面编辑器与短代码 (Shortcode)</h4>
                                <p>在 Gutenberg 区块、Elementor 小部件或富文本中插入以下短代码即可渲染：</p>
                                <div class="wpit-code-box">
[wp_translator_switcher]
[wp_translator_switcher template="dropdown"]
[wp_translator_switcher template="bar"]
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 底部保存条 -->
                <div class="wpit-save-footer">
                    <?php submit_button('保存所有配置更改', 'primary wpit-save-btn', 'submit', false); ?>
                </div>
            </form>
        </div>
        <?php
    }
}
