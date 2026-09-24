/**
 * Google 网页翻译客户端静默加载与调度驱动
 */
(function() {
    'use strict';

    var data = window.wpTranslatorData || {};
    var sourceLang = data.sourceLang || 'zh-CN';
    var currentLang = data.currentLang || sourceLang;
    var languagesPool = data.languagesPool || {};
    var showFlag = (typeof data.showFlag !== 'undefined') ? data.showFlag : true;

    // 获取二级根域名
    function getRootDomain() {
        var host = window.location.hostname;
        var parts = host.split('.');
        if (parts.length > 2 && !/^\d+\.\d+\.\d+\.\d+$/.test(host)) {
            return '; domain=.' + parts.slice(-2).join('.');
        }
        return '';
    }

    // 格式化语言代码给 Google 翻译库
    function normalizeGoogleLang(lang) {
        if (!lang) return 'en';
        return lang;
    }

    // 设置 Google 翻译官方 Cookie: googtrans=/source/target
    function setGoogleTranslateCookie(targetLang) {
        var sLang = normalizeGoogleLang(sourceLang);
        var tLang = normalizeGoogleLang(targetLang);
        var cookieVal = '/' + sLang + '/' + tLang;
        var domain = getRootDomain();

        document.cookie = 'googtrans=' + cookieVal + '; path=/' + domain + '; SameSite=Lax';
        document.cookie = 'googtrans=' + cookieVal + '; path=/; SameSite=Lax';
    }

    // 清除 Google 翻译官方 Cookie 回到原始语言
    function clearGoogleTranslateCookie() {
        var domain = getRootDomain();
        var expireStr = '; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';
        document.cookie = 'googtrans=' + expireStr + domain + '; SameSite=Lax';
        document.cookie = 'googtrans=' + expireStr + '; SameSite=Lax';
    }

    // 核心切换接口暴露至全局
    window.WpIpTranslator = {
        changeLanguage: function(targetLang) {
            currentLang = targetLang;

            if (targetLang === sourceLang) {
                clearGoogleTranslateCookie();
            } else {
                setGoogleTranslateCookie(targetLang);
            }

            // 立即且全量更新界面对勾、图标、文字高亮
            this.updateSwitcherUI(targetLang);

            // 触发 Google 翻译原生 Select 元素更新
            var select = document.querySelector('.goog-te-combo');
            if (select) {
                select.value = normalizeGoogleLang(targetLang);
                select.dispatchEvent(new Event('change'));
            } else {
                // 如果 select 尚未注入，重载页面以激活 Cookie
                window.location.reload();
            }
        },

        updateSwitcherUI: function(activeLang) {
            if (!activeLang) activeLang = currentLang;

            var info = languagesPool[activeLang] || {
                name: activeLang,
                native: activeLang,
                flag: '🌐'
            };

            // 1. 同步更新悬浮球模版 (Floating Badge)
            var floatingFlag = document.querySelector('.wpit-btn-flag');
            var floatingText = document.querySelector('.wpit-btn-text');
            var floatingTrigger = document.getElementById('wpit-floating-trigger');

            if (floatingFlag && showFlag) {
                floatingFlag.textContent = info.flag || '🌐';
            }
            if (floatingText) {
                floatingText.textContent = (activeLang.split('-')[0] || activeLang).toUpperCase();
            }
            if (floatingTrigger) {
                floatingTrigger.setAttribute('title', info.native || activeLang);
            }

            // 2. 同步更新下拉菜单模版 (Dropdown)
            document.querySelectorAll('.wpit-dropdown-toggle').forEach(function(btn) {
                var dropFlag = btn.querySelector('.wpit-drop-flag');
                var dropLabel = btn.querySelector('.wpit-drop-label');

                if (dropFlag && showFlag) {
                    dropFlag.textContent = info.flag || '🌐';
                }
                if (dropLabel) {
                    dropLabel.textContent = info.native || activeLang;
                }
            });

            // 3. 更新所有模版中的激活高亮项与对勾迁移 (Active / Checkmark Sync)
            // 先移除所有旧 active class
            document.querySelectorAll('.wpit-lang-item.active, .wpit-drop-item.active, .wpit-bar-item.active').forEach(function(el) {
                el.classList.remove('active');
            });

            // 给对应当前语言的项加上 active class
            document.querySelectorAll('[data-lang="' + activeLang + '"]').forEach(function(el) {
                var parentItem = el.closest('.wpit-lang-item, .wpit-drop-item, .wpit-bar-item');
                if (parentItem) {
                    parentItem.classList.add('active');
                } else if (el.classList.contains('wpit-bar-item')) {
                    el.classList.add('active');
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
            // 确保初始化时对勾与图标和激活语言精确一致
            if (window.WpIpTranslator) {
                window.WpIpTranslator.updateSwitcherUI(currentLang);
            }
        }, 300);
    };

    // 页面就绪后立即校准一次 UI 图标、文字及对勾状态
    document.addEventListener('DOMContentLoaded', function() {
        if (window.WpIpTranslator) {
            window.WpIpTranslator.updateSwitcherUI(currentLang);
        }
    });

    // 异步注入 Google 官方 translate.google.com API 脚本
    function loadGoogleTranslateScript() {
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
