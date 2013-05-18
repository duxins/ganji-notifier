<?php
class Ganji{
    protected $spider;

    public function __construct(){
       $this->spider = new Spider(); 
    }
    public function canParse($url){
        if(
            preg_match("#ganji\.com#isU", $url)
        ){
            return TRUE;
        }
        return FALSE;
    }

    public function fetch($url){
        $host = strtolower(parse_url($url, PHP_URL_HOST));
        $html = $this->spider->fetch($url);
        if(!preg_match_all("#<dl\s+class=\"list-nopic\"\s+>(.*)</dl>#isU", $html, $matches, PREG_SET_ORDER)){
            echo "[$url] can't get urls\n";
            return array();
        }

        $list = array();
        foreach($matches as $item){
            if(!preg_match("#<a href=\"(.*)\"[^>]*class=\"infor-title01[^>]*>(.*)</a>#isU", $item[1], $match)){
                continue;
            }

            $link = $match[1];
            $title = $match[2];

            if(!preg_match("#/(\w+)\.htm#", $link, $match)){
                continue;
            }

            $id = str_replace('x', '', $match[1]);

            $status = "";
            if(preg_match("#class=\"show01\">([^<]*)</dd>#isU", $item[1], $match)){
                $status = trim($match[1]);
            }

            $time = "";
            if(preg_match('#class="time[^>]*>([^<]*)</span>#isU', $item[1], $match)){
                $time = trim($match[1]);
            }

            if($time == '商家'){
                continue;
            }

            $list[] = array(
                'id' => $id,
                'link' => "http://$host".$link,
                'title' => $title.($status?' - '.$status:''),
                'time' => $time,
                'desc' => ''
            );
        }

        return $list;
    }

}