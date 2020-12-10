<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderLog extends Model
{
    protected $table = 'order_log';

    /**
     * 添加管理员日志
     * 
     * @param  array $data
     * 
     * @return bool
     */

    public function addLog($data)
    {
        return Db::table($this->table)->insert($data);
    }

    /**
     * 查看管理员日志
     * 
     * @param  int $merId
     * 
     * @return array 
     */

    public function getLog($input, $page, $num)
    {
        $where = [];
        if (isset($input['stime']) && !empty($input['stime'])) {
            array_push($where, [$this->table . '.time', '>=', strtotime($input['stime'])]);
        }
        if (isset($input['etime']) && !empty($input['etime'])) {
            array_push($where, [$this->table . '.time', '<=', strtotime($input['etime'])]);
        }
        if (isset($input['userId']) && !empty($input['userId'])) {
            array_push($where, ['merchant_no', '=', $input['userId']]);
        }
        $data['list'] = Db::table($this->table)
            ->select([$this->table . '.merchant_no', $this->table . '.pay_orderid', $this->table . '.param', $this->table . '.time', $this->table . '.ip'])
            ->where($where)
            ->offset(($page - 1) * $num)
            ->limit($num)
            ->orderBy('time','desc')
            ->get();
        $data['count'] = Db::table($this->table)->where($where)->count();
        $data['page'] = ceil($data['count'] / $num);
        return $data;
    }
}
