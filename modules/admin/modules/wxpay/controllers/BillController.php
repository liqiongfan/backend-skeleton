<?php

namespace app\modules\admin\modules\wxpay\controllers;

use app\modules\admin\extensions\BaseController;
use app\modules\admin\modules\wxpay\forms\BillDownload;
use EasyWeChat\Foundation\Application;
use Yii;
use yii\helpers\VarDumper;

/**
 * 对账单管理
 *
 * @package app\modules\admin\modules\wxpay\controllers
 * @author hiscaler <hsicaler@gmail.com>
 */
class BillController extends BaseController
{

    public function actionIndex()
    {
        if (!isset(Yii::$app->params['wechat']) || !is_array(Yii::$app->params['wechat'])) {
            throw new InvalidConfigException('无效的微信参数配置。');
        }

        $model = new BillDownload();
        if ($model->load(Yii::$app->getRequest()->post()) && $model->validate()) {
            $application = new Application(Yii::$app->params['wechat']);
            $payment = $application->payment;
            $bill = $payment->downloadBill($model->date, $model->type)->getContents();
            Yii::$app->getResponse()->sendContentAsFile($bill, 'bill-' . $model->date . '.csv');
            Yii::$app->end();
        }

        return $this->render('index', [
            'model' => $model,
        ]);
    }

}