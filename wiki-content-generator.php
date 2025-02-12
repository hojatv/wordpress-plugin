<?php
/*
Plugin Name: AI Content Generator
Plugin URI: https://yourwebsite.com
Description: Generates blog posts based on a keyword using AI.
Version: 1.0
Author: Your Name
Author URI: https://yourwebsite.com
License: GPL2
*/

add_action('admin_menu', 'ai_content_generator_menu');

function ai_content_generator_menu() {
    add_menu_page(
        'AI Content Generator',
        'AI Content Generator',
        'manage_options',
        'ai-content-generator',
        'ai_content_generator_page'
    );
}

function ai_content_generator_page() {
    ?>
    <div class="wrap">
        <h1>AI Content Generator</h1>
        <form method="post">
            <?php wp_nonce_field('ai_generate_post', 'ai_nonce'); ?>
            <p>
                <label for="keyword">Enter a Keyword:</label>
                <input type="text" id="keyword" name="keyword">
            </p>
            <p>
                <input type="submit" name="generate" value="Generate Post">
            </p>
        </form>
    </div>
    <?php
    if (isset($_POST['generate']) && check_admin_referer('ai_generate_post', 'ai_nonce')) {
        $keyword = sanitize_text_field($_POST['keyword']);
        generate_ai_post($keyword);
        echo "<p>Post generated successfully!</p>";
    }
}

function generate_ai_post($keyword) {
    $url = "https://en.wikipedia.org/w/api.php?action=query&format=json&prop=extracts&exintro&explaintext&titles=" . urlencode($keyword);
    
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        echo "Error retrieving Wikipedia content.";
        return;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $pages = $body['query']['pages'] ?? [];
    $generated_text = "No Wikipedia content available.";
    
    foreach ($pages as $page) {
        if (isset($page['extract'])) {
            $generated_text = $page['extract'];
            break;
        }
    }
    
    $image_url = get_related_image($keyword);
    if ($image_url) {
        $generated_text .= "<br><img src='$image_url' alt='$keyword'>";
    }
    
    $post_data = [
        'post_title'    => ucfirst($keyword),
        'post_content'  => $generated_text,
        'post_status'   => 'publish',
        'post_author'   => get_current_user_id(),
        'post_category' => [1]
    ];
    wp_insert_post($post_data);
}

function get_related_image($keyword) {
    $pexels_api_key = 'put your api key here';
    $url = "https://api.pexels.com/v1/search?query=" . urlencode($keyword) . "&per_page=1";
    
    $response = wp_remote_get($url, [
        'headers' => ['Authorization' => $pexels_api_key]
    ]);
    
    if (is_wp_error($response)) return '';
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['photos'][0]['src']['large'] ?? '';
}

