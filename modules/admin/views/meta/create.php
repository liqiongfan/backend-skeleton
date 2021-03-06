<?php
/* @var $this yii\web\View */
/* @var $model app\models\Meta */

$this->title = Yii::t('app', 'Create {modelClass}', [
    'modelClass' => Yii::t('model', 'Meta'),
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Meta'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->params['menus'] = [
    ['label' => Yii::t('app', 'List'), 'url' => ['index']],
    ['label' => Yii::t('app', 'Create'), 'url' => ['create']],
];
?>
<div class="meta-create">
    <?=
    $this->render('_form', [
        'model' => $model,
    ])
    ?>
</div>
