<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract,JWTSubject
{
    use Authenticatable, Authorizable;


    protected $user_table = "users";
    protected $role_table = "roles";
    protected $user_role_table = "users_roles";
    protected $auth_table = "auths";
    protected $role_auth_table = "roles_auths";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username'
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
     * Handle an incoming request.
     *
     * @param  $id 用户id
     * @param  $path用户访问的path
     * @param  $method请求的方式
     * @return bool
     */
    public function hasAccess($id,$path,$method){
        $users = DB::table($this->user_role_table)
            ->leftJoin($this->role_auth_table, $this->user_role_table.'.role_id', '=', $this->role_auth_table.'.role_id')
            ->leftJoin($this->auth_table, $this->role_auth_table.'.auth_id', '=', $this->auth_table.'.id')
            ->where([[$this->user_role_table.'.user_id','=',$id],[$this->auth_table.'.path','=',$path],[$this->auth_table.'.method','=',$method]])
            ->select($this->auth_table.'.id')
            ->get();

        $user = json_decode($users,true);

        if(!$user){
            return false;
        }
        return true;
    }

    /**
     * 修改用户登录ip
     *
     * @param integer $id 用户id
     * @param mixed $ip 用户ip
     * @return bool
     */
    public function setLastIp($id,$ip){
        $res = DB::table($this->user_table)->where('id','=',$id)->update(['last_ip' => $ip,'last_time' => time()]);
        if(!$res>0){
            return false;
        }
        
        return true;
    }

    /**
     * 获取用户列表
     *
     * @param  $num 每页数量
     * @param  $page 页数
     * @return array
     */
    public function getUserList($page,$num){
        $res['list'] = DB::table($this->user_table)->select(["id","username","phone_num","email","created_time","last_time","last_ip","avatar","status"])->orderBy("status","asc")->orderBy("id","asc")->offset(($page-1)*$num)->limit($num)->get();
        $res['count'] = DB::table($this->user_table)->count();
        $res['page'] = ceil(count($res['list'])/$num);
        return $res;
    }

    /**
     * 通过用户id查询用户信息
     *
     * @param  $userid 用户id
     * @return bool
     */
    public function getUserInfoById($userid){
        $res = DB::table($this->user_table)->select(["id","username","phone_num","email","created_time","avatar","status"])->where('id','=',$userid)->first();
        return $res;
    }

    /**
     * 判断用户是否存在
     *
     * @param  $userid 用户id
     * @return bool
     */

    public function hasUser($userid){
        return Db::table($this->user_table)->where('id','=',$userid)->exists();
    }

    /**
     * 判断用户名是否存在
     *
     * @param  $username 用户名
     * @return bool
     */

    public function hasUsername($username){
        return Db::table($this->user_table)->where('username','=',$username)->exists();
    }

    /**
     * 添加用户
     *
     * @param  array $data
     * @return bool
     */

    public function addUser($data){
        return Db::table($this->user_table)->insert($data);
    }

    /**
     * 修改用户信息
     *
     * @param  int $userId 用户id
     * @param  array $data 用户id
     * @return bool
     */

    public function editUser($userId,$data){
        return Db::table($this->user_table)->where('id','=',$userId)->update($data);
    }
    
}
