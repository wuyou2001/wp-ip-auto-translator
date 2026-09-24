<?php
/**
 * 模版 3: 悬浮横条栏 (Bar)
 * 页面底部或顶部的全宽/悬浮胶囊横条，极其适合移动端和自建主题底部导航
 * 可在自建主题目录 wp-content/themes/{theme}/wp-ip-translator/switcher-bar.php 中覆盖重写
 */

if (!defined('ABSPATH')) {
    exit;
}

$wrapper_class = 'wpit-bar-wrapper wpit-pos-' . esc_attr($position);
if (!empty($custom_class)) {
    $wrapper_class .= ' ' . esc_attr($custom_class);
}

$current_info = isset($languages[$current_lang]) ? $languages[$current_lang] : (isset($all_pool[$current_lang]) ? $all_pool[$current_lang] : array('name' => 'Language', 'native' => 'Language', 'flag' => '🌐'));
?>

<div class="<?php echo esc_attr($wrapper_class); ?>" id="wpit-bar-switcher" data-wpit-container>
    <div class="wpit-bar-container">
        <div class="wpit-bar-label">
            <span class="wpit-bar-icon">🌐</span>
            <span class="wpit-bar-text">Language:</span>
        </div>
        <div class="wpit-bar-scroll">
            <div class="wpit-bar-items">
                <?php foreach ($languages as $code => $info) : ?>
                    <?php $is_active = ($code === $current_lang); ?>
                    <a href="javascript:void(0);" class="wpit-bar-item wpit-lang-choice <?php echo $is_active ? 'active' : ''; ?>" data-lang="<?php echo esc_attr($code); ?>" title="<?php echo esc_attr($info['name']); ?>">
                        <?php if ($show_flag) : ?>
                            <span class="wpit-bar-flag"><?php echo esc_html($info['flag']); ?></span>
                        <?php endif; ?>
                        <span class="wpit-bar-name"><?php echo esc_html($info['native']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
