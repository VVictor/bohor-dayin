$(function(){
    $.pjax.defaults.timeout = 5000;
    $.pjax.defaults.maxCacheLength = 0;
    
    toastr.options = {
        closeButton: true,                  //是否显示关闭按钮
        debug: false,                       //是否使用debug模式
        progressBar: true,                  //是否显示进度条
        positionClass: "toast-top-right",   //弹出窗的位置
        showDuration: "300",                //显示动作时间
        preventDuplicates: true,            //提示框只出现一次
        hideDuration: "300",                //隐藏动作时间
        timeOut: "3000",                    //自动关闭超时时间
        extendedTimeOut: "1000",            ////加长展示时间
        showEasing: "swing",                //显示时的动画缓冲方式
        hideEasing: "linear",               //消失时的动画缓冲方式
        showMethod: "fadeIn",               //显示时的动画方式
        hideMethod: "fadeOut"               //消失时的动画方式
    };
    
    //退出登录
    $('body').off('click', '.login_out');
    $('body').on("click", '.login_out', function(event){
        var url = $(this).attr('href');
        window.location.href=url;
        return false;
    });
    
    $(document).pjax('a:not(a[target="_blank"])', {container:'#pjax-container', fragment:'#pjax-container'});
    
    $(document).on('submit', 'form[pjax-search]', function(event) {
        var _this = $(this);
        $.pjax.submit(event, {container:'#pjax-container', fragment:'#pjax-container'});
        _this.find('input[name="k"]').val('');
    })
    
    $(document).on('pjax:send', function() { NProgress.start(); });
    $(document).on('pjax:complete', function() { NProgress.done(); });
    
    //提交
    $('body').off('click', '.submits');
    $('body').on("click", '.submits', function(event){
        var _this = $(this);
        _this.button('loading');
        var form = _this.closest('form');
        if(form.length){
            var ajax_option={
                dataType:'json',
                success:function(data){
                    if(data.status == '1'){
                        toastr.success(data.info);
                        var url = data.url;
                        $.pjax({url: url, container: '#pjax-container', fragment:'#pjax-container'})
                    }else{
                        _this.button('reset');
                        toastr.warning(data.info);
                    }
                }
            }
            form.ajaxSubmit(ajax_option);
        }
    });
    
    //注册-登录
    $('body').off('click', '.login-btn');
    $('body').on("click", '.login-btn', function(event){
        var _this = $(this);
        _this.button('loading');
        var form = _this.closest('form');
        if(form.length){
            var ajax_option={
                dataType:'json',
                success:function(data){
                    if(data.status == '1'){
                        toastr.success(data.info);
                        var url = data.url;
                        window.location.href=url;
                    }else{
                        _this.button('reset');
                        $('#code').click();
                        toastr.warning(data.info);
                    }
                }
            }
            form.ajaxSubmit(ajax_option);
        }
    });
    
    //回复评论框
    $('body').off('click', '.arc-btn');
    $('body').on("click", '.arc-btn', function(event){
        var _this = $(this);
        var _form = $('.arc-form');
        if(_this.hasClass('arc-reply')){   //取消回复
            _this.html('回复').removeClass('btn-danger arc-reply');
            $('.guestbook_box').append(_form);
            _form.find('input[name="ruid"]').val(0);
            _form.find('input[name="cid"]').val(0);
        }else{   //回复
            $('.arc-btn').html('回复').removeClass('btn-danger arc-reply');   //其他按钮还原
            _this.html('取消回复').addClass('btn-danger arc-reply');
            var _item = _this.closest('.item');
            _this.after(_form);
            _form.find('input[name="ruid"]').val(_this.data('uid'));
            _form.find('input[name="cid"]').val(_this.data('cid'));
        }
    });
    
    //文章点赞
    $('body').off('click', '.arc-thumbs-up');
    $('body').on("click", '.arc-thumbs-up', function(event){
        var _this = $(this);
        var id = _this.data('id');
        _this.attr('disabled',"true");
        $.ajax({
            type : "get",
            url : '/index/arc_thumbsup/likes/id/'+id,
            dataType : 'json',
            success : function(data) {
                if(data.status == '1'){
                    _this.find('span').html(data.data);
                    toastr.success(data.info);
                }else{
                    toastr.warning(data.info);
                }
                $(".tooltip.fade.top.in").remove();
            }
        });
    });
})

/*返回顶部*/
$(window).scroll(function(){
    var sc=$(window).scrollTop();
    var rwidth=$(window).width()+$(document).scrollLeft();
    var rheight=$(window).height()+$(document).scrollTop();
    if(sc>0){
        $("#goTop").css("display","block");
    }else{
        $("#goTop").css("display","none");
    }
});
$("#goTop").click(function(){
    $('body,html').animate({scrollTop:0},300);
});
/*返回顶部*/

/*手机固定*/
$(window).resize(function(){
    if($(window).width() < 500){
        $('body').removeClass("layout-boxed").addClass("fixed");
    }
});
if($(window).width() < 500){
    $('body').removeClass("layout-boxed").addClass("fixed");
}
/*手机固定*/