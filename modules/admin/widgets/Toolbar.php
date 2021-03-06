<?php

namespace app\modules\admin\widgets;

use Yii;
use yii\base\Widget;

class Toolbar extends Widget
{

    public function getItems()
    {
        $items = [];
        $user = Yii::$app->getUser();
        if (!$user->isGuest) {
            $identity = $user->getIdentity();
            $items[] = [
                'label' => $identity->getUsername() . ($identity->getRole() ? " [ {$identity->getRole()} ] " : ''),
                'url' => ['/admin/account/index'],
            ];

            $items[] = [
                'label' => Yii::t('app', 'Logout'),
                'url' => ['/admin/default/logout'],
                'template' => '<a id="logout" href="{url}">{label}</a>'
            ];
        }

        return $items;
    }

    public function run()
    {
        return $this->render('Toolbar', [
            'items' => $this->getItems(),
        ]);
    }

}
