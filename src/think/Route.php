<?php

/*
 * This file is part of the tp5er/think-auth
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * (L) Licensed <https://opensource.org/license/MIT>
 *
 * (A) zhiqiang <https://www.zhiqiang.wang>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\think\auth\think;

use think\facade\Route as thinkRoute;
use tp5er\think\auth\contracts\Authenticatable;
use tp5er\think\auth\facade\Gate;
use tp5er\think\auth\User;

class Route
{
    public static function api()
    {
        //定义一个演示的权限
        Gate::define('edit-settings', function (Authenticatable $authenticatable) {
            return true;
        });

        thinkRoute::get("/api/register", function () {
            //TODO 自己根据实际需求进行注册
            $user = new User();
            $user->name = "tp5er";
            $user->password = hash_make("123456");
            $user->email = "tp5er@qq.com";
            $user->save();

            return json(["code" => 0, "msg" => $user]);
        });

        thinkRoute::get("/api/login", function () {
            //TODO 自己根据实际需求进行登录
            auth()->attempt(["name" => "admin", "password" => "admin"], true);

            return json(["code" => 0, "msg" => "登录成功"]);
        });
        thinkRoute::get("/api/user", function () {
            $user = requesta()->user();
            //$user=  auth()->user();

            return json(["code" => 0, "msg" => "获取登录信息", "data" => $user]);
        });

        thinkRoute::get("/api/scan", function () {
            $ret = [];
            if (Gate::allows('edit-settings')) {
                $ret["edit-settings"] = "有权限";
            } else {
                $ret["edit-settings"] = "无权限";
            }

            if (Gate::allows('delete-settings')) {
                $ret["delete-settings"] = "有权限";
            } else {
                $ret["delete-settings"] = "无权限";
            }

            return json(["code" => 0, "msg" => "获取权限列表", 'data' => $ret]);
        });

        thinkRoute::get("/api/token", function () {
            //$user = requesta()->user();
            $user = auth()->user();
            $token = $user->createToken("test-token");

            return json(["code" => 0, "msg" => "获取token信息", "data" => ["token" => $token->plainTextToken]]);
        });

        Route::sanctum();
        Route::jwt();
    }

    public static function sanctum()
    {
        thinkRoute::get("/api/sanctum", function () {
            //TODO 逻辑
            // 1. 首先判断你是否完成登录，通过默认guard中获取用户信息，如果有用户进行就直接返回
            // 2. 如果在默认的guard没有获取到用户信息就通过header中获取Authorization，然后进行获取用户信息
            // 3. Authorization是用`/api/token`中拿到的token，然后进字符串拼接成：（Bearer token）放在header中Bearer 参考curl
            // curl -H "Authorization: Bearer 1|eQdtbpVmxShtySPRAyOtIjbFkyaoHNRTErLkTgge"  "http://127.0.0.1:8000/api/sanctum"
            // 注意： 使用sanctum必须使用模型，database 无法进行access权限验证

            //$user = requesta()->user();
            $user = auth()->user();

            return json(["code" => 0, "msg" => "通过sanctum获取用户信息", "data" => $user]);
        })->middleware('auth', "sanctum");

        thinkRoute::get("/api/tokencan", function () {
            //$user = requesta()->user();
            $user = auth()->user();
            $ret = [];
            //TODO 默认accessToken是tp5er\think\auth\sanctum\TransientToken
            // 此处无论是什么都有权限的哦
            // 可以使用withAccessToken(HasAbilities $accessToken) 进行自定义
            if ($user->tokenCan("edit-settings")) {
                $ret["tokenCan"] = "有权限";
            } else {
                $ret["tokenCan"] = "无权限";
            }
            //TODO Gate 定义的关系
            if ($user->can("edit-settings")) {
                $ret["edit-settings"] = "有权限";
            } else {
                $ret["edit-settings"] = "无权限";
            }
            if ($user->can('delete-settings')) {
                $ret["delete-settings"] = "有权限";
            } else {
                $ret["delete-settings"] = "无权限";
            }

            return json(["code" => 0, "msg" => "获取权限列表", 'data' => $ret]);
        })->middleware('auth', "sanctum");
    }

    protected static function jwt()
    {
        thinkRoute::get("/jwt/token", function () {
            $token = auth('jwt')
                //有效期1分钟,默认是1小时
//                ->setTTL(1)
                ->attempt(["name" => "admin", "password" => "admin"]);

            return json([
                "code" => 0,
                "msg" => "获取token信息",
                "data" => [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth('jwt')->factory()->getTTL() * 60,
                    'claims' => auth('jwt')->getPayload(),
                    'user' => auth('jwt')->user(),
                ]
            ]);
        });
        thinkRoute::post("/jwt/user", function () {
            $user = auth('jwt')->user();

            return json([
                "code" => 0,
                "msg" => "获取用户信息",
                "data" => $user
            ]);
        })->middleware('auth', "jwt");

        thinkRoute::get("/jwt/logout", function () {
            auth('jwt')->logout();

            return json([
                "code" => 0,
                "msg" => "退出登录",
            ]);
        })->middleware('auth', "jwt");

        thinkRoute::get("/jwt/refresh", function () {
            $token = auth('jwt')->parseToken()->getToken()->get();
            $newtoken = auth('jwt')->parseToken()->refresh();

            return json([
                "code" => 0,
                "msg" => "刷新token成功",
                "data" => [
                    "token" => $token,
                    'refresh_token' => $newtoken,
                    'token_type' => 'bearer',
                    'expires_in' => auth('jwt')->factory()->getTTL() * 60
                ]
            ]);
        })->middleware('auth', "jwt");
    }
}
