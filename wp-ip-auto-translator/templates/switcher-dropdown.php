<?php
/**
 * 模版 2: 紧凑下拉菜单 (Compact Dropdown)
 * 适用于自建主题导航条、页眉、页脚及原生菜单嵌入
 * 可在自建主题目录 wp-content/themes/{theme}/wp-ip-translator/switcher-dropdown.php 中覆盖重写
 */

if (!defined('ABSPATH')) {
    exit;
}

$wrapper_class = 'wpit-dropdown-wrapper';
if (!empty($custom_class)) {
    $wrapper_class .= ' ' . esc_attr($custom_class);
}

$current_info = isset($languages[$current_lang]) ? $languages[$current_lang] : (isset($all_pool[$current_lang]) ? $all_pool[$current_lang] : array('name' => 'Language', 'native' => 'Language', 'flag' => '🌐'));
?>

<div class="<?php echo esc_attr($wrapper_class); ?>" data-wpit-container>
    <div class="wpit-dropdown">
        <button type="button" class="wpit-dropdown-toggle" aria-haspopup="true" aria-expanded="false">
            <?php if ($show_flag) : ?>
                <span class="wpit-drop-flag"><?php echo esc_html($current_info['flag']); ?></span>
            <?php else : ?>
                <span class="wpit-drop-icon">🌐</span>
            <?php endif; ?>
            <span class="wpit-drop-label"><?php echo esc_html($current_info['native']); ?></span>
            <span class="wpit-drop-arrow">▾</span>
        </button>
        <div class="wpit-dropdown-menu">
            <ul class="wpit-dropdown-list">
                <?php foreach ($languages as $code => $info) : ?>
                    <?php $is_active = ($code === $current_lang); ?>
                    <li class="wpit-drop-item <?php echo $is_active ? 'active' : ''; ?>">
                        <a href="javascript:void(0);" class="wpit-lang-choice" data-lang="<?php echo esc_attr($code); ?>">
                            <?php if ($show_flag) : ?>
                                <span class="wpit-drop-item-flag"><?php echo esc_html($info['flag']); ?></span>
                            <?php endif; ?>
                            <span class="wpit-drop-item-name"><?php echo esc_html($info['native']); ?></span>
                            <?php if ($is_active) : ?>
                                <span class="wpit-drop-item-check">✓</span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
