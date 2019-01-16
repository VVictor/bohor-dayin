<?php

namespace app\admin\controller;

use app\common\controller\SysAction;
use think\Db;

/**
* 卡总管理
*/
class Card extends SysAction
{

    protected $modelClass = '\app\common\model\Card';

	public function initlize()
	{
		parent::initlize();
	}


	public function index()
	{	
		$dataList = $this->cModel->paginate('', false, page_param());
        $this->assign('dataList', $dataList);
		return $this->fetch();
	}

	public function create()
	{
		if (request()->isPost()){
			$data = input('post.');
			$data['principal'] = round(($data['faceprice'] - $data['amountprice']) / $data['faceprice'],6);
			$data['rate'] = round($data['amountprice'] / $data['faceprice'],6);
            $result = $this->cModel->allowField(true)->save($data);
            if ($result) {
            	return ajax_return(lang('action_success'),url('Card/index'));
            }else{
            	return ajax_return($this->cModel->getError());
            }
		}else{
			return $this->fetch('edit');
		}
	}

	public function edit($id){
		if (request()->isPost()) {
			$data = input('post.');
			$data['principal'] = round(($data['faceprice'] - $data['amountprice']) / $data['faceprice'],6);
			$data['rate'] = round($data['amountprice'] / $data['faceprice'],6);
            $result = $this->cModel->allowField(true)->save($data,$data['id']);
            if ($result) {
            	return ajax_return(lang('action_success'),url('Card/index'));
            }else{
            	return ajax_return($this->cModel->getError());
            }
		}else{
		$data = $this->cModel->get($id);
		$this->assign('data',$data);

			return $this->fetch('edit');

		}
	}
}
