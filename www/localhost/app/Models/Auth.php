<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Auth extends Model
{
    protected $user_tale = "users";
    protected $role_table = "roles";
    protected $user_role_table = "users_roles";
    protected $auth_table = "auths";
    protected $role_auth_table = "roles_auths";
    protected $role_pauth_table = "roles_pauths";
    protected $pauth_table = "pauths";



    /**
     * 获取Api权限信息列表
     *
     * @return array
     */

    public function getApiAuthList()
    {
        $data = DB::table($this->auth_table)
            ->select('id', 'title', 'path', 'method')
            ->orderBy("group", "desc")
            ->get();
        return json_decode($data, true);
    }

    /**
     * 获取Api权限列表
     *
     * @return array
     */

    public function getApiAuthIdList()
    {
        $data = DB::table($this->auth_table)
            ->pluck('id');
        return json_decode($data, true);
    }

    /**
     * 获取角色Api权限
     *
     * @param  $roleId 角色id
     * @return array
     */

    public function getRoleApiAuthsList($roleId)
    {
        $data = DB::table($this->role_auth_table)
            ->where('role_id', '=', $roleId)
            ->pluck("auth_id");
        return json_decode($data, true);
    }

    /**
     * 获取page权限列表
     *
     * @return array
     */

    public function getPageAuthList()
    {
        $data = DB::table($this->pauth_table)
            ->select('id', 'title', 'path')
            ->get();
        return json_decode($data, true);
    }

    /**
     * 获取page权限id列表
     *
     * @return array
     */

    public function getPageAuthIdList()
    {
        $data = DB::table($this->pauth_table)
            ->pluck('id');
        return json_decode($data, true);
    }

    /**
     * 获取角色page权限
     *
     * @param  $roleId 角色id
     * @return array
     */

    public function getRolePageAuthsList($roleId)
    {
        $data = DB::table($this->role_pauth_table)
            ->leftJoin($this->pauth_table, $this->role_pauth_table . ".auth_id", '=', $this->pauth_table . '.id')
            ->where($this->role_pauth_table . '.role_id', '=', $roleId)
            ->pluck($this->role_pauth_table . ".auth_id");
        return json_decode($data, true);
    }

    /**
     * 获取用户Api权限
     *
     * @param  $userId 用户id
     * @return array
     */

    public function getUserApiAuthsList($userId)
    {
        $data = DB::table($this->user_role_table)
            ->leftJoin($this->role_pauth_table, $this->user_role_table . ".role_id", '=', $this->pauth_table . 'role_id')
            ->leftJoin($this->pauth_table, $this->role_pauth_table . ".auth_id", '=', $this->pauth_table . 'id')
            ->where($this->user_role_table . '.user_id', '=', $userId)
            ->pluck("path");
        return json_decode($data, true);
    }

    /**
     * 获取角色列表
     *
     * @return array
     */

    public function getRoleList()
    {
        $data = DB::table($this->role_table)
            ->pluck('id');
        return json_decode($data, true);
    }

    /**
     * 获取角色信息列表
     *
     * @return array
     */

    public function getRoleInfoList()
    {
        $data = DB::table($this->role_table)
            ->select("id", "role_name")
            ->get();
        return json_decode($data, true);
    }

    /**
     * 获取用户角色
     *
     * @param  $userId 用户id
     * @return array
     */

    public function getUserRoleList($userId)
    {
        $data = DB::table($this->user_role_table)
            ->where('user_id', '=', $userId)
            ->pluck('role_id');
        return json_decode($data, true);
    }

    /**
     * 删除用户角色
     *
     * @param  $userId 用户id
     * @param  $data 角色id
     * @return bool
     */

    public function delUserRole($userId, $data)
    {
        $res = DB::table($this->user_role_table)
            ->where('user_id', '=', $userId)
            ->whereIn('role_id', $data)
            ->delete();
        return $res;
    }

    /**
     * 添加用户角色
     *
     * @param  $userId 用户id
     * @param  $data 角色id
     * @return bool
     */

    public function insertUserRole($userId, $data)
    {
        $insert_data = [];
        foreach ($data as $v) {
            $row = ['user_id' => $userId, 'role_id' => $v];
            array_push($insert_data, $row);
        }
        $res = DB::table($this->user_role_table)->insert($insert_data);
        return $res;
    }

    /**
     * 判断角色是否存在
     *
     * @param  $roleId 角色id
     * @return bool
     */

    public function hasRole($roleId)
    {
        return Db::table($this->role_table)->where('id', '=', $roleId)->exists();
    }

    /**
     * 删除角色权限
     *
     * @param  $roleId 角色id
     * @param  $data 权限列表
     * @return bool
     */

    public function delRoleAuth($roleId, $data)
    {
        $res = DB::table($this->role_auth_table)
            ->where('role_id', '=', $roleId)
            ->whereIn('auth_id', $data)
            ->delete();
        return $res;
    }

    /**
     * 添加角色权限
     *
     * @param integer $roleId 角色id
     * @param array $data 权限列表
     * @return bool
     */

    public function insertRoleAuth($roleId, $data)
    {
        $insert_data = [];
        foreach ($data as $v) {
            $row = ['role_id' => $roleId, 'auth_id' => $v];
            array_push($insert_data, $row);
        }
        $res = DB::table($this->role_auth_table)->insert($insert_data);
        return $res;
    }

    /**
     * 删除角色页面权限
     *
     * @param  $roleId 角色id
     * @param  $data 权限列表
     * @return bool
     */

    public function delRolePauth($roleId, $data)
    {
        $res = DB::table($this->role_pauth_table)
            ->where('role_id', '=', $roleId)
            ->whereIn('auth_id', $data)
            ->delete();
        return $res;
    }

    /**
     * 添加角色页面权限
     *
     * @param  $roleId 角色id
     * @param  $data 权限列表
     * @return bool
     */

    public function insertRolePauth($roleId, $data)
    {
        $insert_data = [];
        foreach ($data as $v) {
            $row = ['role_id' => $roleId, 'auth_id' => $v];
            array_push($insert_data, $row);
        }
        $res = DB::table($this->role_pauth_table)->insert($insert_data);
        return $res;
    }

    /**
     * 删除角色
     *
     * @param  $roleId 角色id
     * @return bool
     */

    public function delRole($roleId)
    {
        return DB::table($this->role_table)->where('id', '=', $roleId)->delete();
    }

    /**
     * 删除用户角色
     *
     * @param  $roleId 角色id
     * @param  $userId 用户id
     * @return bool
     */

    public function delUsersRole($roleId, $userId)
    {
        return DB::table($this->role_table)->where([['role_id', '=', $roleId], ['userId', '=', $userId]])->delete();
    }

    /**
     * add角色
     *
     * @param  $data
     * @return bool
     */

    public function addRole($data)
    {
        return DB::table($this->role_table)->insert($data);
    }
}
