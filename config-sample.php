<?php
$settings = array(
    "mail" => array(
        'host' => "smtp.example.com",
        'username' => "user@example.com",
        'password' => "123456",
        'from' => "user@example.com",
        'fromname' => "user",
    )
);


$config['apple'] = array(
    "urls" => array(
        "http://bj.58.com/iphonesj/",
        "http://bj.ganji.com/iphone/"
    ),

    "keywords" => array(
        "!全新",
        "iphone",
        "ipad",
        "itouch"
    ),

    "ignore" => array(
        "android"
    ),

    "recipients" => array(
        "user@example.com"
    )
);
