<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\QueryException;

class Merchant extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;

    protected $table = "merchants";
    protected $pro_table = "products";
    protected $set_table = "channel_set";


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'merchant_no'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * 修改商户登录ip
     *
     * @param  $id 商户id
     * @param  $ip 商户ip
     * @return bool
     */
    public function setLastIp($id, $ip)
    {
        $res = DB::table($this->table)->where('id', '=', $id)->update(['last_ip' => $ip, 'last_time' => time()]);

        if (!$res > 0) {
            return false;
        }

        return true;
    }

    /**
     * 获取商户列表
     *
     * @param  $num 每页数量
     * @param  $page 页数
     * @return bool
     */
    public function getUserList($where, $page, $num)
    {
        $condition = [];
        foreach ($where as $k => $v) {
            if (!empty($v)) {
                array_push($condition, [$this->table . "." . $k, '=', $v]);
            }
        }
        $res['list'] = DB::table($this->table)
            ->select([$this->table . ".id", $this->table . ".merchant_no", $this->table . ".username", $this->table . ".phone_num", $this->table . ".email", $this->table . ".created_time", $this->table . ".last_time", $this->table . ".last_ip", $this->table . ".status", $this->table . ".type", $this->table . ".avatar", $this->table . ".recharge", $this->table . ".account", "t2.username as agentName"])
            ->leftJoin($this->table . " as t2", $this->table . ".agent_id", '=', 't2.id')
            ->where($condition)
            ->orderBy("status", "asc")
            ->orderBy("id", "asc")
            ->offset(($page - 1) * $num)
            ->limit($num)
            ->get();
        $res['count'] = DB::table($this->table)->where($condition)->count();
        $res['page'] = ceil(count($res['list']) / $num);
        return $res;
    }

    /**
     * 通过商户id查询商户信息
     *
     * @param  $userid 商户id
     * @param  $agent_id 商户上级代理的id
     * @return bool
     */
    public function getUserInfoById($userid, $agent_id = 0)
    {
        if (!empty($agent_id)) {
            $where[] = [$this->table . '.agent_id', '=', $agent_id];
        }
        $where[] = [$this->table . '.id', '=', $userid];
        $res = DB::table($this->table)->select([$this->table . ".id", $this->table . ".merchant_no", $this->table . ".username", $this->table . ".phone_num", $this->table . ".email", $this->table . ".created_time", $this->table . ".avatar", $this->table . ".status", $this->table . ".type", $this->table . ".last_ip", $this->table . ".last_time", $this->table . ".recharge", $this->table . ".account", "t2.username as agentName"])
            ->leftJoin($this->table . " as t2", $this->table . ".agent_id", '=', 't2.id')
            ->where($where)
            ->first();
        return $res;
    }

    /**
     * 修改商户信息
     *
     * @param  $userid 商户id
     * @param  $data 修改状态
     * @return bool
     */
    public function editUserInfo($userid, $data)
    {
        $res = DB::table($this->table)->where('id', '=', $userid)->update($data);
        return $res;
    }

    /**
     * 判断商户是否存在
     *
     * @param  $userid 商户id
     * @return bool
     */

    public function hasUser($userid)
    {
        return Db::table($this->table)->where('id', '=', $userid)->exists();
    }

    /**
     * 判断商户是否存在
     *
     * @param  $userid 商户id
     * @return bool
     */

    public function hasMer($userid)
    {
        return Db::table($this->table)->where('merchant_no', '=', $userid)->exists();
    }

    /**
     * 判断商户名是否存在
     *
     * @param  $username 商户名
     * @return bool
     */

    public function hasUsername($username)
    {
        return Db::table($this->table)->where('username', '=', $username)->exists();
    }

    /**
     * 添加商户
     *
     * @param  array $data
     * @return bool
     */

    public function addMerchant($data)
    {
        DB::beginTransaction();
        try {
            $pro_list = Db::table($this->pro_table)->pluck('id');
            foreach ($pro_list as $k => $v) {
                $set = ['merchant_no' => $data['merchant_no'], 'product_id' => $v];
                $re = Db::table($this->set_table)->insert($set);
                if (!$re) {
                    DB::rollBack();
                    return false;
                }
            }
            $res = Db::table($this->table)->insert($data);
            if (!$res) {
                DB::rollBack();
                return false;
            }
            DB::commit();
        } catch (QueryException $ex) {
            DB::rollBack();
            return false;
        }
        return true;
    }

    /**
     * 判断商户是否允许支付
     *
     * @param  numeric $merchant_no
     * @return bool
     */

    public function allowPay($merchant_no)
    {
        $data = Db::table($this->table)->where('merchant_no', '=', $merchant_no)->select(['status', 'recharge'])->first();
        if (!empty($data) && $data->status == 1 && $data->recharge == 1) {
            return true;
        }
        return false;
    }

    /**
     * 获取商户类型
     *
     * @param  numeric $userId
     * @return bool
     */

    public function getUserType($userId)
    {
        $data = Db::table($this->table)->where('id', '=', $userId)->pluck('type');
        return json_decode($data, true);
    }
}
