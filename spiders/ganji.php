<?php
class Ganji{
    private $spider;

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
        $this->url = $url;
        $html = $this->spider->fetch($url);

        if(preg_match('#<dl\s+class="list-bigpic#isU', $html)){
            $list = $this->_parse_tpl_bigpic($html);
        }elseif(preg_match('#<dl\s+class="list-nopic#isU',$html)){
            $list = $this->_parse_tpl_nopic($html);
        }

        return $list;

    }

    private function _parse_tpl_bigpic($html){
        if(!preg_match_all("#<dl\s+class=\"list-bigpic[^>]*>(.*)</dl>#isU", $html, $matches, PREG_SET_ORDER)){
            return array();
        }

        $list = array();

        foreach($matches as $item){
            if(!preg_match('#<a href="([^<]*)" target="_blank" class="ft-tit">([^<]*)</a>#isU', $item[1], $match)){
                continue;
            }

            $link = preg_replace("/#(.*)$/isU","",$match[1]);
            $title = trim($match[2]);

            $id = '';
            if(!preg_match('#\/(\w+)\.htm#', $link, $match)){
                continue;
            }

            $id = str_replace("x", "", $match[1]);

            $desc = '';
            if(preg_match("#<p>([^<]*)</p>\s+<p>#isU", $item[1], $match)){
                $desc = str_replace(array("\n", "\r"), "", $match[1]);
            }

            $time = '';
            if(preg_match("#<i class=\"mr8\">([^<]*)</i>#isU", $item[1], $match)){
                $time = $match[1];
            }

            if($time == '商家'){
                continue;
            }

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

    private function _parse_tpl_nopic($html){
        $host = parse_url($this->url, PHP_URL_HOST);

        if(!preg_match_all("#<dl\s+class=\"list-nopic\"\s+>(.*)</dl>#isU", $html, $matches, PREG_SET_ORDER)){
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
    }

}