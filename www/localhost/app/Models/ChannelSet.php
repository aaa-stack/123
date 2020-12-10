<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Channel;

class ChannelSet extends Model
{
    protected $table = 'channel_set';
    protected $product_table = 'products';
    protected $channel_table = 'channels';
    /**
     * 查询订单
     * 
     * @return array
     */

    public function getSetList()
    {
        $pro_data = Db::table($this->product_table)->select(['id', 'product_name'])->get();
        $pro_arr = json_decode($pro_data, true);
        foreach ($pro_arr as $k => &$v) {
            $channel_info = Db::table($this->channel_table)->where('product_id', '=', $v['id'])->select(['id', 'name'])->get();
            $v['mode'] = 1;
            $v['status'] = 2;
            $v['channel_arr'] = json_decode($channel_info, true);
        }
        return $pro_arr;
    }

    /**
     * 查询订单
     * 
     * @return array
     */

    public function getAllCost()
    {
        $pro_data = Db::table($this->product_table)->select(['id as product_id', 'product_name'])->get();
        $pro_arr = json_decode($pro_data, true);
        foreach ($pro_arr as $k => &$v) {
            $v['rate'] = 0;
        }
        return $pro_arr;
    }

    /**
     * 获取当前用户费率
     * @param int $merNo
     * @return array
     */

    public function getCostList($merNo)
    {
        $pro_data = Db::table($this->table)
            ->leftJoin($this->product_table, $this->table . '.product_id', '=', $this->product_table . '.id')
            ->where($this->table . '.merchant_no', '=', $merNo)
            ->select([$this->table . '.product_id', $this->table . '.rate', $this->product_table . '.product_name'])
            ->get();;
        return json_decode($pro_data, true);
    }

    /**
     * 统一设置通道
     * 
     * @return bool
     */

    public function getUserSet($merNO)
    {
        $data = Db::table($this->table)
            ->select([$this->table . '.product_id', $this->table . '.status', $this->table . '.channel_id', $this->table . '.weight', $this->product_table . '.product_name', $this->table . '.mode'])
            ->leftJoin($this->product_table, $this->table . '.product_id', '=', $this->product_table . '.id')
            ->where('merchant_no', '=', $merNO)
            ->get();
        $dataArr = json_decode($data, true);
        if(empty($dataArr)){
            return $dataArr;
        }
        $channel = new Channel();
        foreach ($dataArr as $k => &$v) {
            $v['channel_arr'] = $channel->getIdList($v['product_id']);
            $allowArr = array_column($v['channel_arr'], 'id');
            $allowArr = array_flip($allowArr);
            if (empty($allowArr)) {
                continue;
            }

            if ($v['mode'] == 2) {
                foreach($v['channel_arr'] as $ke => $va){
                    $v['channel_arr'][$ke]['weight'] = 0;
                    $v['channel_arr'][$ke]['select'] = 0;
                }
                $id_arr = explode(',', $v['channel_id']);
                $w_arr = explode(',', $v['weight']);
                foreach ($id_arr as $key => $val) {
                    if (isset($allowArr[$val])) {
                        $v['channel_arr'][$allowArr[$val]]['weight'] = $w_arr[$key];
                        $v['channel_arr'][$allowArr[$val]]['select'] = 1;
                    }
                }
            } else {
                if (isset($allowArr[$v['channel_id']])) {
                    $v['channel_id'] = (int)$v['channel_id'];
                }
            }
            unset($v['weight']);
        }
        return $dataArr;
    }

    /**
     * 获取用户产品列表
     * 
     * @return bool
     */

    public function getMerProList($merNO)
    {
        $data = Db::table($this->table)
            ->select([$this->table . '.product_id', $this->table . '.status', $this->table . '.rate', $this->product_table . '.product_name'])
            ->leftJoin($this->product_table, $this->table . '.product_id', '=', $this->product_table . '.id')
            ->where('merchant_no', '=', $merNO)
            ->get();
        return $data;
    }
}
