/**
 * Google 网页翻译客户端静默加载与调度驱动
 */
(function() {
    'use strict';

    var data = window.wpTranslatorData || {};
    var sourceLang = data.sourceLang || 'zh-CN';
    var currentLang = data.currentLang || sourceLang;

    // 格式化语言代码给 Google 翻译库 (如 zh-CN 保持或转换)
    function normalizeGoogleLang(lang) {
        if (!lang) return 'en';
        // Google 网页翻译通常接受 zh-CN, zh-TW, en, ja, etc.
        return lang;
    }

    // 设置 Google 翻译官方 Cookie: googtrans=/source/target
    function setGoogleTranslateCookie(targetLang) {
        var sLang = normalizeGoogleLang(sourceLang);
        var tLang = normalizeGoogleLang(targetLang);
        var cookieVal = '/' + sLang + '/' + tLang;

        var hostParts = window.location.hostname.split('.');
        var domain = '';
        if (hostParts.length > 1) {
            domain = '; domain=.' + hostParts.slice(-2).join('.');
        }

        document.cookie = 'googtrans=' + cookieVal + '; path=/;' + domain;
        document.cookie = 'googtrans=' + cookieVal + '; path=/;';
    }

    // 清除 Google 翻译官方 Cookie 回到原始语言
    function clearGoogleTranslateCookie() {
        var hostParts = window.location.hostname.split('.');
        var domain = '';
        if (hostParts.length > 1) {
            domain = '; domain=.' + hostParts.slice(-2).join('.');
        }

        document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;' + domain;
        document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    }

    // 核心切换接口暴露至全局
    window.WpIpTranslator = {
        changeLanguage: function(targetLang) {
            if (targetLang === sourceLang) {
                clearGoogleTranslateCookie();
            } else {
                setGoogleTranslateCookie(targetLang);
            }

            // 触发 Google 翻译原生 Select 元素更新
            var select = document.querySelector('.goog-te-combo');
            if (select) {
                select.value = normalizeGoogleLang(targetLang);
                select.dispatchEvent(new Event('change'));
            } else {
                // 如果 select 尚未注入，重载页面应用 Cookie
                window.location.reload();
            }

            // 更新页面 UI 中的高亮状态
            this.updateSwitcherUI(targetLang);
        },

        updateSwitcherUI: function(activeLang) {
            // 更新浮动按钮文本
            var floatingText = document.querySelector('.wpit-btn-text');
            if (floatingText) {
                floatingText.textContent = (activeLang.split('-')[0] || activeLang).toUpperCase();
            }

            // 更新高亮 class
            document.querySelectorAll('[data-wpit-container] .active').forEach(function(el) {
                el.classList.remove('active');
            });

            document.querySelectorAll('[data-lang="' + activeLang + '"]').forEach(function(el) {
                var parentItem = el.closest('li, .wpit-bar-item');
                if (parentItem) {
                    parentItem.classList.add('active');
                }
            });
        }
    };

    // Google 翻译引擎全局回调初始化函数
    window.googleTranslateElementInit = function() {
        if (typeof google === 'undefined' || !google.translate || !google.translate.TranslateElement) {
            return;
        }

        new google.translate.TranslateElement({
            pageLanguage: normalizeGoogleLang(sourceLang),
            autoDisplay: false,
            multilanguagePage: true
        }, 'google_translate_element');

        // 如果当前语言不是源语言，且 select 已经加载，执行匹配
        setTimeout(function() {
            if (currentLang && currentLang !== sourceLang) {
                var select = document.querySelector('.goog-te-combo');
                if (select && select.value !== currentLang) {
                    select.value = normalizeGoogleLang(currentLang);
                    select.dispatchEvent(new Event('change'));
                }
            }
        }, 300);
    };

    // 异步注入 Google 官方 translate.google.com API 脚本
    function loadGoogleTranslateScript() {
        // 如果初次访问且目标语言与站点源语言不同，先设置 cookie 便于 Google 初始化直出
        if (currentLang && currentLang !== sourceLang) {
            setGoogleTranslateCookie(currentLang);
        }

        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.async = true;
        script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        document.head.appendChild(script);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadGoogleTranslateScript);
    } else {
        loadGoogleTranslateScript();
    }
})();
