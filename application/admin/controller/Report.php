<?php

namespace app\admin\controller;
use app\common\controller\SysAction;
use think\Db;
use Request;
/**
* 报表
*/
class Report extends SysAction
{
	
	public function initlize()
	{
		parent::initlize();
	}

	public function index()
	{	
		$data = Request::instance()->get();
		$db = Db::table('tf_detail')->group('f_membertype')->field('sum(f_userprice) as f_userprice,f_ID,f_NAME,f_single,f_membertype');
		// if($data['f_time'] ){
        	$time = explode('~', $data['f_time']);
            $db->whereTime('f_CREATETIME', 'between', [$time[0] , $time[1] ]);
        $list = $db->select();
		//$list6=$db->where('f_tax','6')->select();
		// $list16=$db->where('f_tax','16')->select();
		
		
		
			
		$userprice=0;	
        $cot = 0;
        $uot = 0;
		foreach ($list as $key => $value) {
			$pe = Db::table('tf_card')->where('name',$value['f_membertype'])->field('principal,rate')->find();
			$datalist[$key]['name'] = $value['f_membertype'];
			$datalist[$key]['dcount'] = sprintf("%.2f",$value['f_userprice']*$pe['principal']);
			$datalist[$key]['count'] = sprintf("%.2f",$value['f_userprice']*$pe['rate']);
			$datalist[$key]['f_usrprice'] = $value['f_userprice'];
			
			$list6find=Db::table('tf_detail')
			->group('f_membertype')
			->field('sum(f_userprice) as f_userprice,f_ID,f_NAME,f_single,f_membertype')
			->whereTime('f_CREATETIME', 'between', [$time[0] , $time[1] ])
			->where('f_tax','6')
			->where('f_membertype',$value['f_membertype'])
			->find();
			
			
			$list16find=Db::table('tf_detail')
			->group('f_membertype')
			->field('sum(f_userprice) as f_userprice,f_ID,f_NAME,f_single,f_membertype')
			->whereTime('f_CREATETIME', 'between', [$time[0] , $time[1] ])
			->where('f_tax','16')
			->where('f_membertype',$value['f_membertype'])
			//->field('f_userprice')
			->find();
			
			$listfind=Db::table('tf_detail')
			->group('f_membertype')
			->field('sum(f_userprice) as f_userprice,f_ID,f_NAME,f_single,f_membertype')
			->whereTime('f_CREATETIME', 'between', [$time[0] , $time[1] ])
			->where('f_tax','')
			->where('f_membertype',$value['f_membertype'])
			//->field('f_userprice')
			->find();
			
			
		//var_dump($list6find);
			//var_dump($list16find);
			//var_dump($listfind);
			
			$datalist[$key]['count6'] = sprintf("%.2f",$list6find['f_userprice']*$pe['principal']);
			$datalist[$key]['count16'] = sprintf("%.2f",$list16find['f_userprice']*$pe['principal']);
			$datalist[$key]['countNull'] = sprintf("%.2f",$listfind['f_userprice']*$pe['principal']);
			
			$userprice=sprintf("%.2f", $userprice + $value['f_userprice']);
			$cot =sprintf("%.2f", $cot + $value['f_userprice']*$pe['principal']);
			$uot = sprintf("%.2f",$uot + $value['f_userprice']*$pe['rate']);
		// }
		$this->assign('datalist',$datalist);
		$this->assign('cot',$cot);
		$this->assign('uot',$uot);
		$this->assign('userprice',$userprice);
        }
		return $this->fetch();
	}
}