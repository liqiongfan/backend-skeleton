<?php

namespace app\modules\admin\controllers;

use cebe\markdown\GithubMarkdown;
use cebe\markdown\Markdown;
use cebe\markdown\MarkdownExtra;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * Class HelpController
 *
 * @package app\modules\admin\controllers
 * @author hiscaler <hiscaler@gmail.com>
 */
class HelpController extends Controller
{

    /**
     * 文档查看
     *
     * @param string $file
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionIndex($file = 'about')
    {
        $filePath = Yii::getAlias('@app/docs/' . strtolower($file) . '.md');
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $markdown = new GithubMarkdown();

            $this->layout = 'help';

            return $this->render('index.php', [
                'body' => $markdown->parse($content),
            ]);
        }
        throw new NotFoundHttpException("$file 文档不存在。");
    }
}