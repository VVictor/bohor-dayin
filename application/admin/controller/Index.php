<?php
namespace app\admin\controller;

use app\common\controller\SysAction;
use Request;
use think\Db;
use think\helper\Time;
use think\facade\Env;
use app\common\model\Config;

class Index extends SysAction
{
  public function initialize()
  {
      parent::initialize();
  }
    
  public function index()
  {
		$data = Request::instance()->get();

    if ($data['list'] && $data['list'] != '显示条数') {
      $lis = $data['list'];
    }else{
      $lis = 20;
    } 
    	$db = Db::table('tf_detail')->group('f_ID,f_single')->field('sum(f_userprice) as f_price,f_ID,f_NAME,f_single,f_membertype,f_tax,f_userprice');
        if ($data['f_time'] ){
        	$time = explode('~', $data['f_time']);
            $db->whereTime('f_CREATETIME', 'between', [$time[0] , $time[1] ]);
        }
        if ($data['f_ID']) {
                $where[] = ['f_ID|f_single','like','%'.$data['f_ID'].'%'];
        	$db->where($where);
        }
 		   $list = $db->order('f_single asc')->paginate($lis, false, page_param());
 		   
       $zheji = 0;
      if ($list->toArray()) {
        $list->toArray();
        foreach ($list as $k => $v) {
          $data = array();
          $data = $v;
          $lart = Db::table('tf_detail')->where('f_ID',$v['f_ID'])->where('f_single',$v['f_single'])->select();
          $f_userprice = Db::table('tf_detail')->where('f_ID',$v['f_ID'])->where('f_single',$v['f_single'])->sum('f_userprice');
          

          foreach ($lart as $key => $value) {
            $jine1 = sprintf("%.2f", $value['f_userprice']/(1+$value['f_tax']*0.01)); //金额
            $shuie1 = sprintf("%.2f", $value['f_userprice']-$jine1); //税额
           
            $zheji= $zheji+$jine1;
            $zheji= $zheji+$shuie1;
            $pr = Db::table('tf_card')->where('name',$value['f_membertype'])->field('principal,rate')->find();
              if (!empty($pr['rate']) ) {
                if ($value['f_userprice'] != 0) {
                $zhekoujine =  round($f_userprice*$pr['rate'],2);    //折扣金额
                if (floatval($value['f_userprice']) == 0) {
                 $jine = 0;
                 $shuie = 0;
                }else{
                $jeb = round(($value['f_userprice']/$f_userprice)*$zhekoujine,2); //折扣分摊金额
                $jine = round($jeb / (1+$value['f_tax']*0.01),2); //金额
                $shuie = $jeb-$jine; //税额
                }
                $c = explode('.', $jine);
                $d = explode('.', $shuie);
                if ($c[0] != 0 || $c[1] != 0) {
                  $jine = '-'.$jine;
                }
                if ($d[0] != 0 || $d[1] != 0) {
                  $shuie = '-'.$shuie;
                }
                $zheji= $zheji+$jine;
                $zheji= $zheji+$shuie;
              }
              }
          }
       		if($zheji >= 0){
       		
          $data['heji'] = $zheji;
          }else{
          $data['heji'] = 0;
          }
          $zheji = 0;
          $list->offsetSet($k,$data);
        }
      } 
      
        $this->assign('page',$data);
 		    $this->assign('list',$list);
        return $this->fetch('index');
  }

