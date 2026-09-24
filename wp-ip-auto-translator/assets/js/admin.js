/**
 * Modern Interactive Admin Controls & Live Preview
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // 1. 选项卡平滑切换
        $('.wpit-tab-btn').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('data-tab');

            $('.wpit-tab-btn').removeClass('active');
            $(this).addClass('active');

            $('.wpit-tab-panel').removeClass('active');
            $('#' + target).addClass('active');
        });

        // 2. 模版单选卡片高亮联动
        $('.wpit-tmpl-card input[type="radio"]').on('change', function() {
            $('.wpit-tmpl-card').removeClass('active');
            $(this).closest('.wpit-tmpl-card').addClass('active');
            updateLivePreview();
        });

        // 3. 颜色选择器与文本框同步
        $('.wpit-color-control input[type="color"]').on('input change', function() {
            var val = $(this).val();
            $(this).siblings('input[type="text"]').val(val);
            updateLivePreview();
        });

        $('.wpit-color-control input[type="text"]').on('input change', function() {
            var val = $(this).val();
            if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                $(this).siblings('input[type="color"]').val(val);
                updateLivePreview();
            }
        });

        // 4. 圆角与国旗开关变动时同步实时预览
        $('input[name*="[border_radius]"], input[name*="[show_flag]"]').on('input change', function() {
            updateLivePreview();
        });

        // 5. 模式选择联动
        $('input[name*="[placement_mode]"]').on('change', function() {
            if ($(this).val() === 'auto') {
                $('.wpit-floating-opt').slideDown(150);
            } else {
                $('.wpit-floating-opt').slideUp(150);
            }
        }).trigger('change');

        // 6. 实时预览更新逻辑
        function updateLivePreview() {
            var tmpl = $('input[name*="[switcher_template]"]:checked').val() || 'floating';
            var themeColor = $('input[name*="[theme_color]"]').val() || '#2563eb';
            var bgColor = $('input[name*="[bg_color]"]').val() || '#ffffff';
            var textColor = $('input[name*="[text_color]"]').val() || '#1e293b';
            var radius = $('input[name*="[border_radius]"]').val() || 10;
            var showFlag = $('input[name*="[show_flag]"]').is(':checked');

            var flagHtml = showFlag ? '🇺🇸 ' : '';
            var $stage = $('#wpit-preview-stage');
            $stage.empty();

            if (tmpl === 'floating') {
                var $btn = $('<div style="display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border:1px solid rgba(0,0,0,0.1); cursor:pointer; font-weight:600; font-size:14px; box-shadow:0 4px 14px rgba(0,0,0,0.12); transition:all 0.2s;">' +
                    flagHtml + 'EN ▾</div>');
                $btn.css({
                    'background-color': bgColor,
                    'color': textColor,
                    'border-radius': radius + 'px'
                });
                $stage.append($btn);
            } else if (tmpl === 'dropdown') {
                var $select = $('<div style="display:inline-flex; align-items:center; gap:8px; padding:7px 14px; border:1px solid #cbd5e1; font-size:13px; font-weight:500; cursor:pointer; box-shadow:0 1px 3px rgba(0,0,0,0.05);">' +
                    flagHtml + 'English ▾</div>');
                $select.css({
                    'background-color': bgColor,
                    'color': textColor,
                    'border-radius': radius + 'px'
                });
                $stage.append($select);
            } else if (tmpl === 'bar') {
                var $bar = $('<div style="display:flex; align-items:center; gap:8px; padding:8px 16px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0; width:100%; max-width:420px; overflow:hidden;">' +
                    '<span style="font-size:12px; font-weight:600; color:#64748b;">🌐 Language:</span>' +
                    '<span style="padding:3px 10px; font-size:12px; font-weight:600; border-radius:' + radius + 'px; background:' + themeColor + '; color:#fff;">' + flagHtml + 'English</span>' +
                    '<span style="padding:3px 10px; font-size:12px; color:#475569; background:#e2e8f0; border-radius:' + radius + 'px;">' + (showFlag ? '🇨🇳 ' : '') + '简体中文</span>' +
                    '</div>');
                $stage.append($bar);
            }
        }

        // 初始化运行一次预览
        updateLivePreview();
    });
})(jQuery);
