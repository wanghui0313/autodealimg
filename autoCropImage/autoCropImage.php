<?php
#phpinfo();
ini_set("memory_limit", "-1"); 
date_default_timezone_set("Asia/Shanghai");
error_reporting(E_ALL);

/* 生成并输出图片 */
require 'Imagedeal.php';
/* 当前目录 */
define('ROOT_DIR', dirname(__FILE__));
/* 项目配置 */
require ROOT_DIR . '/_config.php';
/* 初始化 */
$autoCropImage = new autoCropImage();

/* 设置头信息 */
$autoCropImage->set_header();

/* 获取宽高,缩放模式,水印位置，水印透明度,水印url地址,版本 */
list($width, $height, $mode, $bc,$waterloc,$watertrans,$waterurl, $versions) = $autoCropImage->width_height_mode_versions();

/* 判断生成逻辑 */
require ROOT_DIR . '/_auth.php';

/* 获取文件路径 */
$path = $autoCropImage->path();

/* 源文件 */
$old = ROOT_DIR . '/../' . $path;

/* 指定规格文件 */
#有水印,有缩略
if ($width && $waterloc)
{
    if ($bc!=DEFAULT_BACKGROUNDCOLOR)
    {
        $basename_arr = explode('.', basename($path));
        $bc_arr = explode(",",$bc);
        $basename = $basename_arr[0].'bc_'.$bc_arr[0].'_'.$bc_arr[1].'_'.$bc_arr[2].'w'.$waterloc.'_'.$watertrans.'_'.md5($_SERVER['REQUEST_URI']).'.'.$basename_arr[1];
    }else{
        $basename_arr = explode('.', basename($path));
        $basename = $basename_arr[0].'w'.$waterloc.'_'.$watertrans.'_'.md5($_SERVER['REQUEST_URI']).'.'.$basename_arr[1];
    }
    $new = sprintf(THUMB_DIR, $width, $height, $mode, $versions, dirname($path), $basename);
}elseif ($width && !$waterloc)
{
    #只有缩略
    $basename = basename($path);
    if ($bc!=DEFAULT_BACKGROUNDCOLOR)
    {
        $basename_arr = explode('.', basename($path));
        $bc_arr = explode(",",$bc);
        $basename = $basename_arr[0].'bc_'.$bc_arr[0].'_'.$bc_arr[1].'_'.$bc_arr[2].'.'.$basename_arr[1];
    }
    $new = sprintf(THUMB_DIR, $width, $height, $mode, $versions, dirname($path), $basename);
}elseif(!$width && $waterloc)
{
    #只有水印
    $basename_arr = explode('.', basename($path));
    $new = sprintf(WATER_DIR, $waterloc, $watertrans,md5($_SERVER['REQUEST_URI']).'.'.$basename_arr[1]);
}

/* 存在源文件 */
if (file_exists($old))
{
    /* 不存指定规格文件夹 */
    if (!file_exists(dirname($new)))
    {
        $autoCropImage->mk_dir(dirname($new));
    }
    /* 不存指定规格文件 */
    if (!file_exists($new))
    {
        #缩略相关操作
        if ($width) {
            $image = new Image();
       	    $image = $image->open($old);
	   switch ($mode)
            {
                case 1:
                    //固定大小缩略,图可能会有所变形
                    $image->thumb($width, $height,$image::IMAGE_THUMB_FIXED)->save($new);
                    break;
                case 2:
                    //等比例缩放
                    $image->thumb($width, $height)->save($new);
                    break;
                case 3:
                    //缩放填充
                    $image->thumb($width, $height,$image::IMAGE_THUMB_FILLED,$bc)->save($new);
                    break;
                case 4:
                    //从左上角开始裁减指定宽高
                    $image->crop($width, $height)->save($new);
                    break;
                default:
                    $image->thumb($width, $height,$image::IMAGE_THUMB_FIXED)->save($new);
                    break;
            }
        }

        #水印相关操作
        if ($waterloc)
        {
            if (!file_exists($new))
            {
                $openfile = $old;
            }else{
                $openfile = $new;
            }
            $spider = new Spider();
            $logo = $spider->getImage($waterurl,'../logos');
            if($logo['error'] == 0)
            {
                $img = new Image();
                $img->open($openfile)->water($logo['save_path']?$logo['save_path']:'',$waterloc,$watertrans*10)->save($new); 
            }else{
                $autoCropImage->show_not_found();
            }
        }
    }
    file_exists($new) && $autoCropImage->show_pic($new);
}
/* 其它处理 */
$autoCropImage->show_not_found();

