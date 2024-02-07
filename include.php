<?php
CModule::IncludeModule("anyera.webp");
global $DBType;

$arClasses=array(
    'AnyeraWebp\\Webp'=>'classes/general/Webp.php'
);

CModule::AddAutoloadClasses("anyera.webp",$arClasses);
