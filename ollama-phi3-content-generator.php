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
    $url = "http://localhost:11434/api/generate";

    $data = [
        'model' => 'phi3:mini',
        'prompt' => "Write a detailed blog post about $keyword.",
        'stream' => false
    ];

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode($data),
        'timeout' => 100
    ]);

    if (is_wp_error($response)) {
        echo "Error retrieving AI-generated content: " . $response->get_error_message();
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($body['response'])) {
        echo "Unexpected API response format.";
        return;
    }

    $generated_text = $body['response'];

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
    $pexels_api_key = 'PUT YOUR API KEY HERE';
    $url = "https://api.pexels.com/v1/search?query=" . urlencode($keyword) . "&per_page=1";

    $response = wp_remote_get($url, [
        'headers' => ['Authorization' => $pexels_api_key]
    ]);

    if (is_wp_error($response)) return '';

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['photos'][0]['src']['large'] ?? '';
}

