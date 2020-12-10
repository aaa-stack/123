<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class Products extends Model
{
    protected $table = 'products';
    protected $mer_table = 'merchants';
    protected $set_table = 'channel_set';


    /**
     * 获取支付产品列表
     *
     * @return array
     */

    public function getProductsList()
    {
        $data = Db::table($this->table)->select(['id', 'product_name', 'status', 'mode', 'client', 'pay_type', 'class'])->get();
        return json_decode($data, true);
    }

    /**
     * 获取支付产品列表
     *
     * @return array
     */

    public function getProductById($productId)
    {
        $data = Db::table($this->table)
            ->where('id', '=', $productId)
            ->select(['id', 'product_name', 'status', 'mode', 'client', 'pay_type', 'class'])
            ->get();
        $data['pay_type_zh'] = PAY_TYPE[$data['pay_type']];
        return json_decode($data, true);
    }

    /**
     * 设置支付产品status
     *
     * @param  int $productId
     * @param  int $status
     * 
     * @return bool
     */

    public function setProductStatus($productId, $status)
    {
        $res = Db::table($this->table)->where('id', '=', $productId)->update(['status' => $status]);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 设置支付产品client
     * 
     * @param  int $productId
     * @param  int $client
     * 
     * @return bool
     */

    public function setProductClient($productId, $client)
    {
        $res = Db::table($this->table)->where('id', '=', $productId)->update(['client' => $client]);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 查看产品是否存在
     * 
     * @param  int $productId
     * 
     * @return bool
     */

    public function hasProduct($productId)
    {
        return Db::table($this->table)->where('id', '=', $productId)->exists();
    }

    /**
     * 通过名称判断产品是否存在
     * 
     * @param  string $name
     * 
     * @return bool
     */

    public function hasProductName($name)
    {
        return Db::table($this->table)->where('product_name', '=', $name)->exists();
    }

    /**
     * 添加支付产品
     * 
     * @param  array $data
     * 
     * @return bool
     */

    public function addProduct($data)
    {
        DB::beginTransaction();
        try {
            $pro_id = Db::table($this->table)->insertGetId($data);
            if (!$pro_id) {
                DB::rollBack();
                return false;
            }
            $mer_list = Db::table($this->mer_table)->pluck('merchant_no');
            $data = ['product_id' => $pro_id];
            $re = Db::table($this->set_table)->insert($data);
            if (!$re) {
                DB::rollBack();
                return false;
            }
            foreach ($mer_list as $k => $v) {
                $set = ['merchant_no' => $v, 'product_id' => $pro_id];
                $re = Db::table($this->set_table)->insert($set);
                if (!$re) {
                    DB::rollBack();
                    return false;
                }
            }
            DB::commit();
        } catch (QueryException $ex) {
            DB::rollBack();
            return false;
        }
        return true;
    }

    /**
     * 删除支付产品
     * 
     * @param  int $productId
     * 
     * @return bool
     */

    public function delProduct($productId)
    {
        DB::beginTransaction();
        try {
            Db::table($this->set_table)->where('product_id', '=', $productId)->delete();
            $res = Db::table($this->table)->where('id', '=', $productId)->delete();
            if (!$res) {
                DB::rollBack();
                return false;
            }
            DB::commit();
            return true;
        } catch (QueryException $ex) {
            DB::rollBack();
            return false;
        }
        return true;
    }

    /**
     * 修改支付产品
     * 
     * @param  int $productId
     * @param  array $data
     * 
     * @return bool
     */

    public function editProduct($productId, $data)
    {
        return Db::table($this->table)->where('id', '=', $productId)->update($data);
    }

    /**
     * 查看产品是否存在
     * 
     * @param  int $productId
     * 
     * @return bool
     */

    public function isOpen($productId)
    {
        $res = Db::table($this->table)->where('id', '=', $productId)->pluck('status');
        $re = json_decode($res, true);
        if ($re && $re[0] == 1) {
            return true;
        }
        return false;
    }

    /**
     * 获取支付产品列表
     *
     * @return array
     */

    public function getProList()
    {
        $data = Db::table($this->table)->where('status', '=', '1')->select(['id as product_id', 'product_name'])->get();
        return json_decode($data, true);
    }

    /**
     * 获取支付产品id列表
     *
     * @return array
     */

    public function getProIdList()
    {
        $products = Db::table($this->table)->pluck("id");
        return json_decode($products, true);
    }
}
