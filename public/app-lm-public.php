<?php

Class Appetiser_Link_Mapper_Public {

    public function __construct() {
        add_filter('the_content', array ($this, 'replace_keywords_with_links') );
    }

    public function replace_keywords_with_links($content) {
        $mappings = get_option('app_lm_link_mappings', []);

        if (empty($mappings) || !is_array($mappings)) {
            return $content;
        }

        $current_path = $_SERVER['REQUEST_URI'];

        foreach ($mappings as $map) {
            if (empty($map['enabled'])) {
                continue;
            } 

            $target_path = wp_parse_url($map['url'], PHP_URL_PATH);

            if (!$target_path || strpos($current_path, $target_path) === false) {
                continue;
            }

            $keyword       = trim($map['keyword']);
            $outbound      = esc_url($map['outbound']);
            $replace_mode  = isset($map['replace_mode']) ? $map['replace_mode'] : 'all';
            $nofollow      = !empty($map['nofollow']) ? ' rel="nofollow"' : '';
            $target        = isset($map['target']) ? $map['target'] : '_self';

            if ($keyword && $outbound) {
                $pattern_split = '/(<a\b[^>]*>.*?<\/a>|<h[1-4][^>]*>.*?<\/h[1-4]>|alt="[^"]*")/is';
                $keyword_pattern = '/(?<![\w\/\.])' . preg_quote($keyword, '/') . '(?![\w\.])/i';

                $parts = preg_split($pattern_split, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

                $replace_done = false;

                foreach ($parts as &$part) {
                    if (
                        preg_match('/^<a\b[^>]*>.*<\/a>$/is', $part) ||
                        preg_match('/^<h[1-4][^>]*>.*<\/h[1-4]>$/is', $part) ||
                        preg_match('/^alt="[^"]*"$/i', $part)
                    ) {
                        continue;
                    }

                    $part = preg_replace_callback($keyword_pattern, function ($match) use ($outbound, $nofollow, $target, $replace_mode, &$replace_done) {
                        if ($replace_mode === 'first' && $replace_done) {
                            return $match[0];
                        }
                        $replace_done = true;
                        return '<a href="' . $outbound . '" target="' . $target . '"' . $nofollow . ' class="app-lm-link">' . $match[0] . '</a>';
                    }, $part, ($replace_mode === 'first' ? 1 : -1));
                }

                $content = implode('', $parts);
            }
        }

        return $content;
    }

}