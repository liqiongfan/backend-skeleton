<?php

namespace app\modules\api\controllers;

use app\modules\api\extensions\yii\filters\auth\AccessTokenAuth;
use yii\filters\auth\QueryParamAuth;

class AuthController extends Controller
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $token = \Yii::$app->getRequest()->get('accessToken');
        if (!empty($token)) {
            $class = AccessTokenAuth::className();
        } else {
            $class = QueryParamAuth::className();
        }

        $behaviors = array_merge($behaviors, [
            'authenticator' => [
                'class' => $class,
            ]
        ]);

        return $behaviors;
    }
}