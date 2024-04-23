<?php
/*
Plugin Name: Title Generate
Description: Automatically generate titles
Version: 0.1
Author: soulteary
Author URI: https://soulteary.com/
Requires at least: 6.0
License: MIT
*/

// 避免直接访问
if (!defined("ABSPATH")) {
    die();
}

// 调用 dify 服务来生成标题
function generate_title_by_content($content)
{
    $ch = curl_init();
    curl_setopt(
        $ch,
        CURLOPT_URL,
        "http://10.11.12.90:8082/v1/completion-messages"
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer app-YChjQYVOeEgiMR6tsmrXfVZM",
        "Content-Type: application/json",
    ]);

    $payload = [
        "inputs" => [
            "content" => $content,
        ],
        "response_mode" => "blocking",
        "user" => "soulteary",
    ];
    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        json_encode($payload, JSON_UNESCAPED_UNICODE)
    );
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (empty($data["answer"])) {
        return "AI 生成标题失败";
    }

    $title = $data["answer"];
    $title = str_replace('"', "", $title);
    return $title;
}

// 当文章发布或更新时，如果标题为空，自动生成一个标题
add_action("the_post", "update_post_title");
function update_post_title($post)
{
    // 当标题存在，就不再生成
    if (!empty($post->post_title)) {
        return;
    }

    // 生成标题
    $post_title = generate_title_by_content($post->post_content);

    // 更新数据库中标题
    wp_update_post(["ID" => $post->ID, "post_title" => $post_title]);

    // 更新当前文章对象
    $post->post_title = $post_title;
}
