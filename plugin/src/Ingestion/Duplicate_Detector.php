<?php
namespace EsotericCurrent\Core\Ingestion;

class Duplicate_Detector {
    public static function content_hash(string $content): string {
        return hash('sha256', trim($content));
    }

    public static function url_hash(string $url): string {
        return hash('sha256', self::normalize_url($url));
    }

    public static function normalize_url(string $url): string {
        $parsed = parse_url($url);
        if ($parsed === false) {
            return $url;
        }

        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host = strtolower($parsed['host'] ?? '');
        $path = $parsed['path'] ?? '';
        $path = rtrim($path, '/') ?: '/';
        $query = $parsed['query'] ?? '';

        $normalized = "{$scheme}://{$host}{$path}";
        if (!empty($query)) {
            parse_str($query, $params);
            ksort($params);
            $normalized .= '?' . http_build_query($params);
        }

        return $normalized;
    }

    public static function is_duplicate_url(string $url, \wpdb $wpdb): bool {
        $hash = self::url_hash($url);
        $table = $wpdb->prefix . 'ec_findings';
        $count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE content_hash = %s",
            $hash
        ));
        return $count > 0;
    }

    public static function is_duplicate_content(string $content, \wpdb $wpdb): bool {
        $hash = self::content_hash($content);
        $table = $wpdb->prefix . 'ec_findings';
        $count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE content_hash = %s",
            $hash
        ));
        return $count > 0;
    }
}
