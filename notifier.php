#!/usr/bin/env php
<?php
define('ABSPATH', dirname(__FILE__));
require_once(ABSPATH.'/libs/spider/Spider.php');

for($i=1; $i<$argc; $i++){
    switch($argv[$i]){
        case '-h':
        case '--help':
            show_usage();
            break;
    }
}

$notifier = new Notifier();
$notifier->run();

class Notifier{
    protected $spiders;
    protected $config = array();
    protected $test_mode = FALSE;

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

    protected function _run($profile){
        $cfg = $this->config[$profile];
        $urls = $cfg['urls'];
        foreach($urls as $url){
            $spider = $this->choose_spider($url);
            if(!$spider){
                echo "Can't parse $url";
                continue;
            }

            $list = $spider->fetch($url);
            print_r($list);
        }
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
    }
}

function show_usage(){
    $script_name = basename(__FILE__);
    echo "
    Usage: $script_name [options]

    Options:

      -h, --help \tOutput usage information

";
}
