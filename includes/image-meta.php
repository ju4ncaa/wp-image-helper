<?php
function chatgpt_send_image_prompt($base64_image, $prompt, $api_key) {
    $url = 'https://api.openai.com/v1/chat/completions';

    $payload = json_encode([
        'model' => 'gpt-4-vision-preview',
        'messages' => [
            ['role' => 'user', 'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,' . $base64_image]]
            ]]
        ],
        'max_tokens' => 150,
    ]);

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => $payload,
        'timeout' => 60,
    ]);

    if (is_wp_error($response)) {
        return null;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['choices'][0]['message']['content'] ?? null;
}

add_filter('wp_generate_attachment_metadata', 'chatgpt_generate_image_metadata', 10, 2);

function chatgpt_generate_image_metadata($metadata, $attachment_id) {
    $file_path = get_attached_file($attachment_id);
    $mime_type = get_post_mime_type($attachment_id);

    if (strpos($mime_type, 'image') !== false && file_exists($file_path)) {
        $base64_image = base64_encode(file_get_contents($file_path));
        $api_key = get_option('chatgpt_api_key');

        if (!$api_key) {
            return $metadata;
        }

        $prompt = "Describe brevemente el contenido de esta imagen. Devuélveme una línea como título y una segunda línea como texto alternativo para uso accesible en una página web.";

        $response = chatgpt_send_image_prompt($base64_image, $prompt, $api_key);

        if ($response) {
            $lines = explode("\n", trim($response));
            $title = isset($lines[0]) ? sanitize_text_field($lines[0]) : '';
            $alt = isset($lines[1]) ? sanitize_text_field($lines[1]) : '';

            if ($title) {
                wp_update_post([
                    'ID' => $attachment_id,
                    'post_title' => $title
                ]);
            }

            if ($alt) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
            }
        }
    }

    return $metadata;
}
