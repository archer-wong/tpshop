<?php
/**
 * Created by PhpStorm.
 * User: wangchao
 * Date: 2016/10/18
 * Time: 15:04
 */

//商品goodsmodel模型

namespace Model;
use Think\Model;

class GoodsModel extends Model{
    //自动完成设置add_time和upd_time
    protected $_auto = array(
        array('add_time','time',1,'function'),
        array('upd_time','time',3,'function'),
    );

    /**
     * 添加数据之前的处理
     * @param $data 收集表单信息  &的作用是 "引用" 传递
     * @param $options  设置的各种条件
     */
    protected function _before_insert(&$data, $options)
    {
        if($_FILES["goods_logo"]["error"] === 0){

            //1.通过Think/Upload.class.php实现附件上传
            $cfg = array(
                "rootPath" => "./Common/Uploads/",  //设置保存目录
            );
            $up = new \Think\Upload($cfg);
            $z =$up -> uploadOne($_FILES["goods_logo"]);
            //拼装图片路径
            $big_path_name = $up->rootPath.$z["savepath"].$z["savename"];
            $data['goods_big_logo'] = $big_path_name;

            //2.制作缩略图
            $im = new \Think\Image();
            $im -> open($big_path_name);    //打开原图
            $im -> thumb(60,60);     //缩略图大小
            //缩略图名字：“small_原图名字”
            $small_path_name = $up->rootPath.$z['savepath']."small_".$z['savename'];
            $im -> save($small_path_name);  //存储缩略图到服务器
            //保存缩略图到数据库
            $data['goods_small_logo'] = $small_path_name;

        }
    }

    /**
     * 添加数据之后的处理
     * @param $data
     * @param $options
     */
    protected function _after_insert($data, $options)
    {
        //上传相册图片判断(只要有一个相册上传,就往下进行)
        foreach($_FILES['goods_pics']['error'] as $a => $b){
            if($b === 0){
                $flag = true;
                break;
            }
        }

        if($flag === true){
            //商品图片上传
            $cfg = array(
                "rootPath" => "./Common/Pics/",  //设置保存目录
            );
            $up = new \Think\Upload($cfg);
            $z =$up -> upload(array('goods_pics' => $_FILES["goods_pics"]));

            foreach($z as $k =>$v){
                $pics_big_name = $up->rootPath.$v["savepath"].$v["savename"];

                /*****根据大图,制作缩略图*****/
                $im = new \Think\Image();
                $im -> open($pics_big_name);    //打开原图
                $im -> thumb(60,60);     //缩略图大小

                //缩略图名字：“small_原图名字”
                $pics_small_name = $up->rootPath.$v['savepath']."small_".$v['savename'];
                $im -> save($pics_small_name);  //存储缩略图到服务器
                /*****根据大图,制作缩略图*****/

                $arr = array(
                    'goods_id' => $data['goods_id'],
                    'pics_big' => $pics_big_name,
                    'pics_small' => $pics_small_name,
                );

                D('GoodsPics')->add($arr);
            }
        }
    }

    /**
     * 获取列表,带有分页类的
     * @return array
     */
    function fetchData(){
        //获取商品总条数
        $total = $this -> count();
        $per = 5;

        //实例化分页类page对象
        $page = new \Common\Tools\Page($total,$per);

        //获得分页信息
        $pageinfo = $this->order('goods_id desc')->limit($page->offset,$per)->select();

        //获得页码列表信息
        $pagelist = $page->fpage();

        return array(
            'pageinfo'=>$pageinfo,
            'pagelist'=>$pagelist
        );
    }

    /**
     * 更新数据前的回调方法
     * @param $data
     * @param $options
     */
    protected function _before_update(&$data,$options)
    {
        /****************logo图片处理start******************/
        //判断是否有上传logo图片，并做处理
        if ($_FILES['goods_logo_upd']['error'] === 0) {
            //1)删除该商品原先的物理图片
            $logoinfo = $this->field('goods_big_logo,goods_small_logo')->find($options['where']['goods_id']);
            if (!empty($logoinfo['goods_big_logo']) || !empty($logoinfo['goods_small_logo'])) {
                unlink($logoinfo['goods_big_logo']);
                unlink($logoinfo['goods_small_logo']);
            }

            //1.通过Think/Upload.class.php实现附件上传
            $cfg = array(
                "rootPath" => "./Common/Uploads/",  //设置保存目录
            );
            $up = new \Think\Upload($cfg);
            $z =$up -> uploadOne($_FILES["goods_logo_upd"]);
            //拼装图片路径
            $big_path_name = $up->rootPath.$z["savepath"].$z["savename"];
            $data['goods_big_logo'] = $big_path_name;

            //2.制作缩略图
            $im = new \Think\Image();
            $im -> open($big_path_name);    //打开原图
            $im -> thumb(60,60);     //缩略图大小
            //缩略图名字：“small_原图名字”
            $small_path_name = $up->rootPath.$z['savepath']."small_".$z['savename'];
            $im -> save($small_path_name);  //存储缩略图到服务器
            //保存缩略图到数据库
            $data['goods_small_logo'] = $small_path_name;
        }
        /****************logo图片处理end******************/

        /****************相册图片处理start******************/
        //判断相册图片
        //上传相册图片判断（只要有一个相册上传，就往下进行）
        $flag = false;
        foreach($_FILES['goods_pics_upd']['error'] as $a => $b){
            if($b === 0){
                $flag = true;
                break;
            }
        }
        if($flag === true){
            //商品相册图片上传
            $cfg = array(
                'rootPath'      =>  './Common/Pics/', //保存根路径
            );
            //dump($_FILES);
            $up = new \Think\Upload($cfg);
            $z = $up -> upload(array('goods_pics_upd'=>$_FILES['goods_pics_upd']));
            //通过返回值$z可以看到对应的上传ok的附件信息

            //遍历$z,获得每个附件的信息，存储到数据表中goods_pics
            foreach($z as $k => $v){
                $pics_big_name = $up->rootPath.$v['savepath'].$v['savename'];

                /******根据大图，制作缩略图******/
                $im = new \Think\Image();//实例化对象
                $im -> open($pics_big_name); //打开原图
                $im -> thumb(60,60); //制作缩略图
                //缩略图名字：“small_原图名字”
                $pics_small_name = $up->rootPath.$v['savepath']."small_".$v['savename'];
                $im -> save($pics_small_name);//存储缩略图到服务器
                /******根据大图，制作缩略图******/

                $arr = array(
                    'goods_id' => $options['where']['goods_id'],
                    'pics_big' => $pics_big_name,
                    'pics_small' => $pics_small_name,
                );
                //实现相册存储
                D('GoodsPics')->add($arr);
            }
        }
        /****************相册图片处理end******************/

    }


}