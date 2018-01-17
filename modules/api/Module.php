<?php

namespace app\modules\api;

use Yii;
use yii\web\Response;

/**
 * api module definition class
 */
class Module extends \yii\base\Module
{

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\api\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        \Yii::$app->setComponents([
            'user' => [
                'class' => 'yii\web\User',
                'identityClass' => 'app\modules\api\models\Member',
                'identityCookie' => ['name' => '_identity_api', 'httpOnly' => true],
                'idParam' => '__id_api',
                'enableAutoLogin' => true,
            ],
            'response' => [
                'class' => 'yii\web\Response',
                'on beforeSend' => function ($event) {
                    $response = $event->sender;
                    if ($response->data !== null && $response->isSuccessful) {
                        $response->data = [
                            'success' => true,
                            'data' => $response->data,
                        ];
                        $response->statusCode = 200;
                    } else {
                        $response->data = [
                            'success' => false,
                            'error' => $response->data,
                        ];
                    }
                },
            ],
        ]);
        $response = Yii::$app->getResponse();
        $response->format && $response->format = Response::FORMAT_JSON;
    }
}
