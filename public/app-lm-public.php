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
            if (!isset($map['enabled']) || $map['enabled'] === false) {
                continue;
            }
    
            $target_path = wp_parse_url($map['url'], PHP_URL_PATH);
    
            if (!$target_path || strpos($current_path, $target_path) === false) {
                continue;
            }
    
            $keyword  = trim($map['keyword']);
            $outbound = esc_url($map['outbound']);
    
            if ($keyword && $outbound) {
                $pattern_split = '/(<a\b[^>]*>.*?<\/a>|<h[1-4][^>]*>.*?<\/h[1-4]>|alt="[^"]*")/is';
    
                $keyword_pattern = '/(?<![\w\/\.])' . preg_quote($keyword, '/') . '(?![\w\.])/i';
    
                $parts = preg_split($pattern_split, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    
                foreach ($parts as &$part) {
                    // Skip segments that are anchors, headings, or alt attributes
                    if (
                        preg_match('/^<a\b[^>]*>.*<\/a>$/is', $part) ||
                        preg_match('/^<h[1-4][^>]*>.*<\/h[1-4]>$/is', $part) ||
                        preg_match('/^alt="[^"]*"$/i', $part)
                    ) {
                        continue;
                    }
    
                    $part = preg_replace_callback($keyword_pattern, function ($match) use ($outbound) {
                        return '<a href="' . $outbound . '" target="_blank" rel="nofollow">' . $match[0] . '</a>';
                    }, $part);
                }
    
                $content = implode('', $parts);
            }
        }
    
        return $content;
    }    
}