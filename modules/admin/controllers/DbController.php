<?php

namespace app\modules\admin\controllers;

use DateTime;
use EasyWeChat\Store\Store;
use PDOException;
use Yii;
use yii\db\Query;
use yii\helpers\FileHelper;
use yii\helpers\VarDumper;
use yii\web\NotFoundHttpException;

/**
 * 数据库管理
 *
 * @package app\modules\admin\controllers
 * @author hiscaler <hiscaler@gmail.com>
 */
class DbController extends Controller
{

    /**
     * 备份历史记录
     *
     * @return string
     */
    public function actionIndex()
    {
        $histories = FileHelper::findDirectories(Yii::getAlias('@app/backup'));
        foreach ($histories as $key => $history) {
            $name = basename($history, '.bak');
            $histories[$key] = [
                'name' => $name,
                'date' => (new DateTime($name))->format('Y-m-d H:i:s'),
            ];
        }

        return $this->render('index', [
            'histories' => $histories,
        ]);
    }

    /**
     * 数据库备份
     *
     * @return string
     * @throws \yii\base\Exception
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     */
    public function actionBackup()
    {
        $pageSize = 100;
        $db = Yii::$app->getDb();
        $schema = $db->getSchema();
        $tablePrefix = $db->tablePrefix;
        $tables = $schema->getTableNames();
        $backupDir = date('Ymd');
        $backupPath = Yii::getAlias('@app/backup/' . $backupDir);
        if (!file_exists($backupDir)) {
            FileHelper::createDirectory($backupPath);
        }

        $processTables = [];
        foreach ($tables as $table) {
            $totalCount = $db->createCommand("SELECT COUNT(*) FROM $table")->queryScalar();
            $processTables[$table] = 0;
            if (!$totalCount) {
                continue;
            }
            $totalPages = (int) (($totalCount + $pageSize - 1) / $pageSize);
            for ($page = 1; $page <= $totalPages; $page++) {
                $data = (new Query())
                    ->from($table)
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->all();
                $processTables[$table] += count($data);
                $data = gzcompress(serialize([
                    'table' => str_replace($tablePrefix, '', $table),
                    'data' => $data,
                ]), 9);
                file_put_contents($backupPath . DIRECTORY_SEPARATOR . "$table-$page.bak", $data);
            }
        }

        return $this->render('backup', [
            'processTables' => $processTables,
        ]);
    }

    /**
     * 恢复数据库数据
     *
     * @param $name
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     */
    public function actionRestore($name)
    {
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 0);
        $path = Yii::getAlias("@app/backup/$name");
        if (file_exists($path)) {
            $db = Yii::$app->getDb();
            $cmd = $db->createCommand();
            $tablePrefix = $db->tablePrefix;
            $tables = $db->getSchema()->getTableNames();
            $files = FileHelper::findFiles($path);
            $currentTable = null;
            foreach ($files as $file) {
                $data = file_get_contents($file);
                if ($data !== false) {
                    $rawData = gzuncompress($data);
                    if ($rawData !== false) {
                        $rawData = unserialize($rawData);
                        $table = $tablePrefix . $rawData['table'];
                        if (!in_array($table, $tables)) {
                            continue;
                        }
                        $tableSchema = $db->getTableSchema($table);
                        foreach ($tableSchema->foreignKeys as $def) {
                            $tmpTable = $def[0];
                            $tmpTableSchema = $db->getTableSchema($tmpTable);
                            foreach ($tmpTableSchema->foreignKeys as $tmpDef) {
                                $cmd->delete($tmpDef[0])->execute();
                            }
                            $cmd->delete($tmpTable)->execute();
                        }

                        if ($currentTable != $table) {
                            $cmd->truncateTable($table)->execute();
                        }
                        $rows = $rawData['data'];
                        if (!$rows) {
                            continue;
                        }
                        $cmd->batchInsert($table, array_keys($rows[0]), $rows)->execute();
                    } else {
                        throw new \Exception(basename($file) . ' 文件读取失败。');
                    }
                }
            }
        } else {
            throw new NotFoundHttpException("$name 备份不存在。");
        }
    }

}