<?php
/**
 * @var $this yii\web\View
 * @var $guide app\models\Guide
 */
use yii\helpers\Html;

$this->title = $guide->title;
$blocksPerRow = 4;
?>
<div class="container guide-view lang-<?= $guide->language ?>">
    <div class="row">
        <div class="col-xs-7 col-sm-8">
            <h1 class="guide-headline"><?= Html::encode($guide->title) ?></h1>
        </div>
        <div class="col-xs-5 col-sm-4">
            <?= $this->render('_versions.php', ['guide' => $guide, 'section' => null]) ?>
        </div>
    </div>

    <div class="guide-content content">

        <div class="row">
    <?php
    $count = 0;
    foreach ($guide->chapters as $chapterTitle => $sections) {
        if ($count && $count % $blocksPerRow === 0) {
            echo '</div><div class="row">';
        }
    ?>
        <div class="col-sm-6 col-md-3">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><?= Html::encode($chapterTitle) ?></h3>
                </div>
                <div class="panel-body">
                <?= Html::ul($sections, ['item' => function ($name, $title) use ($guide) {
                    return '<li>' . Html::a(Html::encode($title), ['guide/view',
                        'section' => $name,
                        'language' => $guide->language,
                        'version' => $guide->version,
                        'type' => $guide->typeUrlName
                    ]) . '</li>';
                }, 'class' => 'list-unstyled']) ?>
                </div>
            </div>
        </div>
    <?php
        $count++;
    }
    ?>
        </div>
</div>
</div>
