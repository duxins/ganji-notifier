<?php
class Wuba{
    protected $spider;

    public function __construct(){
       $this->spider = new Spider(); 
    }
    public function canParse($url){
        if(
            preg_match("#58\.com#isU", $url)
        ){
            return TRUE;
        }
        return FALSE;
    }

    public function fetch($url){
        $html = $this->spider->fetch($url);

        if(!preg_match_all("#<tr logr=\"(.*)\">(.*)</tr>#isU", $html, $matches, PREG_SET_ORDER)){
            echo "[$url] can't get urls\n";
            return array();
        }

        $list = array();
        foreach($matches as $item){
            //过滤 "商家推广"
            if(preg_match("#adJump#", $item[0])){
                continue;
            }
            
            if(!preg_match("#<a href=\"([^<]*)\"[^>]*class=\"t\"\s+>(.*)</a>#isU", $item[0], $match)){
                continue;
            }

            $link = $match[1];
            $title = trim($match[2]);

            if(!preg_match("#/(\w+)\.shtml#", $link, $match)){
                continue;
            }

            $id = str_replace('x', '', $match[1]);

            $desc = "";
            if(preg_match("#</i>([^<]*)<i#isU", $item[0], $match)){
                $desc = trim($match[1]);
            }

            $time = "";
            

            $list[] = array(
                'id' => $id,
                'link' => $link,
                'title' => $title,
                'time' => $time,
                'desc' => $desc
            );
        }

        return $list;
    }

}
