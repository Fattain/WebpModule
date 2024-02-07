<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

IncludeModuleLangFile(__FILE__);

$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = htmlspecialchars($request['mid'] != '' ? $request['mid'] : $request['id']);
Loader::includeModule($module_id);


$aTabs = array(
    array(

        'DIV'     => 'edit1',
        'TAB'     => GetMessage("ANYERA_WEBP_OSNOVNYE_NASTROYKI"),
        'TITLE'   => GetMessage("ANYERA_WEBP_OSNOVNYE_NASTROYKI"),
        'OPTIONS' => array(
            array(
                'picture_quality',                                   
                GetMessage("ANYERA_WEBP_KACESTVO_VYHODASEGO"), 
                '80',                                          
                array('text', 3)                              
            ),
        )
    )
);


$tabControl = new CAdminTabControl(
    'tabControl',
    $aTabs
);

$tabControl->begin();
?>
<form action="<?= $APPLICATION->getCurPage(); ?>?mid=<?=$module_id; ?>&lang=<?= LANGUAGE_ID; ?>" method="post">
    <?= bitrix_sessid_post(); ?>
    <?php
    foreach ($aTabs as $aTab) {
        if ($aTab['OPTIONS']) {
            $tabControl->beginNextTab();
            __AdmSettingsDrawList($module_id, $aTab['OPTIONS']);?>
            <?= BeginNote()?>
              <?= GetMessage("ANYERA_WEBP_CONVERT_MESSAGE")?>
            <?= EndNote()?>
            <?
        }
    }?>

    
    <?$tabControl->buttons();?>
    <input type="submit" name="apply" 
           value="<?=GetMessage("ANYERA_WEBP_SOHRANITQ_NASTROYKI")?>" class="adm-btn-save" />
    <input type="submit" name="default"
           value="<?=GetMessage("ANYERA_WEBP_VOSSTANOVITQ_PO_UMOL")?>" />
    <input type="submit" name="convertAll"
           value="<?=GetMessage("ANYERA_WEBP_CONVERT_ALL")?>" />
</form>

<?php
$tabControl->end();


if ($request->isPost() && check_bitrix_sessid()) {

    foreach ($aTabs as $aTab) { 
        foreach ($aTab['OPTIONS'] as $arOption) {
            if (!is_array($arOption)) { 
                continue;
            }
            if ($arOption['note']) { 
                continue;
            }
            if ($request['apply']) {
                $optionValue = $request->getPost($arOption[0]);
                Option::set($module_id, $arOption[0], is_array($optionValue) ? implode(',', $optionValue) : $optionValue);
            } elseif ($request['default']) { 
                Option::set($module_id, $arOption[0], $arOption[2]);
            }
            elseif ($request['convertAll']){
              AnyeraWebp\Webp::convertAllElements();
            }
        }
    }

    LocalRedirect($APPLICATION->getCurPage().'?mid='.$module_id.'&lang='.LANGUAGE_ID);

}
?>