  public function see($id,$f_single)
  {
      $data = Db::table('tf_detail')->where('f_ID',$id)->where('f_single',$f_single)->select();
      $nubmer = Db::table('tf_detail')->where('f_ID',$id)->where('f_single',$f_single)->find();
      $f_userprice = Db::table('tf_detail')->where('f_ID',$id)->where('f_single',$f_single)->sum('f_userprice');
      $nums = explode('-', $nubmer['f_single']);
      $numc = $nums['1']; //流水号 NO.$num
      $vipnum = $id; //会员卡号 会员名称
      $date = date('Y年m月d日',time()); //开票日期
      $liushui = $f_single; //账单流水号
      $left_id = Db::table('tf_detail')->where('f_ID', ['>',$id], ['=',$id], 'or')->where('f_single','<>',$f_single)->order("f_ID", "asc")->find();
      $right_id = Db::table('tf_detail')->where('f_ID', ['<',$id], ['=',$id], 'or')->where('f_single','<>',$f_single)->order("f_ID", "asc")->find();
      $list = [];
      $num = 0;
      $hejijine = 0;
      $hejishuie = 0;
      foreach ($data as $key => $value) {
          $list[$num][0]['jine'] = sprintf("%.2f", $value['f_userprice']/(1+$value['f_tax']*0.01)); //金额
          $list[$num][0]['danjia'] = sprintf("%.6f",$list[$num][0]['jine']/$value['f_quantity']); //单价
          $list[$num][0]['shuilv'] = $value['f_tax'];
          if ($list[$num][0]['jine'] <= 0) {
              $list[$num][0]['danjia'] = '';
          }
          $list[$num][0]['shuie'] = sprintf("%.2f", $value['f_userprice']-$list[$num][0]['jine']); //税额
          $list[$num][0]['f_entryname'] = $value['f_entryname']; //项目名称
          $list[$num][0]['shuliang'] = $value['f_quantity']; //项目数量
          $hejijine =  $hejijine+$list[$num][0]['jine']; //合计金额
          $hejishuie = $hejishuie+$list[$num][0]['shuie'];//合计税额
        $pr = Db::table('tf_card')->where('name',$value['f_membertype'])->field('principal,rate')->find();
        if (!empty($pr['rate']) ) {
          if ($value['f_userprice'] != 0) {
          $list[$num][1]['f_entryname'] = $value['f_entryname']; //项目名称
          $list[$num][1]['shuliang'] = ''; //项目数量
          $list[$num][1]['danjia'] = ''; //项目单价
          $list[$num][1]['shuilv'] = $value['f_tax'];
          $zhekoujine =  round($f_userprice*$pr['rate'],4);    //折扣金额
          if (floatval($value['f_userprice']) == 0) {
           $jine = 0;
           $shuie = 0;
          }else{
          $jeb = round(($value['f_userprice']/$f_userprice)*$zhekoujine,2); //折扣分摊金额
          $jine = round($jeb / (1+$value['f_tax']*0.01),2); //金额
          $shuie = $jeb-$jine; //税额
          }
          $c = explode('.', $jine);
          $d = explode('.', $shuie);
          if ($c[0] != 0 || $c[1] != 0) {
            $jine = '-'.$jine;
          }
          if ($d[0] != 0 || $d[1] != 0) {
            $shuie = '-'.$shuie;
          }
          $list[$num][1]['shuie'] = $shuie;
          $list[$num][1]['jine'] = $jine;
        $hejijine = $hejijine+$list[$num][1]['jine']; //合计金额
        $hejishuie = $hejishuie+$list[$num][1]['shuie']; //合计税额
        }
        }
        $num = $num+1;

      }
      $zongji = $hejijine+$hejishuie;
      $hzongji = $this->num_to_rmb($zongji);
      $this->assign('hzongji',$hzongji);
      $this->assign('liushui',$liushui);
      $this->assign('zongji',$zongji);
      $this->assign('hejijine',$hejijine);
      $this->assign('hejishuie',$hejishuie);
      $this->assign('vipnum',$vipnum);
      $this->assign('nubmer',$nubmer);
      $this->assign('numc',$numc);
      $this->assign('list',$list);
      $this->assign('date',$date);
      $this->assign('left_id',$left_id);
      $this->assign('right_id',$right_id);
      return $this->fetch();
  }


public function printing()
  {
        $ret = Request::instance()->post();
        $data = Db::table('tf_detail')->where('f_ID',$ret['id'])->where('f_single',$ret['f_single'])->select();
        $nubmer = Db::table('tf_detail')->where('f_ID',$ret['id'])->where('f_single',$ret['f_single'])->find();
        $f_userprice = Db::table('tf_detail')->where('f_ID',$ret['id'])->where('f_single',$ret['f_single'])->sum('f_userprice');
        $nums = explode('-', $nubmer['f_single']);
        $numc = $nums['1']; //流水号 NO.$num
        $vipnum = $ret['id']; //会员卡号 会员名称
        $date = date('Y年m月d日',time()); //开票日期
        $liushui = $ret['f_single']; //账单流水号
        $zjine = 0;
        $zshuie =0;
        $list = [];
        $str = "";
        $str.= "<div class='title' style='width:95%; margin:0 auto; overflow: hidden;padding-bottom: 20px;font-family: 楷体;'>
    <div class='title-left' style='float: left;width: 64%;font-size: 44px;text-align: right;margin-top: 18px;'>销售发票</div>
    <div class='title-right' style='float: right;'>
      <p style='font-size: 18px;margin-top:15px;margin-bottom:15px;'>NO.".$numc."</p>
      <p style='font-size: 12px;'>开票日期：".$date."</p>
    </div>
    </div>
    <div class='table' style='width: 100%;margin:0 auto;border: 1px solid #000;height: 870px;font-family: 楷体;'> 
     <div class='buyer' style='overflow: hidden;height: 85px;border-bottom: 1px solid #000;'>
            <div class='buyer-tit' style='float: left;width:26%;height: 85px;text-align: center;line-height: 85px;border-right: 1px solid #000;'>
                <span style='font-size: 36px;'>购买方</span>
            </div>
            <div class='buy-cont' style='padding-top: 3px;padding-left: 8px;float: left;'>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>名&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;称：</span>
                    <span>".$nubmer['f_NAME']."(".$vipnum.")</span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>纳税人识别号：</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>地&nbsp;址、电&nbsp;话：</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>开户行及账号：</span>
                    <span></span>
                </p>
            </div>
        </div>
        <div style='height:588px;position:relative;'>
         <div style='overflow:hidden;font-size:12px;'>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:26%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>货物或应税劳务、服务名称</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:7%;border-right:1px solid #000;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>规格型号</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:4%;border-right:1px solid #000;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>单位</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:11%;border-right:1px solid #000;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>数量</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:13%;border-right:1px solid #000;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>单价</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:16%;border-right:1px solid #000;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>金额</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:6%;border-right:1px solid #000;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>税率</span></p>
            <p style='float:left;margin: 0;width:17%;padding-top:3px;padding-bottom:3px;text-align: center;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>税额</span></p>
        </div>";
          $num = 0;
          $zheji = 0;
          foreach ($data as $key => $value) {
                  $jine = sprintf("%.2f", $value['f_userprice']/(1+$value['f_tax']*0.01)); //金额
                  $Unitprice = sprintf("%.6f",$jine/$value['f_quantity']); //单价
                  $zheji += $jine;
                  if ($jine <= 0) {
                      $Unitprice = '';
                  }
                  $shuie =sprintf("%.2f", $value['f_userprice']-$jine); //税额
                  $zheji+=$shuie;
                  if (   $key == 0  || !is_int(count($list)/30)) {
                        $str.="<div class='div1' style='overflow:hidden;font-size:12px;'>
               <div style='float:left;margin: 0;text-align: left;width:26%;border-right:1px solid #000;padding-top:2px;padding-bottom:2px;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;margin-left:-13px;'>".$value['f_entryname']."</span></div>
               <div style='float:left;margin: 0;min-height:1px;width:7%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;min-height:1px;width:4%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;width:11%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$value['f_quantity']."</span></div>
               <div style='float:left;margin: 0;width:13%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$Unitprice."</span></div>
               <div style='float:left;margin: 0;width:16%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$jine."</span></div>
               <div style='float:left;margin: 0;width:6%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;'>".$value['f_tax']."%</span></div>
               <div style='float:left;margin:0;width:17%;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$shuie."</span></div>
               
           </div>";
                        $pr = Db::table('tf_card')->where('name',$value['f_membertype'])->field('principal,rate')->find();
                        if (!empty($pr['rate'])) {
                            if ($value['f_userprice'] != 0 && !empty($value['f_userprice'])) {
                        
                            $num = $num+1;
                            $list[$num] = 1;
                            $zhekoujine =  round($f_userprice*$pr['rate'],2);    //折扣金额
                            if(floatval($value['f_userprice']) == 0){
                                $zhje =0;
                                $zkse =0;
                            }else{
                                $jeb = round(($value['f_userprice']/$f_userprice)*$zhekoujine,2); //折扣分摊金额
                                $zhje = round($jeb / (1+$value['f_tax']*0.01),2); //金额
                                $zkse = $jeb-$zhje; //税额
                            }

                            $zjine = $zjine-$zhje;
                            $zshuie = $zshuie-$zkse;
                            $c = explode('.', $zhje);
                            $d = explode('.', $zkse);
                            if ($c[0] != 0 || $c[1] != 0) {
                              $zhje = '-'.$zhje;
                            }
                            if ($d[0] != 0 || $d[1] != 0) {
                              $zkse = '-'.$zkse;
                            }
                            $zheji = $zheji+$zhje;
                            $zheji = $zheji+$zkse;
                          $str.="<div class='div1' style='overflow:hidden;font-size:12px;'>
               <div style='float:left;margin: 0;text-align: left;width:26%;border-right:1px solid #000;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-left:-13px;'>".$value['f_entryname']."</span></div>
               <div style='float:left;margin: 0;min-height:1px;width:7%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;min-height:1px;width:4%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;width:11%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;width:13%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px'></span></div>
               <div style='float:left;margin: 0;width:16%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$zhje."</span></div>
               <div style='float:left;margin: 0;width:6%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;'>".$value['f_tax']."%</span></div>
               <div style='float:left;margin:0;width:17%;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$zkse."</span></div>
               
           </div>";  
                          }
                        }  
                        $num = $num+1;
                        $list[$num] = 1; 
                        $zjine = $zjine+$jine; //总金额
                        $zshuie = $zshuie+$shuie; //总税额
                        $heji = $zshuie+$zjine; //合计
                  }else{
                        $list = [];
                        $str.="<div style='position: absolute;top:0;width: 100%;overflow: hidden;font-size:12px;'>
            <div style='float:left;width:26%;border-right:1px solid #000;height: 588px;line-height: 1150px;text-align: center;'>合计</div>
            <div style='float:left;width: 7%;border-right: 1px solid #000;height: 588px;'>
             </div>
            <div style='float:left;width: 4%;border-right: 1px solid #000;height: 588px;'>
             </div>
            <div style='float:left;width: 11%;border-right: 1px solid #000;height: 588px;'>
             </div>
            <div style='float:left;width: 13%;border-right: 1px solid #000;height: 588px;'>
             </div>
            <div style='float:left;width: 16%;border-right: 1px solid #000;height: 588px;text-align: right;line-height: 1150px;padding-right:8px;'>
            ￥".$zjine."</div>
            <div style='float:left;width: 6%;border-right: 1px solid #000;height: 588px;'>
            </div>
            <div style='float:left;width: 17%;height: 588px;text-align: right;line-height: 1150px;padding-right:8px;'>".$zshuie."</div>
          </div>
                </div>
                <div class='amount' style='height: 40px;line-height:40px;overflow: hidden;border-top: 1px solid #000;font-size:12px;'>
            <p style='float: left;width: 26%;text-align: center;margin: 0;border-right: 1px solid #000;'>价税合计（大写）</p>
            <p style='float: left;margin: 0;width: 40%;padding-left: 5px;'>ⓧ".$this->num_to_rmb($heji)."</p>
            <p style='float: left;margin: 0;'>（小写）¥".$heji."</p>
        </div>
        <div class='sales' style='border-top: 1px solid #000;overflow: hidden;height: 155px;'>
            <div class='buyer-tit' style='float: left;width:26%;height: 155px;text-align: center;line-height: 155px;border-right: 1px solid #000;'>
                <span style='font-size: 36px;'>销售方</span>
            </div>
            <div class='buy-cont' style='padding-top: 3px;padding-left: 8px;float: left;width: 38%;'>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>名&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;称：</span>
                    <span>".$this->confv('mingcheng')."</span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>纳税人识别号：".$this->confv('nashui')."</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>地&nbsp;址、电&nbsp;话：".$this->confv('dizhi')."</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>开户行及账号：".$this->confv('zhanghu')."</span>
                    <span></span>
                </p>
            </div>
            <div class='remark' style='float: left;width: 40px;height:155px;text-align: center;
                  border-left: 1px solid #000;border-right: 1px solid #000;'>
                <span style='margin-top: 13px;display: block;'>备<br/><br/>注</span>
            </div>
            <div class='remark-txt' style='float: left;padding-left: 8px;padding-top: 20px;'>
                <span>账单流水号：".$liushui."</span>
            </div>
        </div>
        </div>
        <div style='page-break-after: always'></div><div class='title' style='width:95%; margin:0 auto; overflow: hidden;padding-bottom: 20px;font-family: 楷体;'>
        <div class='title-left' style='float: left;width: 64%;font-size: 44px;text-align: right;margin-top: 18px;'>销售发票</div>
        <div class='title-right' style='float: right;'>
            <p style='font-size: 18px;margin-top:15px;margin-bottom:15px;'>NO.".$numc."</p>
            <p style='font-size: 12px;'>开票日期：".$date."</p>
        </div>
      </div>
      <div class='table' style='width: 100%;margin:0 auto;border: 1px solid #000;height: 870px;font-family: 楷体;'> 
       <div class='buyer' style='overflow: hidden;height: 85px;border-bottom: 1px solid #000;'>
            <div class='buyer-tit' style='float: left;width:26%;height: 85px;text-align: center;line-height: 85px;border-right: 1px solid #000;'>
                <span style='font-size: 36px;'>购买方</span>
            </div>
            <div class='buy-cont' style='padding-top: 3px;padding-left: 8px;float: left;'>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>名&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;称：</span>
                    <span>".$nubmer['f_NAME']."(".$vipnum.")</span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>纳税人识别号：</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>地&nbsp;址、电&nbsp;话：</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>开户行及账号：</span>
                    <span></span>
                </p>
            </div>
        </div>
         <div style='height:588px;position:relative;'>
         <div style='overflow:hidden;font-size:12px;'>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:26%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>货物或应税劳务、服务名称</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:7%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>规格型号</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:4%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>单位</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:11%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>数量</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:13%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>单价</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:16%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>金额</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:6%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>税率</span></p>
            <p style='float:left;margin: 0;width:17%;padding-top:3px;padding-bottom:3px;text-align: center;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>税额</span></p>
        </div>";
                        $str.="<div class='div1' style='overflow:hidden;font-size:12px;'>
               <div style='float:left;margin: 0;text-align: left;width:26%;border-right:1px solid #000;padding-top:2px;padding-bottom:2px;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;margin-left:-13px;'>".$value['f_entryname']."</span></div>
               <div style='float:left;margin: 0;min-height:1px;width:7%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;min-height:1px;width:4%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;width:11%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$value['f_quantity']."</span></div>
               <div style='float:left;margin: 0;width:13%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$Unitprice."</span></div>
               <div style='float:left;margin: 0;width:16%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$jine."</span></div>
               <div style='float:left;margin: 0;width:6%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;'>".$value['f_tax']."%</span></div>
               <div style='float:left;margin:0;width:17%;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$shuie."</span></div>
               
           </div>";
                        $pr = Db::table('tf_card')->where('name',$value['f_membertype'])->field('principal,rate')->find();
                        if (!empty($pr['rate'])) {
                          if ($value['f_userprice'] != 0 && !empty($value['f_userprice'])) {
                              $num = $num+1;
                              $list[$num] = 1;
                              $zhekoujine =  round($f_userprice*$pr['rate'],2);    //折扣金额
                              if(floatval($value['f_userprice']) == 0){
                                  $zhje =0;
                                  $zkse =0;
                              }else{
                                  $jeb = round(($value['f_userprice']/$f_userprice)*$zhekoujine,2); //折扣分摊金额
                                  $zhje = round($jeb / (1+$value['f_tax']*0.01),2); //金额
                                  $zkse = $jeb-$zhje; //税额
                              }
                              $zjine = $zjine-$zhje;
                              $zshuie = $zshuie-$zkse;
                              $c = explode('.', $zhje);
                              $d = explode('.', $zkse);
                              if ($c[0] != 0 || $c[1] != 0) {
                                $zhje = '-'.$zhje;
                              }
                              if ($d[0] != 0 || $d[1] != 0) {
                                $zkse = '-'.$zkse;
                              }
                              $zheji= $zheji + $zkse;
                              $zheji= $zheji + $zhje;
                            $str.="<div class='div1' style='overflow:hidden;font-size:12px;'>
               <div style='float:left;margin: 0;text-align: left;width:26%;border-right:1px solid #000;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-left:-13px;'>".$value['f_entryname']."</span></div>
               <div style='float:left;margin: 0;min-height:1px;width:7%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;min-height:1px;width:4%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;width:11%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;width:13%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px'></span></div>
               <div style='float:left;margin: 0;width:16%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$zhje."</span></div>
               <div style='float:left;margin: 0;width:6%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;'>".$value['f_tax']."%</span></div>
               <div style='float:left;margin:0;width:17%;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$zkse."</span></div>
               
           </div>";   
                            }
                          }  
                          $num = $num+1;
                          $list[$num] = 1; 
                          $zjine =0;
                          $zshuie =0;
                          $heji =0; 
                        }

                      }
                    $str .= "<div style='position: absolute;top:0;width: 100%;overflow: hidden;font-size:12px;'>
            <div style='float:left;width:26%;border-right:1px solid #000;height: 588px;line-height: 1150px;text-align: center;'>合计</div>
            <div style='float:left;width: 7%;padding-left: 5px;border-right: 1px solid #000;height: 588px;'></div>
            <div style='float:left;width: 4%;padding-left: 5px;border-right: 1px solid #000;height: 588px;'></div>
            <div style='float:left;width: 11%;padding-left: 5px;border-right: 1px solid #000;height: 588px;'></div>
            <div style='float:left;width: 13%;padding-left: 5px;border-right: 1px solid #000;height: 588px;'></div>
            <div style='float:left;width: 16%;padding-right: 5px;border-right: 1px solid #000;height: 588px;text-align: right;line-height: 1150px;'>￥".$zjine."</div>
            <div style='float:left;width: 6%;padding-left: 5px;border-right: 1px solid #000;height: 588px;'></div>
            <div style='float:left;padding-right: 5px;width: 17%;height: 588px;text-align: right;line-height: 1150px;'>".$zshuie."</div>
          </div>
        </div>
           <div class='amount' style='height: 40px;line-height:40px;overflow: hidden;border-top: 1px solid #000;font-size:12px;'>
            <p style='float: left;width: 26%;text-align: center;margin: 0;border-right: 1px solid #000;'>价税合计（大写）</p>
            <p style='float: left;margin: 0;width: 40%;padding-left: 5px;'>ⓧ".$this->num_to_rmb($zheji)."</p>
            <p style='float: left;margin: 0;'>（小写）¥".$zheji."</p>
        </div>
        <div class='sales' style='border-top: 1px solid #000;overflow: hidden;height: 155px;'>
            <div class='buyer-tit' style='float: left;width:26%;height: 155px;text-align: center;line-height: 155px;border-right: 1px solid #000;'>
                <span style='font-size: 36px;'>销售方</span>
            </div>
            <div class='buy-cont' style='padding-top: 3px;padding-left: 8px;float: left;width: 38%;'>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>名&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;称：</span>
                    <span>".$this->confv('mingcheng')."</span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>纳税人识别号：".$this->confv('nashui')."</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>地&nbsp;址、电&nbsp;话：".$this->confv('dizhi')."</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>开户行及账号：".$this->confv('zhanghu')."</span>
                    <span></span>
                </p>
            </div>
            <div class='remark' style='float: left;width: 40px;height:155px;text-align: center;
                  border-left: 1px solid #000;border-right: 1px solid #000;'>
                <span style='margin-top: 13px;display: block;'>备<br/><br/>注</span>
            </div>
            <div class='remark-txt' style='float: left;padding-left: 8px;padding-top: 20px;'>
                <span>账单流水号：".$liushui."</span>
            </div>
        </div>";
                  echo htmlspecialchars_decode($str);exit;
  }




