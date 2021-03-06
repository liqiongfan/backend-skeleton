<?php

namespace app\modules\api\controllers;

use app\models\Member;
use app\modules\api\extensions\BaseController;
use app\modules\api\models\Constant;
use EasyWeChat\Foundation\Application;
use Yii;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;

/**
 * 微信接口处理
 * Class WechatController
 *
 * @package app\modules\api\controllers
 * @author hiscaler <hiscaler@gmail.com>
 */
class WechatController extends BaseController
{

    public function init()
    {
        parent::init();
        if (!isset(Yii::$app->params['wechat']) || !Yii::$app->params['wechat'] || !isset(Yii::$app->params['wechat']['app_id'], Yii::$app->params['wechat']['secret'])) {
            throw new InvalidConfigException('无效的微信配置。');
        }
    }

    /**
     * 微信认证
     *
     * @param $redirectUrl
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     */
    public function actionAuth($redirectUrl)
    {
        $db = Yii::$app->getDb();
        $application = new Application(Yii::$app->params['wechat']);
        $user = $application->oauth->scopes(['snsapi_userinfo'])->user();
        if ($user) {
            $openid = $user->openid;
            $memberId = $db->createCommand('SELECT [[member_id]] FROM {{%wechat_member}} WHERE [[openid]] = :openid', [':openid' => $openid])->queryScalar();
            if (!$memberId) {
                $member = new Member();
                $nickname = preg_replace('/([0-9#][\x{20E3}])|[\x{00ae}\x{00a9}\x{203C}\x{2047}\x{2048}\x{2049}\x{3030}\x{303D}\x{2139}\x{2122}\x{3297}\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $user->getNickname());
                $maxId = $db->createCommand('SELECT MAX[[id]] FROM {{%member}}')->queryScalar();
                $member->username = sprintf('wx%08d', $maxId + 1) . rand(1000, 9999);
                $member->nickname = $nickname ?: $member->username;
                $member->setPassword($member->username);
                $member->avatar = $user->headimgurl;
                $member->status = Member::STATUS_ACTIVE;
                if ($member->save()) {
                    $memberId = $member->id;
                    $columns = [
                        'member_id' => $memberId,
                        'subscribe' => Constant::BOOLEAN_TRUE,
                        'openid' => $openid,
                        'nickname' => $user->nickname,
                        'sex' => $user->sex,
                        'country' => $user->country,
                        'province' => $user->province,
                        'city' => $user->city,
                        'language' => $user->language,
                        'headimgurl' => $user->headimgurl,
                        'subscribe_time' => time(),
                    ];
                    $db->createCommand()->insert('{{%wechat_member}}', $columns)->execute();
                } else {
                    $memberId = null;
                }
            }
            if ($memberId) {
                $accessTokenExpire = isset(Yii::$app->params['user.accessTokenExpire']) ? (int) Yii::$app->params['user.accessTokenExpire'] : 7200;
                $accessTokenExpire = $accessTokenExpire ?: 7200;
                $accessToken = Yii::$app->getSecurity()->generateRandomString() . '.' . (time() + $accessTokenExpire);
                // Update user access_token value
                $db->createCommand()->update('{{%member}}', ['access_token' => $accessToken], ['id' => $memberId])->execute();
            } else {
                $accessToken = null;
            }

            $redirectUrl = urldecode($redirectUrl);
            if (strpos($redirectUrl, '?') === false) {
                $redirectUrl .= '?';
            } else {
                $redirectUrl .= '&';
            }
            $redirectUrl .= "accessToken=$accessToken";

            $this->redirect($redirectUrl);
        } else {
            throw new InvalidCallException('拉取微信认证失败。');
        }
    }

    /**
     * JsSdk 配置值
     *
     * @param null $url
     * @param string $apis
     * @param bool $debug
     * @param bool $beta
     * @return array|string
     */
    public function actionJssdk($url = null, $apis = '', $debug = false, $beta = true)
    {
        $validApis = ['checkJsApi', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ', 'onMenuShareWeibo', 'onMenuShareQZone'];
        $apis = array_filter(explode(',', $apis), function ($api) use ($validApis) {
            return $api && in_array($api, $validApis);
        });
        empty($apis) && $apis = ['checkJsApi'];

        $application = new Application(Yii::$app->params['wechat']);
        $js = $application->js;
        $url = $url ? urldecode($url) : Yii::$app->getRequest()->getHostInfo();
        $js->setUrl($url);
        $config = $js->config($apis, $debug, $beta, false);

        return $config;
    }

}
