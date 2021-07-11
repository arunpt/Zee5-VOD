<?php

/* Copyright (C) 2021 W4RR10R.
Licensed under the  GPL-3.0 License;
you may not use this file except in compliance with the License.
*/

// usage zee5.php?id=0-0-408330

header("Content-Type: application/json");

if(!isset($_GET["id"]) || !$_GET["id"]) {
    exit("No content id specified");
}

function video_token() {
    $resp = file_get_contents("https://useraction.zee5.com/tokennd/");
    return json_decode($resp) -> video_token;
}

function platform_token() {
    $r = file_get_contents("https://useraction.zee5.com/token/platform_tokens.php?platform_name=web_app");
    return json_decode($r) -> token;
}

function get_metadata($zee_api) {
    $token = platform_token();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $zee_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-ACCESS-TOKEN: $token"
    ));
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "Error: " . curl_error($ch);
        return null;
    }
    curl_close($ch);
    return json_decode($response);
}

function zee5_movies($content_id) {
    $response = @get_metadata("https://gwapi.zee5.com/content/details/{$content_id}?translation=en&country=IN&version=2");
    $hls = str_replace('drm', 'hls', $response->hls[0]);
    $token = video_token();
    $stream_url = "https://zee5vodnd.akamaized.net{$hls}{$token}";
    $arr = array(
        "id" => $response -> id,
        "title" => $response  -> title,
        "thumb" => $response  -> image_url,
        "subtitle" => $response  -> video_details -> vtt_thumbnail_url,
        "hls" => $stream_url
    );
    echo json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

$response = @get_metadata("https://gwapi.zee5.com/content/tvshow/{$_GET['id']}?translation=en&country=IN&version=2");
if (array_key_exists('seasons', $response)) {
    $result = array();
    foreach($response -> seasons as $season) {
        foreach($season -> episodes as $ep) {
            $hls = str_replace('drm', 'hls', $ep->hls[0]);
            $token = video_token();
            $stream_url = "https://zee5vodnd.akamaized.net{$hls}{$token}";
            $result[] = array(
                "id" => $ep -> id,
                "title" => $ep -> title,
                "thumb" => $ep -> image_url,
                "subtitle" => $ep -> video_details -> vtt_thumbnail_url,
                "hls" => $stream_url
            );
        }
    }
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} else {
    zee5_movies($content_id);
}
?>                          