 public function printings()
    {
        $ret = Request::instance()->post();
        $str = "";
        foreach ($ret['dataMember'] as $key => $value) {
            $c = $key;
            $data[$key] = Db::table('tf_detail')->where('f_ID',$value['f_ID'])->where('f_single',$value['f_single'])->select();
            $f_userprice = Db::table('tf_detail')->where('f_ID',$value['f_ID'])->where('f_single',$value['f_single'])->sum('f_userprice');

            $nubmer = Db::table('tf_detail')->where('f_ID',$value['f_ID'])->where('f_single',$value['f_single'])->find();
            $nums = explode('-', $nubmer['f_single']);
            $numc = $nums['1']; //流水号 NO.$num
            $vipnum = $value['f_ID']; //会员卡号 会员名称
            $date = date('Y年m月d日',time()); //开票日期
            $liushui = $value['f_single']; //账单流水号
            $zjine = 0;
            $zshuie =0;
            $list = [];
        $str.= "<div class='title' style='width:95%; margin:0 auto; overflow: hidden;padding-bottom: 20px;font-family: 楷体;'>
    <div class='title-left' style='float: left;width: 64%;font-size: 44px;text-align: right;margin-top: 18px;'>销售发票</div>
    <div class='title-right' style='float: right;'>
      <p style='font-size: 18px;margin-top:15px;margin-bottom:15px;'>NO.".$numc."</p>
      <p style='font-size: 12px;'>开票日期：".$date."</p>
    </div>
    </div>
    <div class='table' style='width: 100%;margin:0 auto;border: 1px solid #000;height: 870px;font-family: 楷体;'> 
     <div class='buyer' style='overflow: hidden;height: 85px;border-bottom: 1px solid #000;'>
            <div class='buyer-tit' style='float: left;width:26%;height: 85px;text-align: center;line-height: 85px;border-right: 1px solid #000;'>
                <span style='font-size: 36px;'>购买方</span>
            </div>
            <div class='buy-cont' style='padding-top: 3px;padding-left: 8px;float: left;'>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>名&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;称：</span>
                    <span>".$nubmer['f_NAME']."(".$vipnum.")</span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>纳税人识别号：</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>地&nbsp;址、电&nbsp;话：</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>开户行及账号：</span>
                    <span></span>
                </p>
            </div>
        </div>
        <div style='height:588px;position:relative;'>
         <div style='overflow:hidden;font-size:12px;'>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:26%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>货物或应税劳务、服务名称</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:7%;border-right:1px solid #000;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>规格型号</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:4%;border-right:1px solid #000;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>单位</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:11%;border-right:1px solid #000;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>数量</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:13%;border-right:1px solid #000;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>单价</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:16%;border-right:1px solid #000;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>金额</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:6%;border-right:1px solid #000;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>税率</span></p>
            <p style='float:left;margin: 0;width:17%;padding-top:3px;padding-bottom:3px;text-align: center;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;'>税额</span></p>
        </div>";
        $num = 0;
        $zheji = 0;
        foreach ($data[$key] as $k => $value) {
                  $jine = sprintf("%.2f", $value['f_userprice']/(1+$value['f_tax']*0.01)); //金额
                  $Unitprice = sprintf("%.6f",$jine/$value['f_quantity']); //单价
                  $zheji =$zheji+ $jine;

                if ($jine <= 0) {
                    $Unitprice = '';
                }
                $shuie = sprintf("%.2f", $value['f_userprice']-$jine); //税额
                $zheji= $zheji+$shuie;
                if ( $k == 0  || !is_int(count($list)/30)) {
                $str.="<div class='div1' style='overflow:hidden;font-size:12px;'>
               <div style='float:left;margin: 0;text-align: left;width:26%;border-right:1px solid #000;padding-top:2px;padding-bottom:2px;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;margin-left:-13px;'>".$value['f_entryname']."</span></div>
               <div style='float:left;margin: 0;min-height:1px;width:7%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;min-height:1px;width:4%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;width:11%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$value['f_quantity']."</span></div>
               <div style='float:left;margin: 0;width:13%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$Unitprice."</span></div>
               <div style='float:left;margin: 0;width:16%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$jine."</span></div>
               <div style='float:left;margin: 0;width:6%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;'>".$value['f_tax']."%</span></div>
               <div style='float:left;margin:0;width:17%;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$shuie."</span></div>
               
           </div>";
                    $pr = Db::table('tf_card')->where('name',$value['f_membertype'])->field('principal,rate')->find();
                    if (!empty($pr['rate'])) {
                      if ($value['f_userprice'] != 0 && !empty($value['f_userprice'])) {
                        $num = $num+1;
                        $list[$num] = 1;
                        $zhekoujine =  round($f_userprice*$pr['rate'],2);    //折扣金额
                        if(floatval($value['f_userprice']) == 0){
                            $zhje =0;
                            $zkse =0;
                        }else{
                            $jeb = round(($value['f_userprice']/$f_userprice)*$zhekoujine,2); //折扣分摊金额
                            $zhje = round($jeb / (1+$value['f_tax']*0.01),2); //金额
                            $zkse = $jeb-$zhje; //税额
                        }
                        $zjine = $zjine-$zhje;
                        $zshuie = $zshuie-$zkse;
                        $c = explode('.', $zhje);
                        $d = explode('.', $zkse);
                        if ($c[0] != 0 || $c[1] != 0) {
                          $zhje = '-'.$zhje;
                        }
                        if ($d[0] != 0 || $d[1] != 0) {
                          $zkse = '-'.$zkse;
                        }
                        $zheji = $zheji+$zhje;
                        $zheji = $zheji+$zkse;
                      $str.="<div class='div1' style='overflow:hidden;font-size:12px;'>
               <div style='float:left;margin: 0;text-align: left;width:26%;border-right:1px solid #000;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-left:-13px;'>".$value['f_entryname']."</span></div>
               <div style='float:left;margin: 0;min-height:1px;width:7%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;min-height:1px;width:4%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;width:11%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;width:13%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px'></span></div>
               <div style='float:left;margin: 0;width:16%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$zhje."</span></div>
               <div style='float:left;margin: 0;width:6%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;'>".$value['f_tax']."%</span></div>
               <div style='float:left;margin:0;width:17%;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$zkse."</span></div>
               
           </div>";  
                      }
                    } 
                    $num = $num+1;
                    $list[$num] = 1; 
                    $zjine = $zjine+$jine; //总金额
                    $zshuie = $zshuie+$shuie; //总税额
                    $heji = $zshuie+$zjine; //合计
            }else{
               $list = [];
                $str.="<div style='position: absolute;top:0;width: 100%;overflow: hidden;font-size:12px;'>
            <div style='float:left;width:26%;border-right:1px solid #000;height: 588px;line-height: 1150px;text-align: center;'>合计</div>
            <div style='float:left;width: 7%;border-right: 1px solid #000;height: 588px;'>
             </div>
            <div style='float:left;width: 4%;border-right: 1px solid #000;height: 588px;'>
             </div>
            <div style='float:left;width: 11%;border-right: 1px solid #000;height: 588px;'>
             </div>
            <div style='float:left;width: 13%;border-right: 1px solid #000;height: 588px;'>
             </div>
            <div style='float:left;width: 16%;border-right: 1px solid #000;height: 588px;text-align: right;line-height: 1150px;padding-right:8px;'>
            ￥".$zjine."</div>
            <div style='float:left;width: 6%;border-right: 1px solid #000;height: 588px;'>
            </div>
            <div style='float:left;width: 17%;height: 588px;text-align: right;line-height: 1150px;padding-right:8px;'>".$zshuie."</div>
          </div>
                </div>
                <div class='amount' style='height: 40px;line-height:40px;overflow: hidden;border-top: 1px solid #000;font-size:12px;'>
            <p style='float: left;width: 26%;text-align: center;margin: 0;border-right: 1px solid #000;'>价税合计（大写）</p>
            <p style='float: left;margin: 0;width: 40%;padding-left: 5px;'>ⓧ".$this->num_to_rmb($heji)."</p>
            <p style='float: left;margin: 0;'>（小写）¥".$heji."</p>
        </div>
        <div class='sales' style='border-top: 1px solid #000;overflow: hidden;height: 155px;'>
            <div class='buyer-tit' style='float: left;width:26%;height: 155px;text-align: center;line-height: 155px;border-right: 1px solid #000;'>
                <span style='font-size: 36px;'>销售方</span>
            </div>
            <div class='buy-cont' style='padding-top: 3px;padding-left: 8px;float: left;width: 38%;'>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>名&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;称：</span>
                    <span>".$this->confv('mingcheng')."</span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>纳税人识别号：".$this->confv('nashui')."</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>地&nbsp;址、电&nbsp;话：".$this->confv('dizhi')."</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>开户行及账号：".$this->confv('zhanghu')."</span>
                    <span></span>
                </p>
            </div>
            <div class='remark' style='float: left;width: 40px;height:155px;text-align: center;
                  border-left: 1px solid #000;border-right: 1px solid #000;'>
                <span style='margin-top: 13px;display: block;'>备<br/><br/>注</span>
            </div>
            <div class='remark-txt' style='float: left;padding-left: 8px;padding-top: 20px;'>
                <span>账单流水号：".$liushui."</span>
            </div>
        </div>
        </div>
        <div style='page-break-after: always'></div><div class='title' style='width:95%; margin:0 auto; overflow: hidden;padding-bottom: 20px;font-family: 楷体;'>
        <div class='title-left' style='float: left;width: 64%;font-size: 44px;text-align: right;margin-top: 18px;'>销售发票</div>
        <div class='title-right' style='float: right;'>
            <p style='font-size: 18px;margin-top:15px;margin-bottom:15px;'>NO.".$numc."</p>
            <p style='font-size: 12px;'>开票日期：".$date."</p>
        </div>
      </div>
      <div class='table' style='width: 100%;margin:0 auto;border: 1px solid #000;height: 870px;font-family: 楷体;'> 
       <div class='buyer' style='overflow: hidden;height: 85px;border-bottom: 1px solid #000;'>
            <div class='buyer-tit' style='float: left;width:26%;height: 85px;text-align: center;line-height: 85px;border-right: 1px solid #000;'>
                <span style='font-size: 36px;'>购买方</span>
            </div>
            <div class='buy-cont' style='padding-top: 3px;padding-left: 8px;float: left;'>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>名&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;称：</span>
                    <span>".$nubmer['f_NAME']."(".$vipnum.")</span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>纳税人识别号：</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>地&nbsp;址、电&nbsp;话：</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>开户行及账号：</span>
                    <span></span>
                </p>
            </div>
        </div>
         <div style='height:588px;position:relative;'>
         <div style='overflow:hidden;font-size:12px;'>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:26%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>货物或应税劳务、服务名称</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:7%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>规格型号</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:4%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>单位</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:11%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>数量</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:13%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>单价</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:16%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>金额</span></p>
            <p style='float:left;margin: 0;padding-top:3px;padding-bottom:3px;text-align: center;width:6%;border-right:1px solid #000;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>税率</span></p>
            <p style='float:left;margin: 0;width:17%;padding-top:3px;padding-bottom:3px;text-align: center;'>
            <span style='font-size: 10px;transform: scale(0.8);display: block;'>税额</span></p>
        </div>";
                      $str.="<div class='div1' style='overflow:hidden;font-size:12px;'>
               <div style='float:left;margin: 0;text-align: left;width:26%;border-right:1px solid #000;padding-top:2px;padding-bottom:2px;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;margin-left:-13px;'>".$value['f_entryname']."</span></div>
               <div style='float:left;margin: 0;min-height:1px;width:7%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;min-height:1px;width:4%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;width:11%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$value['f_quantity']."</span></div>
               <div style='float:left;margin: 0;width:13%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
              <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$Unitprice."</span></div>
               <div style='float:left;margin: 0;width:16%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$jine."</span></div>
               <div style='float:left;margin: 0;width:6%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;'>".$value['f_tax']."%</span></div>
               <div style='float:left;margin:0;width:17%;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$shuie."</span></div>
               
           </div>";
                      $pr = Db::table('tf_card')->where('name',$value['f_membertype'])->field('principal,rate')->find();
                        if (!empty($pr['rate'])) {
                          if ($value['f_userprice'] != 0 && !empty($value['f_userprice'])) {
                              $num = $num+1;
                              $list[$num] = 1;
                              $zhekoujine =  round($f_userprice*$pr['rate'],2);    //折扣金额
                              if(floatval($value['f_userprice']) == 0){
                                  $zhje =0;
                                  $zkse =0;
                              }else{
                                  $jeb = round(($value['f_userprice']/$f_userprice)*$zhekoujine,2); //折扣分摊金额
                                  $zhje = round($jeb / (1+$value['f_tax']*0.01),2); //金额
                                  $zkse = $jeb-$zhje; //税额
                              }
                              $zjine = $zjine-$zhje;
                              $zshuie = $zshuie-$zkse;
                              $c = explode('.', $zhje);
                              $d = explode('.', $zkse);
                              if ($c[0] != 0 || $c[1] != 0) {
                                $zhje = '-'.$zhje;
                              }
                              if ($d[0] != 0 || $d[1] != 0) {
                                $zkse = '-'.$zkse;
                              }
                              $zheji= $zheji + $zhje;
                              $zheji= $zheji + $zkse;
                            $str.="<div class='div1' style='overflow:hidden;font-size:12px;'>
               <div style='float:left;margin: 0;text-align: left;width:26%;border-right:1px solid #000;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-left:-13px;'>".$value['f_entryname']."</span></div>
               <div style='float:left;margin: 0;min-height:1px;width:7%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;min-height:1px;width:4%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
                <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;width:11%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'></span></div>
               <div style='float:left;margin: 0;width:13%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px'></span></div>
               <div style='float:left;margin: 0;width:16%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$zhje."</span></div>
               <div style='float:left;margin: 0;width:6%;border-right:1px solid #000;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;'>".$value['f_tax']."%</span></div>
               <div style='float:left;margin:0;width:17%;text-align:right;padding-top:2px;padding-bottom:2px;'>
               <span style='font-size: 10px;transform: scale(0.8);display: block;margin-right:-5px;'>".$zkse."</span></div>
               
           </div>";  
                            }
                          }  
                          $num = $num+1;
                          $list[$num] = 1; 
                          $zjine =0;
                          $zshuie =0;
                          $heji =0; 
            }

        }
        $str .= "<div style='position: absolute;top:0;width: 100%;overflow: hidden;font-size:12px;'>
            <div style='float:left;width:26%;border-right:1px solid #000;height: 588px;line-height: 1150px;text-align: center;'>合计</div>
            <div style='float:left;width: 7%;padding-left: 5px;border-right: 1px solid #000;height: 588px;'></div>
            <div style='float:left;width: 4%;padding-left: 5px;border-right: 1px solid #000;height: 588px;'></div>
            <div style='float:left;width: 11%;padding-left: 5px;border-right: 1px solid #000;height: 588px;'></div>
            <div style='float:left;width: 13%;padding-left: 5px;border-right: 1px solid #000;height: 588px;'></div>
            <div style='float:left;width: 16%;padding-right: 5px;border-right: 1px solid #000;height: 588px;text-align: right;line-height: 1150px;'>￥".$zjine."</div>
            <div style='float:left;width: 6%;padding-left: 5px;border-right: 1px solid #000;height: 588px;'></div>
            <div style='float:left;padding-right: 5px;width: 17%;height: 588px;text-align: right;line-height: 1150px;'>".$zshuie."</div>
          </div>
        </div>
           <div class='amount' style='height: 40px;line-height:40px;overflow: hidden;border-top: 1px solid #000;font-size:12px;'>
            <p style='float: left;width: 26%;text-align: center;margin: 0;border-right: 1px solid #000;'>价税合计（大写）</p>
            <p style='float: left;margin: 0;width: 40%;padding-left: 5px;'>ⓧ".$this->num_to_rmb($zheji)."</p>
            <p style='float: left;margin: 0;'>（小写）¥".$zheji."</p>
        </div>
        <div class='sales' style='border-top: 1px solid #000;overflow: hidden;height: 155px;'>
            <div class='buyer-tit' style='float: left;width:26%;height: 155px;text-align: center;line-height: 155px;border-right: 1px solid #000;'>
                <span style='font-size: 36px;'>销售方</span>
            </div>
            <div class='buy-cont' style='padding-top: 3px;padding-left: 8px;float: left;width: 38%;'>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>名&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;称：</span>
                    <span>".$this->confv('mingcheng')."</span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>纳税人识别号：".$this->confv('nashui')."</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>地&nbsp;址、电&nbsp;话：".$this->confv('dizhi')."</span>
                    <span></span>
                </p>
                <p style='margin-top:3px;margin-bottom:3px;'>
                    <span>开户行及账号：".$this->confv('zhanghu')."</span>
                    <span></span>
                </p>
            </div>
            <div class='remark' style='float: left;width: 40px;height:155px;text-align: center;
                  border-left: 1px solid #000;border-right: 1px solid #000;'>
                <span style='margin-top: 13px;display: block;'>备<br/><br/>注</span>
            </div>
            <div class='remark-txt' style='float: left;padding-left: 8px;padding-top: 20px;'>
                <span>账单流水号：".$liushui."</span>
            </div>
        </div></div>";

        if (end($ret['dataMember']) != $c) {
           $str .= "<div style='page-break-after: always'></div>";
        }


        }

        echo htmlspecialchars_decode($str);exit;

    }

