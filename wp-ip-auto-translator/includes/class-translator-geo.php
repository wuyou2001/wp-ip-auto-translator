<?php
/**
 * IP 定位与国家-语言智能映射解析器
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Translator_Geo {

    /**
     * ISO 3166-1 alpha-2 国家代码到常见主要语言代码映射字典
     */
    private $country_lang_map = array(
        // 大中华区
        'CN' => 'zh-CN',
        'TW' => 'zh-TW',
        'HK' => 'zh-TW',
        'MO' => 'zh-TW',

        // 亚太地区
        'JP' => 'ja',
        'KR' => 'ko',
        'VN' => 'vi',
        'TH' => 'th',
        'ID' => 'id',
        'MY' => 'ms',
        'PH' => 'tl',
        'SG' => 'zh-CN',
        'IN' => 'hi',

        // 英语核心区
        'US' => 'en',
        'GB' => 'en',
        'CA' => 'en',
        'AU' => 'en',
        'NZ' => 'en',
        'IE' => 'en',
        'ZA' => 'en',

        // 欧洲大语种
        'DE' => 'de',
        'AT' => 'de',
        'CH' => 'de',
        'FR' => 'fr',
        'BE' => 'fr',
        'ES' => 'es',
        'IT' => 'it',
        'PT' => 'pt',
        'NL' => 'nl',
        'PL' => 'pl',
        'RU' => 'ru',
        'UA' => 'uk',
        'TR' => 'tr',
        'GR' => 'el',
        'SE' => 'sv',
        'NO' => 'no',
        'DK' => 'da',
        'FI' => 'fi',
        'CZ' => 'cs',
        'HU' => 'hu',
        'RO' => 'ro',

        // 拉美地区（西班牙语/葡萄牙语）
        'BR' => 'pt',
        'MX' => 'es',
        'AR' => 'es',
        'CL' => 'es',
        'CO' => 'es',
        'PE' => 'es',

        // 中东与阿拉伯语系
        'SA' => 'ar',
        'AE' => 'ar',
        'EG' => 'ar',
        'QA' => 'ar',
        'KW' => 'ar',
        'IL' => 'he',
        'IR' => 'fa',
    );

    public function __construct() {
        // AJAX 回退探测端点（若服务端无 CF 头且无本地缓存）
        add_action('wp_ajax_wp_translator_detect_ip', array($this, 'ajax_detect_ip'));
        add_action('wp_ajax_nopriv_wp_translator_detect_ip', array($this, 'ajax_detect_ip'));
    }

    /**
     * 获取客户端真实公网 IP
     */
    public function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_TRUE_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return !empty($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '127.0.0.1';
    }

    /**
     * 检测访客国家代码（2位大写 ISO 代码）
     */
    public function detect_visitor_country() {
        // 1. 优先从 Cloudflare 特性头直读（零网络开销、毫秒级）
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            $cf_country = strtoupper(trim(sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY'])));
            if (strlen($cf_country) === 2 && $cf_country !== 'XX' && $cf_country !== 'T1') {
                return $cf_country;
            }
        }

        // 2. 检查由前端或之前请求缓存在 Cookie 中的国家代码
        if (!empty($_COOKIE['wp_user_visitor_country'])) {
            $cookie_country = strtoupper(trim(sanitize_text_field($_COOKIE['wp_user_visitor_country'])));
            if (strlen($cookie_country) === 2) {
                return $cookie_country;
            }
        }

        $ip = $this->get_client_ip();
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return '';
        }

        // 3. 服务端 Transient 内存与数据库缓存（缓存 7 天）
        $cache_key = 'wp_ip_geo_' . md5($ip);
        $cached_country = get_transient($cache_key);
        if ($cached_country !== false) {
            return $cached_country;
        }

        // 4. 服务端轻量调用免 Key IP 接口 (ip-api.com)
        $country = '';
        $response = wp_remote_get('http://ip-api.com/json/' . $ip . '?fields=status,countryCode', array(
            'timeout' => 2,
            'sslverify' => false,
            'headers' => array('User-Agent' => 'WordPress-IP-Auto-Translator')
        ));

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($data) && $data['status'] === 'success' && !empty($data['countryCode'])) {
                $country = strtoupper(trim($data['countryCode']));
            }
        }

        if (!empty($country)) {
            set_transient($cache_key, $country, 7 * DAY_IN_SECONDS);
        }

        return $country;
    }

    /**
     * 根据国家解析匹配语言
     */
    public function get_language_by_country($country_code, $available_languages = array(), $default_lang = 'en') {
        $country_code = strtoupper(trim($country_code));
        $mapped_lang = isset($this->country_lang_map[$country_code]) ? $this->country_lang_map[$country_code] : '';

        // 允许主题与第三方插件通过 Filter 自定义国家-语言映射
        $mapped_lang = apply_filters('wp_translator_country_lang_mapping', $mapped_lang, $country_code);

        if (!empty($mapped_lang)) {
            // 如果指定了允许的语言列表，检查是否在其内或有兼容主语言 (如 zh-CN 降级为 zh)
            if (!empty($available_languages)) {
                if (in_array($mapped_lang, $available_languages, true)) {
                    return $mapped_lang;
                }
                $short_lang = explode('-', $mapped_lang)[0];
                foreach ($available_languages as $avail) {
                    if ($avail === $short_lang || strpos($avail, $short_lang . '-') === 0) {
                        return $avail;
                    }
                }
            } else {
                return $mapped_lang;
            }
        }

        return $default_lang;
    }

    /**
     * 客户端 AJAX 异步探测接口
     */
    public function ajax_detect_ip() {
        check_ajax_referer('wp_translator_nonce', 'security');
        $country = $this->detect_visitor_country();
        $ip = $this->get_client_ip();

        wp_send_json_success(array(
            'ip' => $ip,
            'country' => $country
        ));
    }
}
