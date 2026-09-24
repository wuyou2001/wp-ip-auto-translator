<?php
/**
 * 模版 1: 浮动悬浮球 (Floating Badge)
 * 可在自建主题目录 wp-content/themes/{theme}/wp-ip-translator/switcher-floating.php 中覆盖重写
 */

if (!defined('ABSPATH')) {
    exit;
}

$wrapper_class = 'wpit-floating-wrapper wpit-pos-' . esc_attr($position) . ' notranslate';
if (!empty($custom_class)) {
    $wrapper_class .= ' ' . esc_attr($custom_class);
}

$current_info = isset($languages[$current_lang]) ? $languages[$current_lang] : (isset($all_pool[$current_lang]) ? $all_pool[$current_lang] : array('name' => 'Language', 'native' => 'Language', 'flag' => '🌐'));
?>

<div class="<?php echo esc_attr($wrapper_class); ?>" id="wpit-floating-switcher" data-wpit-container translate="no">
    <!-- 悬浮触发圆形徽章 -->
    <button type="button" class="wpit-floating-btn notranslate" id="wpit-floating-trigger" aria-label="Select Language" title="<?php echo esc_attr($current_info['native']); ?>" translate="no">
        <?php if ($show_flag) : ?>
            <span class="wpit-btn-flag"><?php echo esc_html($current_info['flag']); ?></span>
        <?php else : ?>
            <span class="wpit-btn-icon">🌐</span>
        <?php endif; ?>
        <span class="wpit-btn-text notranslate" translate="no"><?php echo esc_html(strtoupper(explode('-', $current_lang)[0])); ?></span>
    </button>

    <!-- 弹出的语言选择浮层面板 -->
    <div class="wpit-floating-panel notranslate" id="wpit-floating-panel" translate="no">
        <div class="wpit-panel-header">
            <span class="wpit-panel-title">🌐 选择站点语言</span>
            <button type="button" class="wpit-panel-close" id="wpit-floating-close" aria-label="Close">&times;</button>
        </div>
        <div class="wpit-panel-body">
            <ul class="wpit-lang-list">
                <?php foreach ($languages as $code => $info) : ?>
                    <?php $is_active = ($code === $current_lang); ?>
                    <li class="wpit-lang-item <?php echo $is_active ? 'active' : ''; ?>">
                        <a href="javascript:void(0);" class="wpit-lang-choice notranslate" data-lang="<?php echo esc_attr($code); ?>" translate="no">
                            <?php if ($show_flag) : ?>
                                <span class="wpit-item-flag"><?php echo esc_html($info['flag']); ?></span>
                            <?php endif; ?>
                            <span class="wpit-item-native"><?php echo esc_html($info['native']); ?></span>
                            <span class="wpit-item-name"><?php echo esc_html($info['name']); ?></span>
                            <!-- 仅受控于 active class 显隐 -->
                            <span class="wpit-item-check">✓</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