#下载图片
class Spider {
    function getImage($url,$save_dir='../logos',$filename='',$type=0){
        if(trim($url)==''){
            return array('file_name'=>'','save_path'=>'','error'=>1);
        }
        if(trim($save_dir)==''){
            $save_dir='./';
        }
        if(trim($filename)==''){//保存文件名
            $ext=strrchr($url,'.');
            $filename=md5($url).$ext;
        }
        if (file_exists($save_dir.'/'.$filename)) {
            return array('file_name'=>$filename,'save_path'=>$save_dir.'/'.$filename,'error'=>0);
        }
        if(0!==strrpos($save_dir,'/')){
            $save_dir.='/';
        }
        //创建保存目录
        if(!file_exists($save_dir)&&!mkdir($save_dir,0777,true)){
            return array('file_name'=>'','save_path'=>'','error'=>5);
        }
        //获取远程文件所采用的方法
        if($type){
            $ch=curl_init();
            $timeout=5;
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
            $img=curl_exec($ch);
            curl_close($ch);
        }else{
            ob_start();
            readfile($url);
            $img=ob_get_contents();
            ob_end_clean();
        }
        $size = strlen($img);
        if ($size) {
            $fp2=@fopen($save_dir.$filename,'a');
            fwrite($fp2,$img);
            fclose($fp2);
            unset($img,$url);
            return array('file_name'=>$filename,'save_path'=>$save_dir.$filename,'error'=>0);
        }else{
            return array('file_name'=>'','save_path'=>'','error'=>6);
        }
    }
}

class autoCropImage
{
    /**
     * 设置头信息
     * @access public
     * @return void
     */
    public function set_header()
    {
        header('Expires: ' . date('D, j M Y H:i:s', strtotime('now + ' . HEADER_CACHE_TIME)) .' GMT');
        $etag = md5(serialize($this->from($_SERVER, 'QUERY_STRING')));
        if ($this->from($_SERVER, 'HTTP_IF_NONE_MATCH') === $etag)
        {
            header('Etag:' . $etag, true, 304);
            exit;
        } else {
            header('Etag:' . $etag);
        }
    }

    /**
     * 获取请求路径
     * 
     * @access public
     * @return string
     */
    public function path()
    {
        $path = $this->_str_replace_once($this->_str_replace_once('autoCropImage/autoCropImage.php', '', $this->from($_SERVER, 'SCRIPT_NAME')), '', $this->_str_replace_once('?' . $this->from($_SERVER, 'QUERY_STRING'), '', $this->from($_SERVER, 'REQUEST_URI')));
        return preg_replace('/(?:_)([0-9]+)x([0-9]+)(?:m([1-5]))?(?:v([A-Za-z0-9_]*))?(?:.)?(?:gif|jpg|png|GIF|JPG|PNG)?$/', '', $path);
    }
    
    /**
     * 子字符串替换一次
     * @access public
     * @param string $needle
     * @param string $replace
     * @param string $haystack
     * @return string
     */
    public function _str_replace_once($needle, $replace, $haystack) {
        $pos = strpos($haystack, $needle);
        if ($pos === false) {
            return $haystack;
        }
        return substr_replace($haystack, $replace, $pos, strlen($needle));
    }
    
    /**
     * 获取宽高、缩放模式和版本
     * 
     * @access public
     * @return array($width, $height, $mode, $versions)
     */
    public function width_height_mode_versions()
    {
        if ($query_string = $this->from($_SERVER, 'QUERY_STRING'))
        {
            if (preg_match('/^(?:([0-9]+)x([0-9]+)(?:m([1-5])(?:bc([0-9,]+))?)?)?(?:w([0-9]+)-([0-9]+)-(.+))?(?:v([A-Za-z0-9_]*))?$/', $query_string, $match))
            {
                return array($match[1], $match[2], $this->from($match, 3, DEFAULT_MODE, TRUE),
                    $this->from($match, 4, DEFAULT_BACKGROUNDCOLOR, TRUE),$this->from($match, 5, '', TRUE),
                    $this->from($match, 6, '', TRUE),$this->from($match, 7, 1, TRUE),
                    $this->from($match, 8, DEFAULT_VERSIONS, TRUE));
            }
        }
        if (file_exists(ROOT_DIR.'/..'.$_SERVER['REQUEST_URI'])) {
            return $this->show_pic(ROOT_DIR.'/..'.$_SERVER['REQUEST_URI']);
        }else{
            return $this->show_not_found();
        }
    }

    /**
     * 输出图片
     * 
     * @access public
     * @param mixed $file
     * @return void
     */
    public function show_pic($file)
    {
        $img = file_get_contents($file,true);
        //使用图片头输出浏览器
        header("Content-Type: image/jpeg;text/html; charset=utf-8");
        echo $img;
        exit;
    }

    /**
     * 404 Not Found 输出
     * 
     * @access public
     * @return void
     */
    public function show_not_found()
    {
        header($this->from($_SERVER, 'SERVER_PROTOCOL') . ' 404 Not Found');
        $img = imagecreate(1, 1);
        imagecolorallocate($img, 0xee, 0xee, 0xee);
        header('Content-Type: image/gif');
        imagegif($img);
        exit;
    }

    /**
     * 递归创建目录
     * 
     * @access public
     * @param mixed $dir
     * @param int $mode
     * @return bool
     */
    public function mk_dir($dir, $mode = 0777) 
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) return true; 
    if (!$this->mk_dir(dirname($dir), $mode)) return false; 
        return @mkdir($dir, $mode); 
    }
    
    /**
     * 获得数组指定键的值
     * 
     * @access public
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @param bool $check_empty
     * @return mixed
     */
    public function from($array, $key, $default = FALSE, $check_empty = FALSE)
    {
        return (isset($array[$key]) === FALSE OR ($check_empty === TRUE && empty($array[$key])) === TRUE) ? $default : $array[$key];
    }
}
