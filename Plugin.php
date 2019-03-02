<?php
/**
 * LoginWeb 
 * 
 * @package LoginWeb 
 * @author kumaomao
 * @version 1.0.0
 * @link 
 */
class LoginWeb_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('admin/login.php')->loginWeb = array('LoginWeb_Plugin', 'login');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
       
        $web_title = new Typecho_Widget_Helper_Form_Element_Text('web_title', NULL, '后台登录', _t('登录页面标题'));
        $form->addInput($web_title);
		$login_title = new Typecho_Widget_Helper_Form_Element_Text('login_title', NULL, '后台登录', _t('登录框名称'));
        $form->addInput($login_title);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function login()
    {
    	//获取项目路径
    	 $path = Helper::options()->pluginUrl;
		 $login_path = $path.'/LoginWeb/login';

		 $html = file_get_contents(dirname(__FILE__).'/login/index.html');
		 $html = self::change_html($html, $login_path);
		return $html;
    	
		
    }
	private static function get_login_url(){
		 $rootUrl = defined('__TYPECHO_ROOT_URL__') ? __TYPECHO_ROOT_URL__ : Helper::options()->request->getRequestRoot();
		 if (defined('__TYPECHO_ADMIN__')) {
            /** 识别在admin目录中的情况 */
            $adminDir = '/' . trim(defined('__TYPECHO_ADMIN_DIR__') ? __TYPECHO_ADMIN_DIR__ : '/admin/', '/');
            $rootUrl = substr($rootUrl, 0, - strlen($adminDir));
        }
   
		$login_url = Typecho_Widget::widget('Widget_Security')->getTokenUrl(
            Typecho_Router::url('do', array('action' => 'login', 'widget' => 'Login'),
            Typecho_Common::url('index.php', $rootUrl)));
		return $login_url;
	}
	private static function get_referer(){
		$request = Helper::options()->request;
		$referer = $request->get('referer');
		return $referer;
	}
	private static function change_html($html,$path){
		$themes_data = Typecho_Widget::widget('Widget_Options')->Plugin('LoginWeb');
		
		$html = str_replace('__PATH__', $path, $html);
		$html = str_replace('__TITLE__', $themes_data->web_title, $html);
		$html = str_replace('__LOGIN__', $themes_data->login_title, $html);
		$html = str_replace('__ACTION__', self::get_login_url(), $html);
		$html = str_replace('__REFERER__', self::get_referer(), $html);
		return $html;
	}
	
	/**
     * 通用CURL请求
     * @param $url  需要请求的url
     * @param null $data
     * return mixed 返回值 json格式的数据
     */
    private static function http_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $info = curl_exec($curl);
        curl_close($curl);
        return $info;
    }
}
