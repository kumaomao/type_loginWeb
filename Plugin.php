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
        $error_msg = new Typecho_Widget_Helper_Form_Element_Checkbox('error_msg',['使用自定义错误提示'],'0');
        $form->addInput($error_msg);
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
        $html = self::get_error_ts($html,$themes_data);


        return $html;
    }


    private static function set_script($msg){
        $options = Helper::options();
        $themes_data = Typecho_Widget::widget('Widget_Options')->Plugin('LoginWeb');
        //var_dump($error_msg);exit;
        $cookie = Typecho_Cookie::getPrefix();
        $index = substr(preg_quote(Typecho_Common::url('s', $options->index), '/'), 0, -1);
        $admin_url = preg_quote($options->adminUrl, '/');
        $path = Typecho_Cookie::getPath();
        $jquery = $options->adminStaticUrl('js').'/jquery.js';
        $typecho = $options->adminStaticUrl('js').'/typecho.js';
        $jquery_ui = $options->adminStaticUrl('js').'/jquery-ui.js';
		$script_js = <<<eof
		<script>!window.jQuery && document.write("<script src=\"{$jquery}\">"+"</scr"+"ipt>");</script>
		<script src="{$jquery_ui}"></script>
		<script src="{$typecho}"></script>
eof;
        $script = <<<eof
		<script>
		var error_msg='';
    (function () {
        $(document).ready(function() {
            // 处理消息机制
            (function () {
                var prefix = '{$cookie}',
                    cookies = {
                        notice      :   $.cookie(prefix + '__typecho_notice'),
                        noticeType  :   $.cookie(prefix + '__typecho_notice_type'),
                        highlight   :   $.cookie(prefix + '__typecho_notice_highlight')
                    },
                    path = '{$path}';

                if (!!cookies.notice && 'success|notice|error'.indexOf(cookies.noticeType) >= 0) {
                
                   {$msg}

                    $.cookie(prefix + '__typecho_notice', null, {path : path});
                    $.cookie(prefix + '__typecho_notice_type', null, {path : path});
                }

                if (cookies.highlight) {
                    $('#' + cookies.highlight).effect('highlight', 1000);
                    $.cookie(prefix + '__typecho_notice_highlight', null, {path : path});
                }
            })();


            // 导航菜单 tab 聚焦时展开下拉菜单
            (function () {
                $('#typecho-nav-list').find('.parent a').focus(function() {
                    $('#typecho-nav-list').find('.child').hide();
                    $(this).parents('.root').find('.child').show();
                });
                $('.operate').find('a').focus(function() {
                    $('#typecho-nav-list').find('.child').hide();
                });
            })();


            if ($('.typecho-login').length == 0) {
                $('a').each(function () {
                    var t = $(this), href = t.attr('href');

                    if ((href && href[0] == '#')
                        || /^{$admin_url}.*$/.exec(href) 
                            || /^{$index}action\/[_a-zA-Z0-9\/]+.*$/.exec(href)) {
                        return;
                    }

                    t.attr('target', '_blank');
                });
            }
        });
    })();

</script>
		
eof;
        return ['js'=>$script_js,'function'=>$script];
    }

    private static function get_error_ts($html,$data){
        $error_msg = $data->error_msg;

        if($error_msg === 0 || is_array($error_msg)){
            if(strpos('<ts>') !== false){
                if(strpos('__TS__') !== false){
                    $html = str_replace('__TS__', '$.parseJSON(cookies.notice)[0]', $html);
                    $msg = self::getSubstr($html,'<ts>','</ts>');
        
                    $old = '<ts>'.$msg.'</ts>';
                    $html = str_replace($old, '', $html);
                }else{
                    $error_msg = self::getSubstr($html,'<ts>','</ts>');
                    $old = '<ts>'.$error_msg.'</ts>';
                    $html = str_replace($old, '', $html);
                }
            }

        }
		$srcipt = self::set_script($msg);
		$js= $srcipt['js'];
		$funciton = $srcipt['function'];
		 $html = str_replace('__SCRIPTJS__',$js , $html);
        $html = str_replace('__SCRIPT__',$funciton , $html);
        return $html;
    }


    private static function msg_js(){
        $msg = <<<eof
	 var head = $('.typecho-head-nav'),
                        p = $('<div class="message popup ' + cookies.noticeType + '">'
                        + '<ul><li>' + $.parseJSON(cookies.notice).join('</li><li>') 
                        + '</li></ul></div>'), offset = 0;
						//console.log($.parseJSON(cookies.notice));

                    if (head.length > 0) {
                        p.insertAfter(head);
                        offset = head.outerHeight();
                    } else {
                        p.prependTo(document.body);
                    }

                    function checkScroll () {
                        if ($(window).scrollTop() >= offset) {
                            p.css({
                                'position'  :   'fixed',
                                'top'       :   0
                            });
                        } else {
                            p.css({
                                'position'  :   'absolute',
                                'top'       :   offset
                            });
                        }
                    }

                    $(window).scroll(function () {
                        checkScroll();
                    });

                    checkScroll();

                    p.slideDown(function () {
                        var t = $(this), color = '#C6D880';
                        
                        if (t.hasClass('error')) {
                            color = '#FBC2C4';
                        } else if (t.hasClass('notice')) {
                            color = '#FFD324';
                        }

                        t.effect('highlight', {color : color})
                            .delay(5000).fadeOut(function () {
                            $(this).remove();
                        });
                    });
eof;
        return $msg;
    }



    private static function getSubstr($str, $leftStr, $rightStr){

        $left = strpos($str, $leftStr);
		//  echo '左边:'.$left;
		$right = strpos($str, $rightStr,$left);
		// echo '<br>右边:'.$right;
		if($left < 0 or $right < $left) return '';
		return substr($str, $left + strlen($leftStr), $right-$left-strlen($leftStr));
	}
}
