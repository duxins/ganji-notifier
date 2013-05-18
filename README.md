#赶集网, 58 信息更新提醒


##config.php

```php
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
        "!全新",  //必须包含关键词，以'!'开头
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
```