<?php

require_once __DIR__ . '/../config/sms.php';


function formatSriLankaMobile(
    string $mobile
): string {

    // Remove spaces, +, -, brackets etc.
    $mobile =
        preg_replace(
            '/[^0-9]/',
            '',
            $mobile
        );


    // Example:
    // 0771234567 → 94771234567

    if (
        str_starts_with(
            $mobile,
            "0"
        )
    ) {

        $mobile =
            "94"
            .
            substr(
                $mobile,
                1
            );
    }


    // +9477... becomes 9477...
    // already handled because + was removed.


    return $mobile;
}



function sendSms(
    string $mobile,
    string $message
): array {

    $mobile =
        formatSriLankaMobile(
            $mobile
        );


    $url =
        "https://app.notify.lk/api/v1/send";


    $data = [

        "user_id" =>
            NOTIFY_USER_ID,

        "api_key" =>
            NOTIFY_API_KEY,

        "sender_id" =>
            NOTIFY_SENDER_ID,

        "to" =>
            $mobile,

        "message" =>
            $message

    ];


    $ch =
        curl_init();


    curl_setopt_array(
        $ch,
        [

            CURLOPT_URL =>
                $url,

            CURLOPT_POST =>
                true,

            CURLOPT_POSTFIELDS =>
                http_build_query(
                    $data
                ),

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_TIMEOUT =>
                30

        ]
    );


    $response =
        curl_exec(
            $ch
        );


    $curlError =
        curl_error(
            $ch
        );


    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    curl_close(
        $ch
    );


    if (
        $response === false
    ) {

        return [

            "success" => false,

            "message" =>
                $curlError,

            "response" =>
                null

        ];
    }


    $decoded =
        json_decode(
            $response,
            true
        );


    return [

        "success" =>
            (
                $httpCode >= 200
                &&
                $httpCode < 300
            ),

        "message" =>
            $decoded[
                "data"
            ]
            ??
            $decoded[
                "message"
            ]
            ??
            "API response received",

        "response" =>
            $decoded
            ??
            $response

    ];
}