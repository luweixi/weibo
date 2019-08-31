<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function gravatar($size = '100')
    {
        $hash = md5(strtolower(trim($this->attributes['email'])));
        return "http://www.gravatar.com/avatar/$hash?s=$size";
    }

    /**
     * 监听模型创建前事件
     * @return [type] [description]
     */
    public static function boot()
    {
        parent::boot();
        static::creating(function ($user) {
            $user->activation_token = Str::random(10);
        });
    }

    /**
     * 关联用户状态表
     * @return [type] [description]
     */
    public function statuses()
    {
        //一对多关系
        return $this->hasMany(Status::class);
    }

    /**
     * 取出用户状态表数据，并排序
     * @return [type] [description]
     */
    public function feed()
    {
        return $this->statuses()->orderBy('created_at', 'desc');
    }

    /**
     * 粉丝列表, 被关注人(user_id)->粉丝(follower_id)
     * @return [type] [description]
     */
    public function followers()
    {
        //多对多关系
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'follower_id');
    }

    /**
     * 关注列表，粉丝身份(follower_id)->被关注人(user_id)
     * @return [type] [description]
     */
    public function followings()
    {
        //多对多关系
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'user_id');
    }

    /**
     * 关注
     * @param  [type] $user_ids [description]
     * @return [type]           [description]
     */
    public function follow($user_ids)
    {
        if (!is_array($user_ids)) {
            $user_ids = compact('user_ids');
        }
        $this->followings()->sync($user_ids, false); //false-合并操作, true-替换操作
    }

    /**
     * 取消关注
     * @param  [type] $user_ids [description]
     * @return [type]           [description]
     */
    public function unfollow($user_ids)
    {
        if (!is_array($user_ids)) {
            $user_ids = compact('user_ids');
        }
        $this->followings()->detach($user_ids);
    }

    /**
     * 是否关注过指定的用户
     *  // 1. $this->followings() 返回的是一个 HasMany 对象(Relations)
        // 2. $this->followings 返回的是一个 Collection 集合
        // 3. 第2个其实相当于这样 $this->followings()->get()
        // 如果不需要条件直接使用 2 那样，写起来更短
     * @param  [type]  $user_id [description]
     * @return boolean          [description]
     */
    public function isFollowing($user_id)
    {
        return $this->followings->contains($user_id);
    }

}
