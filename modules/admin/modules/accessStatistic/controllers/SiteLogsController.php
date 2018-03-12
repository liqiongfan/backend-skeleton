<?php

namespace app\modules\admin\modules\accessStatistic\controllers;

use app\modules\admin\components\QueryConditionCache;
use app\modules\admin\modules\accessStatistic\models\AccessStatisticSiteLog;
use app\modules\admin\modules\accessStatistic\models\AccessStatisticSiteLogSearch;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\NotFoundHttpException;

/**
 * SiteLogsController implements the CRUD actions for AccessStatisticSiteLog model.
 */
class SiteLogsController extends Controller
{

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'delete', 'to-excel'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all AccessStatisticSiteLog models.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AccessStatisticSiteLogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single AccessStatisticSiteLog model.
     *
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Deletes an existing AccessStatisticSiteLog model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * 导出为 Excel 文件
     *
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionToExcel()
    {
        $query = QueryConditionCache::get(AccessStatisticSiteLog::class);
        if ($query) {
            $items = $query->orderBy(['ip' => SORT_ASC])->all();
        } else {
            $items = AccessStatisticSiteLog::find()->all();
        }
        $phpExcel = new PHPExcel();

        $phpExcel->getProperties()->setCreator("Microsoft")
            ->setLastModifiedBy("Microsoft")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Access Statistics");

        $phpExcel->setActiveSheetIndex(0);
        $activeSheet = $phpExcel->getActiveSheet();
        $phpExcel->getDefaultStyle()
            ->getFont()->setSize(14);

        $activeSheet->getDefaultRowDimension()->setRowHeight(25);

        $cols = ['A' => 4, 'B' => 16, 'C' => 30, 'D' => 12, 'E' => 12, 'F' => 12, 'G' => 20];
        foreach ($cols as $col => $width) {
            $activeSheet->getColumnDimension($col)->setWidth($width);
        }

        $activeSheet->setCellValue('A1', '访问统计')->mergeCells('A1:F1')->getStyle()->applyFromArray(array(
            'font' => array(
                'bold' => true,
                'size' => 16,
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
        ));

        $activeSheet->setCellValue("A2", '序号')
            ->setCellValue("B2", 'IP 地址')
            ->setCellValue("C2", '来源')
            ->setCellValue("D2", '浏览器')
            ->setCellValue("E2", '浏览器语言')
            ->setCellValue("F2", '操作系统')
            ->setCellValue("G2", '访问时间');

        $formatter = Yii::$app->getFormatter();
        $row = 3;
        foreach ($items as $i => $item) {
            $activeSheet->setCellValue("A{$row}", $i + 1)
                ->setCellValue("B{$row}", $item['ip'])
                ->setCellValue("C{$row}", $item['referrer'])
                ->setCellValue("D{$row}", $item['browser'])
                ->setCellValue("E{$row}", $item['browser_lang'])
                ->setCellValue("F{$row}", $item['os'])
                ->setCellValue("G{$row}", $formatter->asDatetime($item['access_datetime']));
            $row++;
        }

        $phpExcel->getActiveSheet()->setTitle('汇总表');
        $phpExcel->setActiveSheetIndex(0);
        $objWriter = PHPExcel_IOFactory::createWriter($phpExcel, 'Excel2007');
        $filename = '汇总表.xlsx';
        $file = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . urlencode($filename);
        $objWriter->save($file);

        Yii::$app->getResponse()->sendFile($file, $filename, ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Finds the AccessStatisticSiteLog model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     * @return AccessStatisticSiteLog the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AccessStatisticSiteLog::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
