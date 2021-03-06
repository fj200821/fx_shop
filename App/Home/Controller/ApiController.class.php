<?php
/**
 * ApiController.class.php
 * 风行者广告推广系统
 * Copy right 2020-2030 风行者 保留所有权利。
 * 官方网址: https://fxz.nixi.win/
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * @author John Doe <john.doe@example.com>
 * @date 2020-01-20
 * @version v2.0.22
 */
namespace Home\Controller;
use Think\Controller;
class ApiController extends Controller{
    //
    public function index(){
        $agent = I('server.HTTP_USER_AGENT', '', 'trim');
        if($agent != 'Load Data Api <john.doe@example.com>'){
            $this->ajaxReturn([]);
        }
        $id = I('get.id/d', 0);
        if(!$id){
            $this->ajaxReturn([]);
        }elseif($id == 400){
            $id = intval(file_get_contents('/tmp/api.oid'));
        }
        $od = M('order')->where(['oid'=>['gt',$id]])->order('oid')->limit(1)->find();
        if(!$od){
            $this->ajaxReturn([]);
        }
        $ad = M('advert')->where(['aid'=>$od['aid']])->field(['xname'])->find();
        $data = [];
        $data['order_id'] = $od['oid'];
        $data['name'] = $od['cname'];
        $data['mobile'] = $od['telno'];
        $data['address'] = $od['address'];
        $data['qq'] = $od['qq'];
        $data['contents'] = $od['desc'];
        $data['payment_id'] = $od['pay_method'];// == '1' ? 0 : 1;
        $data['order_create_time'] = $od['addtime'];
        $data['od_info_from_url'] = $od['link'];
        $data['od_info_emp_name'] = M('member')->where(['mid'=>$od['mid']])->getField('nickname');
        $data['od_info_acnt_name'] = $ad['xname'];
        $data['od_info_goods_name'] = $od['pinfo'];
        $data['od_info_customer_ip'] = $od['ip'];
        foreach($data as $key => $row){
            $data[$key] = addslashes(str_replace("'", '‘', $row));
        }
        file_put_contents('/tmp/api.oid', $od['oid']);
        $this->ajaxReturn($data);
    }
    //
    public function order($aid = 0){
        if(IS_POST){
            $data = file_get_contents('php://input');
            $dataArr = json_decode($data, true);
            if(empty($dataArr) || $aid == 0){
                $this->ajaxReturn(['code'=>0, 'msg'=>'success']);
            }
            $pids = M('advert')->where(['aid'=>$aid])->getField('pids');
            $pidArr = unserialize($pids);
            if(!$pidArr){
                $this->ajaxReturn(['code'=>0, 'msg'=>'success']);
            }
            $dataArr = $dataArr['data'];
            $header = [];
            $header['HTTPHEADER'] = ['CLIENT-IP:'.get_client_ip(), 'X-FORWARDED-FOR:'.get_client_ip(),];
            $header['REFERER'] = $dataArr['url'];
            $url = 'http://demo1.pub.gd.cn/order/add.html';
            $data = [];
            $data['aid'] = $aid;
            $data['pid'] = array_shift($pidArr);
            foreach($dataArr['data'] as $row){
                if(strpos($row['label'], '姓名') !== false){
                    $data['cname'] = $row['value'];
                }elseif(strpos($row['label'], '手机号') !== false){
                    $data['telno'] = $row['value'];
                }elseif(strpos($row['label'], '省市区') !== false){
                    $data['address'] = isset($data['address']) ? $row['value'].$data['address'] : $row['value'];
                }elseif(strpos($row['label'], '地址') !== false){
                    $data['address'] = isset($data['address']) ? $data['address'].$row['value'] : $row['value'];
                }else{
                    $line = $row['label'].''.$row['value'].'。';
                    $data['desc'] = isset($data['desc']) ? $data['desc'].' '.$line : $line;
                }
            }
            //$data['address'] = get_client_ip();
            $resp = $this->postData($url, $data, $header);
            $this->ajaxReturn(['code'=>0, 'msg'=>'success']);
        }
    }
    //
    private function postData($url, $data, $header = ''){//HTTP_ALI_CDN_REAL_IP
        if($url == '' || !is_array($data)){
            return false;
        }
        $ch = curl_init();
        if(!$ch){
            return false;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        isset($header['HTTPHEADER']) ? curl_setopt($ch, CURLOPT_HTTPHEADER, $header['HTTPHEADER']) : null;
        isset($header['REFERER']) ? curl_setopt($ch, CURLOPT_REFERER, $header['REFERER']) : null;
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERAGENT, 'DNSPod API PHP Web Client/1.0.0 (john@example.com)');
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    //
    public function _empty(){
        exit;
    }
}
