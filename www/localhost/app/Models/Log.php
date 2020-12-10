<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Log extends Model
{
    protected $table = 'users_log';

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
            array_push($where, ['user_id', '=', $input['userId']]);
        }
        $data['list'] = Db::table($this->table)
            ->select([$this->table . '.username', $this->table . '.path', $this->table . '.method', $this->table . '.time', $this->table . '.ip', $this->table . '.param'])
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
