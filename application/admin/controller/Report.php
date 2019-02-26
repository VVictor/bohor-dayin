<?php

namespace app\admin\controller;

use app\common\controller\SysAction;
use think\Db;
use Request;
use  PhpOffice\PhpSpreadsheet\Spreadsheet;

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
        $db->whereTime('f_CREATETIME', 'between', [$time[0], $time[1]]);
        $list = $db->select();
        //$list6=$db->where('f_tax','6')->select();
        // $list16=$db->where('f_tax','16')->select();


        $userprice = 0;
        $cot = 0;
        $uot = 0;
        foreach ($list as $key => $value) {
            $pe = Db::table('tf_card')->where('name', $value['f_membertype'])->field('principal,rate')->find();
            $datalist[$key]['name'] = $value['f_membertype'];
            $datalist[$key]['dcount'] = sprintf("%.2f", $value['f_userprice'] * $pe['principal']);
            $datalist[$key]['count'] = sprintf("%.2f", $value['f_userprice'] * $pe['rate']);
            $datalist[$key]['f_usrprice'] = $value['f_userprice'];

            $list6find = Db::table('tf_detail')
                ->group('f_membertype')
                ->field('sum(f_userprice) as f_userprice,f_ID,f_NAME,f_single,f_membertype')
                ->whereTime('f_CREATETIME', 'between', [$time[0], $time[1]])
                ->where('f_tax', '6')
                ->where('f_membertype', $value['f_membertype'])
                ->find();


            $list16find = Db::table('tf_detail')
                ->group('f_membertype')
                ->field('sum(f_userprice) as f_userprice,f_ID,f_NAME,f_single,f_membertype')
                ->whereTime('f_CREATETIME', 'between', [$time[0], $time[1]])
                ->where('f_tax', '16')
                ->where('f_membertype', $value['f_membertype'])
                //->field('f_userprice')
                ->find();

            $listfind = Db::table('tf_detail')
                ->group('f_membertype')
                ->field('sum(f_userprice) as f_userprice,f_ID,f_NAME,f_single,f_membertype')
                ->whereTime('f_CREATETIME', 'between', [$time[0], $time[1]])
                ->where('f_tax', '')
                ->where('f_membertype', $value['f_membertype'])
                //->field('f_userprice')
                ->find();


            //var_dump($list6find);
            //var_dump($list16find);
            //var_dump($listfind);

            $datalist[$key]['count6'] = sprintf("%.2f", $list6find['f_userprice'] * $pe['principal']);
            $datalist[$key]['count16'] = sprintf("%.2f", $list16find['f_userprice'] * $pe['principal']);
            $datalist[$key]['countNull'] = sprintf("%.2f", $listfind['f_userprice'] * $pe['principal']);

            $datalist[$key]['count6_fuserprice'] = sprintf("%.2f", $list6find['f_userprice']);
            $datalist[$key]['count16_fuserprice'] = sprintf("%.2f", $list16find['f_userprice']);
            $datalist[$key]['countNull_fuserprice'] = sprintf("%.2f", $listfind['f_userprice']);


            $userprice = sprintf("%.2f", $userprice + $value['f_userprice']);
            $cot = sprintf("%.2f", $cot + $value['f_userprice'] * $pe['principal']);
            $uot = sprintf("%.2f", $uot + $value['f_userprice'] * $pe['rate']);
            // }
            $this->assign('datalist', $datalist);
            $this->assign('cot', $cot);
            $this->assign('uot', $uot);
            $this->assign('userprice', $userprice);
        }
        return $this->fetch();
    }

    public function export(string $time)
    {

        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();

        // Add title name
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A1', '卡核销一览表')
            ->mergeCellsByColumnAndRow(1, 1, 7, 1);

        // Add title name
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A2', $time)
            ->mergeCellsByColumnAndRow(1, 2, 7, 2);

        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A3', '卡号类型')
            ->setCellValue('B3', '支付金额')
            ->setCellValue('C3', '本金金额(商业折扣后金额)')
            ->setCellValue('D3', '折扣金额')
            ->setCellValue('E3', '税率')
            ->setCellValue('F3', '税金')
            ->setCellValue('G3', '分摊金额');

        // Rename worksheet
        $spreadsheet->getActiveSheet()->setTitle('报表统计');

        $i = 4;
        $cell = 2;

        $db = Db::table('tf_detail')->group('f_membertype')->field('sum(f_userprice) as f_userprice,f_ID,f_NAME,f_single,f_membertype');
        $times = explode('~', $time);
        $db->whereTime('f_CREATETIME', 'between', [$times[0], $times[1]]);
        $list = $db->select();

        $userprice = 0;
        $cot = 0;
        $uot = 0;

        foreach ($list as $key => $value) {
            $pe = Db::table('tf_card')->where('name', $value['f_membertype'])->field('principal,rate')->find();
            $list6find = Db::table('tf_detail')
                ->group('f_membertype')
                ->field('sum(f_userprice) as f_userprice,f_ID,f_NAME,f_single,f_membertype')
                ->whereTime('f_CREATETIME', 'between', [$times[0], $times[1]])
                ->where('f_tax', '6')
                ->where('f_membertype', $value['f_membertype'])
                ->find();

            $list16find = Db::table('tf_detail')
                ->group('f_membertype')
                ->field('sum(f_userprice) as f_userprice,f_ID,f_NAME,f_single,f_membertype')
                ->whereTime('f_CREATETIME', 'between', [$times[0], $times[1]])
                ->where('f_tax', '16')
                ->where('f_membertype', $value['f_membertype'])
                ->find();

            $listfind = Db::table('tf_detail')
                ->group('f_membertype')
                ->field('sum(f_userprice) as f_userprice,f_ID,f_NAME,f_single,f_membertype')
                ->whereTime('f_CREATETIME', 'between', [$times[0], $times[1]])
                ->where('f_tax', '')
                ->where('f_membertype', $value['f_membertype'])
                ->find();


            $userprice = sprintf("%.2f", $userprice + $value['f_userprice']);
            $cot = sprintf("%.2f", $cot + $value['f_userprice'] * $pe['principal']);
            $uot = sprintf("%.2f", $uot + $value['f_userprice'] * $pe['rate']);

            // Add data
            $spreadsheet->getActiveSheet()
                ->setCellValueByColumnAndRow(1, $i, $value['f_membertype'])
                ->mergeCellsByColumnAndRow(1, $i, 1, $i + $cell)
                ->setCellValueByColumnAndRow(2, $i, $value['f_userprice'])
                ->mergeCellsByColumnAndRow(2, $i, 2, $i + $cell)
                ->setCellValueByColumnAndRow(3, $i, sprintf("%.2f", $value['f_userprice'] * $pe['principal']))
                ->mergeCellsByColumnAndRow(3, $i, 3, $i + $cell)
                ->setCellValueByColumnAndRow(4, $i, sprintf("%.2f", $value['f_userprice'] * $pe['rate']))//$rs['status'] ? '成功' : '失败')
                ->mergeCellsByColumnAndRow(4, $i, 4, $i + $cell)
                ->setCellValueByColumnAndRow(5, $i, '6%')//date('Y-m-d H:i:s',$rs['logintime']))
                ->setCellValueByColumnAndRow(5, $i + 1, '16%')
                ->setCellValueByColumnAndRow(5, $i + 2, '其他')
                ->setCellValueByColumnAndRow(6,  $i, sprintf("%.2f", $list6find['f_userprice'] * $pe['principal']))
                ->setCellValueByColumnAndRow(6, $i + 1, sprintf("%.2f", $list16find['f_userprice'] * $pe['principal']))
                ->setCellValueByColumnAndRow(6, $i + 2, sprintf("%.2f", $listfind['f_userprice'] * $pe['principal']))
                ->setCellValueByColumnAndRow(7,  $i, sprintf("%.2f", $list6find['f_userprice']))
                ->setCellValueByColumnAndRow(7, $i + 1, sprintf("%.2f", $list16find['f_userprice'] ))
                ->setCellValueByColumnAndRow(7, $i + 2, sprintf("%.2f", $listfind['f_userprice'] ));
            $i = $i + 3;
        }

        $highetRow = $spreadsheet->getActiveSheet()
            ->getHighestRow();

        $spreadsheet->getActiveSheet()
            ->setCellValueByColumnAndRow(1,$highetRow+1, '合计')
            ->setCellValueByColumnAndRow(2,$highetRow+1, $userprice)
            ->setCellValueByColumnAndRow(3,$highetRow+1, $cot)
            ->setCellValueByColumnAndRow(4,$highetRow+1,  $uot);


        //Set width
        $spreadsheet->getActiveSheet()
            ->getColumnDimension('A')
            ->setWidth(15);
        $spreadsheet->getActiveSheet()
            ->getColumnDimension('B')
            ->setWidth(15);
        $spreadsheet->getActiveSheet()
            ->getColumnDimension('C')
            ->setWidth(60);
        $spreadsheet->getActiveSheet()
            ->getColumnDimension('D')
            ->setWidth(15);
        $spreadsheet->getActiveSheet()
            ->getColumnDimension('E')
            ->setWidth(20);
        $spreadsheet->getActiveSheet()
            ->getColumnDimension('F')
            ->setWidth(20);
        $spreadsheet->getActiveSheet()
            ->getColumnDimension('G')
            ->setWidth(20);

        // Set alignment
        $spreadsheet->getActiveSheet()->getStyle('A1:F' . $i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('C2:C' . $i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);
        return exportExcel($spreadsheet, 'xls', '报表统计');
    }
}