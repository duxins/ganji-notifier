#!/usr/bin/env php
<?php
define('ABSPATH', dirname(__FILE__));
define('LOGPATH', ABSPATH.'/logs');
require_once(ABSPATH.'/libs/spider/Spider.php');
require_once(ABSPATH.'/libs/PHPMailer/class.phpmailer.php');

$dry_run = FALSE;
$test_url = "";

for($i=1; $i<$argc; $i++){
    switch($argv[$i]){
        case '-h':
        case '--help':
            show_usage();
            break;
        case '-n':
        case '--dry-run':
            $dry_run = TRUE; 
            break;
        case '-u':
        case '--url':
            $test_url = @$argv[$i+1];
            break;
    }
}

$notifier = new Notifier();

if($dry_run){
    Notifier::log('DRY RUN');
    $notifier->dry_run();
}elseif($test_url){
    $notifier->test_url($test_url);
}else{
    $notifier->run();
}

class Notifier{
    private $spiders;
    private  $config = array();
    private $settings = array();
    private  $test_mode = FALSE;
    private $profile = '';

    public function __construct(){
        $this->load_spiders();
        $this->load_config();
    }

    public function run($profile = ''){
        if(!$profile){
            foreach($this->config as $name => $cfg){
                $this->_run($name);
            }
        }elseif(!isset($this->config[$profile])){
            echo "profile $profile doesn't exist.";
            exit(1);
        }else{
            $this->_run($profile);
        }
    }

    public function dry_run($profile = ''){
        $this->test_mode = TRUE;
        $this->run($profile);
    }

    public function test_url($url){
        $this->test_mode = TRUE;
        $spider = $this->choose_spider($url);
        if(!$spider){
            self::error("Can't parse $url!"); 
            exit;
        }

        self::log('spider: '.get_class($spider)); 
        $list = $spider->fetch($url);

        self::log($list);
    }

    private function _run($profile){
        self::log('profile: '.$profile);
        $this->profile = $profile;
        $cfg = $this->config[$profile];
        $urls = $cfg['urls'];

        $items = array();
        foreach($urls as $url){
            self::log('> url: '.$url);
            $spider = $this->choose_spider($url);
            if(!$spider){
                self::error("> Can't parse $url!"); 
                continue;
            }

            self::log('> spider: '.get_class($spider)); 

            $list = $spider->fetch($url);

            foreach($list as $item){
                $text = $item['title'] . $item['desc'];
                if($this->filter($text) && !$this->is_notified($item)){
                    $items[] = $item;
                }
            }
        }

        if(!$this->test_mode){
            $this->notify($items); 
        }
    }

    private function notify($items){
        if(!$items){
            return FALSE;
        }

        $first = reset($items);

        $cfg = $this->config[$this->profile];
        $recipients = @$cfg['recipients'];
        if(!$recipients){
            $recipients = $this->settings['mail']['recipients'];
        }

        $subject = "[$this->profile 更新] ".$first['title'].' ... ('.count($items).')';
        $body = "<ul>";
        foreach($items as $item){
            $body .= "<li><a href='".$item['link']."'>".
                     $item['title']."</a> - ".
                     $item['time']."<br><span class='color:grey'>".
                     $item['desc']."</span></li>\n";
        }
        $body .= "</ul>";

        $mail = new PHPMailer;

        $mail->CharSet = 'UTF-8';
        $mail->IsSMTP();                       
        $mail->Host = $this->settings['mail']['host'];
        $mail->SMTPAuth = true;                            
        $mail->Username = $this->settings['mail']['username'];                        
        $mail->Password = $this->settings['mail']['password'];                      
        $mail->From = $this->settings['mail']['from'];
        $mail->FromName = $this->settings['mail']['fromname'];

        foreach($recipients as $item){
            $mail->AddAddress($item);
        }

        $mail->IsHTML(true); 
        $mail->Subject = $subject;
        $mail->Body    = $body;
        self::log('> Send email to: '.implode(',', $recipients));
        if(!$mail->Send()){
            self::error("> Error:" . $mail->ErrorInfo);
        }

        $logfile = LOGPATH.'/'.$this->profile.".log";
        foreach($items as $item){
            @file_put_contents($logfile, $item['link']."\n", FILE_APPEND);            
        }
    }

    private function is_notified($item){
        $link = $item['link'];
        $logfile = LOGPATH.'/'.$this->profile.".log";

        if(!file_exists($logfile)){
            return FALSE;
        }

        $cmd = "grep '$link' $logfile > /dev/null";
        system($cmd, $status);

        if($status == 0){
            if($this->test_mode){
                self::error("> - $link");
            }
            return TRUE;
        }

        if($this->test_mode){
            self::ok("> + $link");
        }

        return FALSE;
    }

    private function filter($text){
        $cfg = $this->config[$this->profile];
        if(isset($cfg['ignore'])){
            $ignore = $cfg['ignore'];
            foreach($ignore as $w){
                if(preg_match("#$w#", $text)){
                    return FALSE;
                }
            }
        }

        if(isset($cfg['keywords'])){
            $keywords = $cfg['keywords'];
            $matched = 0;
            $mismatched = 0;
            foreach($keywords as $w){
                //必须包含的关键词
                if(preg_match("#^!#", $w)){
                    $w = preg_replace("#^!#", "", $w);
                    if(!preg_match("#$w#", $text)){
                        return FALSE;
                    }
                }elseif(preg_match("#$w#", $text)){
                    //echo "匹配 $w\n";
                    $matched ++;
                }else{
                    $mismatched ++;
                }
            }
            if($matched > 0 || $mismatched == 0){
                return TRUE;
            }else{
                return FALSE;
            }

        }

        return TRUE;

    }

    private function choose_spider($url){
        foreach($this->spiders as $spider){
            if($spider->canParse($url)){
                return $spider;
            }
        }
        return false;
    }
    private function load_spiders(){
        $files = glob(ABSPATH.'/spiders/*.php'); 
        foreach($files as $file){
            $name = str_ireplace('.php', '',basename($file));
            include_once($file);
            $class_name = ucfirst($name);
            $this->spiders[$name] = new $class_name;
        }
    }

    private function load_config(){
        include_once(ABSPATH.'/config.php');
        $this->config = $config;
        $this->settings = $settings;
    }

    public static function log($msg, $newline = TRUE){
        if(is_array($msg)){
            print_r($msg);
        }else{
            echo $msg;
        }
        echo ($newline?"\n":"");
    }

    public static function error($msg, $newline = TRUE){
        echo "\033[0;31m".$msg."\033[0m".($newline?"\n":"");
    }

    public static function ok($msg, $newline = TRUE){
        echo "\033[0;32m".$msg."\033[0m".($newline?"\n":"");
    }
}

function show_usage(){
    $script_name = basename(__FILE__);
    echo "
    Usage: $script_name [options]

    Options:

      -h, --help \tOutput usage information
      -n, --dry-run
      -u, --url <url> 

";
}
