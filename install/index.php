<?
IncludeModuleLangFile(__FILE__);


Class anyera_webp extends CModule
{
    var $MODULE_ID = "anyera.webp";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;

    function anyera_webp()
    {
        $arModuleVersion = array();

        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path."/version.php");
        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
        $this->MODULE_NAME = getMessage("ANYERA_WEBP_KONVERTER");
        $this->MODULE_DESCRIPTION = getMessage("ANYERA_WEBP_KONVERTACIA_IZOBRAJE");
        $this->PARTNER_NAME = "Anyera";
		    $this->PARTNER_URI = "https://anyera.promo/";
    }

    function DoInstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;
        RegisterModuleDependences("iblock","OnBeforeIBlockElementUpdate",$this->MODULE_ID,"AnyeraWebp\\Webp","changePropertyImgToWebp");
        RegisterModuleDependences("iblock","OnBeforeIBlockElementAdd",$this->MODULE_ID,"AnyeraWebp\\Webp","changePropertyImgToWebp");
        // Install events
        RegisterModule($this->MODULE_ID);
        $APPLICATION->IncludeAdminFile(getMessage("ANYERA_WEBP_USTANOVKA_MODULA").$this->MODULE_ID, __DIR__."/step.php");
        return true;
    }

    function DoUninstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;
        UnRegisterModuleDependences("iblock","OnBeforeIBlockElementUpdate",$this->MODULE_ID,"AnyeraWebp\\Webp","changePropertyImgToWebp");
        UnRegisterModuleDependences("iblock","OnBeforeIBlockElementAdd",$this->MODULE_ID,"AnyeraWebp\\Webp","changePropertyImgToWebp");
        UnRegisterModule($this->MODULE_ID);
        $APPLICATION->IncludeAdminFile(getMessage("ANYERA_WEBP_DEINSTALLACIA_MODULA").$this->MODULE_ID, __DIR__."/unstep.php");
        return true;
    }
}