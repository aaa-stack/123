<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Channel extends Model
{
    protected $table = "channels";
    protected $products_table = "products";

    /**
     * 获取支付通道列表
     *
     * @param  $num 每页数量
     * @param  $page 页数
     * @return array
     */

    public function getChannelList($page, $num)
    {
        $data['list'] = DB::table($this->table)
            ->select([$this->table . '.id', $this->table . '.name', $this->table . '.class', $this->table . '.status', $this->table . '.rate', $this->products_table . '.product_name'])
            ->leftJoin($this->products_table, $this->table . '.product_id', '=', $this->products_table . '.id')
            ->offset(($page - 1) * $num)->limit($num)
            ->get();
        $data['count'] = DB::table($this->table)->count();
        $data['page'] = ceil(count($data['list']) / $num);
        return $data;
    }

    /**
     * 添加支付通道列表
     *
     * @return array
     */

    public function addChannel($data)
    {
        return Db::table($this->table)->insert($data);
    }

    /**
     * 通过支付通道id查询支付通道信息
     *
     * @param  $channelId 支付通道id
     * @return bool
     */
    public function getChannelById($channelId)
    {
        $res = DB::table($this->table)->select(["id", "name", "class", "product_id", "status", "rate", "status", "gateway", "serverreturn", "pagereturn"])->where('id', '=', $channelId)->first();
        return $res;
    }

    /**
     * 通过支付产品id查询支付通道信息
     *
     * @param  $productId 支付通道id
     * @return bool
     */
    public function getChannelByProductId($productId)
    {
        $data = DB::table($this->table)
            ->select(["id", "name", "class", "product_id", "status", "rate", "status", "gateway", "serverreturn", "pagereturn"])->where('product_id', '=', $productId)
            ->get();
        return json_decode($data, true);
    }

    /**
     * 修改通道信息
     *
     * @param  int $channelId 通道id
     * @param  array $data 修改信息
     * @return bool
     */

    public function editChannel($channelId, $data)
    {
        return Db::table($this->table)->where('id', '=', $channelId)->update($data);
    }

    /**
     * 判断通道是否存在
     *
     * @param  $channelId 通道id
     * @return bool
     */

    public function hasChannel($channelId)
    {
        return Db::table($this->table)->where('id', '=', $channelId)->exists();
    }

    /**
     * 判断通道是否存在
     *
     * @param  $name 通道name
     * @return bool
     */

    public function hasChannelName($name)
    {
        return Db::table($this->table)->where('name', '=', $name)->exists();
    }

    /**
     * 删除支付通道
     * 
     * @param  int $channelId
     * 
     * @return bool
     */

    public function delChannel($channelId)
    {
        return Db::table($this->table)->where('id', '=', $channelId)->delete();
    }

    /**
     * 获取支付通道列表
     *
     * @return array
     */

    public function getChanList()
    {
        $data = DB::table($this->table)
            ->select(['id', 'name'])
            ->get();
        return json_decode($data, true);
    }

    /**
     * 获取可用支付通道ID列表
     *
     * @param int $productId
     * @return array
     */

    public function getChanId($productId = 0)
    {
        $where[] = ['status', '=', 1];
        if (!empty($productId)) {
            $where[] = ['product_id', '=', $productId];
        }
        $data = DB::table($this->table)
            ->where($where)
            ->pluck('id');
        return json_decode($data, true);
    }

    /**
     * 获取支付通道列表
     * 
     * @param  $productId 支付通道id
     *
     * @return array
     */

    public function getIdList($productId)
    {
        $data = DB::table($this->table)
            ->where([['status', '=', 1], ['product_id', '=', $productId]])
            ->select(['id', 'name'])
            ->get();
        return json_decode($data, true);
    }
}