    private function confv($k, $type = 'web'){
      $config = new Config();
      return $config->confv($k, $type);
    }

    private function num_to_rmb($num){
    $c1 = "零壹贰叁肆伍陆柒捌玖";
    $c2 = "分角元拾佰仟万拾佰仟亿";
    //精确到分后面就不要了，所以只留两个小数位
    $num = round($num, 2); 
    //将数字转化为整数
    $num = $num * 100;
    if (strlen($num) > 10) {
        return "金额太大，请检查";
    } 
    $i = 0;
    $c = "";
    while (1) {
        if ($i == 0) {
            //获取最后一位数字
            $n = substr($num, strlen($num)-1, 1);
        } else {
            $n = $num % 10;
        }
        //每次将最后一位数字转化为中文
        $p1 = substr($c1, 3 * $n, 3);
        $p2 = substr($c2, 3 * $i, 3);
        if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
            $c = $p1 . $p2 . $c;
        } else {
            $c = $p1 . $c;
        }
        $i = $i + 1;
        //去掉数字最后一位了
        $num = $num / 10;
        $num = (int)$num;
        //结束循环
        if ($num == 0) {
            break;
        } 
    }
    $j = 0;
    $slen = strlen($c);
    while ($j < $slen) {
        //utf8一个汉字相当3个字符
        $m = substr($c, $j, 6);
        //处理数字中很多0的情况,每次循环去掉一个汉字“零”
        if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
            $left = substr($c, 0, $j);
            $right = substr($c, $j + 3);
            $c = $left . $right;
            $j = $j-3;
            $slen = $slen-3;
        } 
        $j = $j + 3;
    } 
    //这个是为了去掉类似23.0中最后一个“零”字
    if (substr($c, strlen($c)-3, 3) == '零') {
        $c = substr($c, 0, strlen($c)-3);
    }
    //将处理的汉字加上“整”
    if (empty($c)) {
        return "零元";
    }else{
        return $c;
    }
}

    public function cleanCache()
    {
        deldir(Env::get('runtime_path'), 'y');
        if (request()->isPost()){
            return ajax_return(lang('action_success'), '', 1);
        }else{
            return $this->fetch();
        }
    }

}
