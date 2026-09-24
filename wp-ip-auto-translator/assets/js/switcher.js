/**
 * 语言切换器交互与全局偏好存储
 */
(function() {
    'use strict';

    var data = window.wpTranslatorData || {};
    var rememberDays = data.rememberDays || 30;

    // 获取二级根域名，确保全站所有子域及 www / 非 www 完美共享 Cookie
    function getRootDomain() {
        var host = window.location.hostname;
        var parts = host.split('.');
        if (parts.length > 2 && !/^\d+\.\d+\.\d+\.\d+$/.test(host)) {
            return '; domain=.' + parts.slice(-2).join('.');
        }
        return '';
    }

    // Cookie 规范写入工具
    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        var domain = getRootDomain();
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/' + domain + '; SameSite=Lax';
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
    }

    function getCookie(name) {
        var nameEQ = name + '=';
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
        }
        return null;
    }

    // 初始化事件绑定
    document.addEventListener('DOMContentLoaded', function() {
        // 1. 浮动球模版：面板展开与收起
        var floatingBtn = document.getElementById('wpit-floating-trigger');
        var floatingPanel = document.getElementById('wpit-floating-panel');
        var floatingClose = document.getElementById('wpit-floating-close');

        if (floatingBtn && floatingPanel) {
            floatingBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                floatingPanel.classList.toggle('active');
            });

            if (floatingClose) {
                floatingClose.addEventListener('click', function(e) {
                    e.stopPropagation();
                    floatingPanel.classList.remove('active');
                });
            }

            document.addEventListener('click', function(e) {
                if (!floatingPanel.contains(e.target) && e.target !== floatingBtn) {
                    floatingPanel.classList.remove('active');
                }
            });
        }

        // 2. 下拉菜单模版：点击切换下拉展开
        var dropdownToggles = document.querySelectorAll('.wpit-dropdown-toggle');
        dropdownToggles.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var parent = btn.closest('.wpit-dropdown');
                if (parent) {
                    parent.classList.toggle('open');
                }
            });
        });

        document.addEventListener('click', function(e) {
            var openDropdowns = document.querySelectorAll('.wpit-dropdown.open');
            openDropdowns.forEach(function(dd) {
                if (!dd.contains(e.target)) {
                    dd.classList.remove('open');
                }
            });
        });

        // 3. 点击切换语言项触发逻辑
        document.addEventListener('click', function(e) {
            var choice = e.target.closest('.wpit-lang-choice');
            if (!choice) return;

            e.preventDefault();
            var targetLang = choice.getAttribute('data-lang');
            if (!targetLang) return;

            // 立即记录用户手动选择（Cookie + LocalStorage 双保险）
            setCookie('wp_translator_manual_lang', targetLang, rememberDays);
            try {
                localStorage.setItem('wp_translator_manual_lang', targetLang);
            } catch (err) {}

            // 调用 Google 翻译与 UI 全量同步驱动
            if (window.WpIpTranslator && typeof window.WpIpTranslator.changeLanguage === 'function') {
                window.WpIpTranslator.changeLanguage(targetLang);
            } else {
                location.reload();
            }

            // 关闭所有展开状态
            if (floatingPanel) floatingPanel.classList.remove('active');
            document.querySelectorAll('.wpit-dropdown.open').forEach(function(d) {
                d.classList.remove('open');
            });
        });

        // 4. 客户端异步探测 IP（当服务端没有命中国家代码且用户未手动选择时）
        var manualLang = getCookie('wp_translator_manual_lang');
        if (!manualLang && data.enableAutoIp && !data.visitorCountry) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', data.ajaxUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 400) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success && res.data && res.data.country) {
                            setCookie('wp_user_visitor_country', res.data.country, 7);
                        }
                    } catch (err) {}
                }
            };
            xhr.send('action=wp_translator_detect_ip&security=' + encodeURIComponent(data.nonce));
        }
    });
})();
