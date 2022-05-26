<?php


namespace app\api\controller;

use app\BaseController;
use app\model\Book;
use think\facade\App;

class Sitemap extends BaseController
{
    protected $books;
    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $end_point = config('seo.book_end_point');
        $num = config('seo.sitemap_gen_num');
        if ($num <= 0) {
            $this->books = Book::select();
        } else {
            $this->books = Book::order('id', 'desc')->limit($num)->select();
        }

        foreach ($this->books as &$book) {
            if ($end_point == 'id') {
                $book['param'] = $book['id'];
            } else {
                $book['param'] = $book['unique_id'];
            }
        }
    }

    public function gen()
    {
        $array = $this->create_array();
        $this->gensitemap($array, 'pc');
        $this->genurls();
        return json(['success' => 1, 'msg' => 'ok']);
    }

    private function gensitemap($array) {
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset>\n";
        foreach ($array as $data) {
            $content .= $this->create_item($data);
        }
        $content .= '</urlset>';
        $fp = fopen(App::getRootPath() .'public/sitemap.xml', 'w+');
        fwrite($fp, $content);
        fclose($fp);
    }

    private function genurls() {
        $site_name = config('site.domain');
        $urls = '';
        foreach ($this->books  as $key => $book) {
            $urls .= $site_name.'/'.BOOKCTRL.'/'.$book->id."\n";
            $fp = fopen(App::getRootPath() .'public/sitemap.txt', 'w+');
            fwrite($fp, $urls);
        }
    }

    private function create_array(){
        $site_name = config('site.domain');

        $data = array();
        $main = array(
            'loc' => $site_name,
            'priority' => '1.0'
        );
        $booklist= array(
            'loc' => $site_name.'/booklist',
            'priority' => '0.5',
            'lastmod' => date("Y-m-d"),
            'changefreq' => 'yearly'
        );


        foreach ($this->books  as $key => $book){ //这里构建所有的内容页数组
            $temp = array(
                'loc' => $site_name.'/'.BOOKCTRL.'/'.$book['param'],
                'priority' => '0.9',
            );
            array_push( $data,$temp);
        }

        array_push($data,$main);
        array_push($data,$booklist);
        return $data;
    }

    private function create_item($data)
    {
        $item = "<url>\n";
        $item .= "<loc>" . $data['loc'] . "</loc>\n";
        $item .= "<priority>" . $data['priority'] . "</priority>\n";
        $item .= "</url>\n";
        return $item;
    }

}