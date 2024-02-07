<?php
namespace AnyeraWebp;
use Bitrix\Main\Config\Option;
use Bitrix\Main;


class Webp {
  static $MODULE_ID="anyera.webp";

  public static function makeWebp($img, $path, $quality){
    imagepalettetotruecolor($img);
    imagealphablending($img, true);
    imagesavealpha($img, true);
    imagewebp($img, $path, $quality); // 80 - standard quality
    imagedestroy($img);
  }
  
  public static function imageCreateFromAny($file){
    $s = exif_imagetype($file);
    switch($s){
      case IMAGETYPE_JPEG:
        return imagecreatefromjpeg($file);
        break;
  
      case IMAGETYPE_PNG:
        return imagecreatefrompng($file);
        break;
  
      case IMAGETYPE_BMP:
        return imagecreatefrombmp($file);
        break;
      
      default:
        return false;
    }
  }

  public static function changePropertyImgToWebp(&$arParams){
    if(empty($arParams['PROPERTY_VALUES'])){
      return true;
    }

    $quality = (int) Option::get('anyera.webp', 'picture_quality', '80');

    $standartProperties = ['PREVIEW_PICTURE', 'DETAIL_PICTURE'];
    foreach($standartProperties as $property){
      if(!isset($arParams[$property]) || !isset($arParams[$property]['tmp_name'])){
        continue;
      }

      $file = $arParams[$property]['tmp_name'];
      $img = Webp::imageCreateFromAny($file);
      if($img){
        $name = str_replace(['.jpg', '.jpeg', '.png', '.bmp', '.gif'], '', $arParams[$property]['name']);
        $path = mb_substr($file, 0, -7).$name.'.webp';

        unlink($file);
        \CModule::IncludeModule("main");
        Webp::makeWebp($img, $path, $quality);
        $fileArray = \CFile::MakeFileArray($path);
        $arParams[$property] = $fileArray;
      }
      else{
        return true;
      }
    }

    foreach($arParams['PROPERTY_VALUES'] as $key=>$array){
      foreach($array as $key2=>$array2){
        if(!isset($array2['VALUE']) || !isset($array2['VALUE']['type'])){
          continue;
        }
        
        if(preg_match('/(jpg|gif|bmp|png|jpeg|webp)/i', $array2['VALUE']['type'])){
          $file = $array2['VALUE']['tmp_name'];
          $img = Webp::imageCreateFromAny($file);
          if($img){
            $name = str_replace(['.jpg', '.jpeg', '.png', '.bmp', '.gif'], '', $array2['VALUE']['name']);
            $path = mb_substr($file, 0, -7).$name.'.webp';
            unlink($file);
            \CModule::IncludeModule("main");
            Webp::makeWebp($img, $path, $quality);
            $fileArray = \CFile::MakeFileArray($path);
            $arParams['PROPERTY_VALUES'][$key][$key2]['VALUE'] = $fileArray;
          }
          else{
            return true;
          }
        }
      }
    }
    return true;
  }


  public static function convertAllElements(){
    if (\CModule::IncludeModule("iblock")){
      $quality = (int) Option::get('anyera.webp', 'picture_quality', '80');

      $res = [];
      $el = new \CIBlockElement;
      
      
      $dbIblocks = \CIBlock::GetList();
      while($arIblocks = $dbIblocks->GetNext()){
        $res[]['ID'] = $arIblocks['ID'];
      }
      
      foreach($res as $key => $value){
        $dbProps = \CIBlock::GetProperties($value['ID']);
        while ($arProps = $dbProps -> GetNext()){
          if($arProps["PROPERTY_TYPE"]==='F'){
            $res[$key]['PROPERTIES'][]['CODE'] = $arProps['CODE'];
          }
        }
      }
      
      foreach($res as $key => $value){
        if(!isset($value['PROPERTIES'])){
          unset($res[$key]);
        }
      }
      $res = array_values($res);
      
      $elements = [];
      
      foreach($res as $key => $value){
        $properties = ['PREVIEW_PICTURE', "DETAIL_PICTURE"];
        foreach($value['PROPERTIES'] as $subkey => $subvalue){
          $properties[] = 'PROPERTY_'.$subvalue['CODE'];
        }
        $filter = [];
        $filter['IBLOCK_ID'] = $value['ID'];
        foreach($properties as $prop => $code){
          $code = '!'.$code;
          $filter[$code] = "";
        }
        $dbElements = \CIBlockElement::GetList(array("sort"=>"asc"), $filter, false,false, array('IBLOCK_ID', 'ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'));
        while ($arElements = $dbElements->GetNextElement()){
          $props = $arElements->GetProperties(array(), array('PROPERTY_TYPE'=>'F', 'EMPTY'=>'N'));
          $fields = $arElements->GetFields();
          if($fields['PREVIEW_PICTURE']!==NULL) $elements[$fields['ID']]['PICS']['PREVIEW_PICTURE']=$fields['PREVIEW_PICTURE'];
          if($fields['DETAIL_PICTURE']!==NULL) $elements[$fields['ID']]['PICS']['DETAIL_PICTURE']=$fields['DETAIL_PICTURE'];
          echo '<pre>';
          var_dump($elements[$fields['ID']['PICS']]);
          echo '</pre>';
          $elements[$fields['ID']]['PROPERTIES'] = $props;
          $elements[$fields['ID']]['IBLOCK_ID'] = $fields['IBLOCK_ID'];
        }
      }

      foreach($elements as $id => $props){
        $picsConverted = 0;
        $resultProperties = [];
        foreach($props['PROPERTIES'] as $code => $value){
          if(!is_array($value['VALUE'])){
            $temp = $value['VALUE'];
            unset($value['VALUE']);
            $value['VALUE'][] = $temp;
          }
          foreach($value['VALUE'] as $key => $picID){
            $file = \CFile::GetPath($picID);
            if(\CFile::IsImage($file)){
              $file = $_SERVER['DOCUMENT_ROOT'].$file;
              $img = Webp::imageCreateFromAny($file);
              if($img){
                $path = str_replace(['.jpg', '.jpeg', '.png', '.bmp', '.gif', '.webp'], '.webp', $file);
                unlink($file);
                Webp::makeWebp($img, $path, $quality);
                $fileArray = \CFile::MakeFileArray($path);
                if($temp) $resultProperties[$code]['VALUE'] = $fileArray;
                else {
                  $result = [];
                  $result['VALUE'] = $fileArray;
                  //$result['DESCRIPTION'] = 'Anyera';
                  $resultProperties[$code][] = $result;
                }
                $picsConverted++;
              }
              else{
                $fileArray = \CFile::MakeFileArray($picID);
                if($temp) $resultProperties[$code]['VALUE'] = $fileArray;
                else {
                  $result = [];
                  $result['VALUE'] = $fileArray;
                  //$result['DESCRIPTION'] = 'Anyera';
                  $resultProperties[$code][] = $result;
                }
                continue;
              }
            }
            else {
              $fileArray = \CFile::MakeFileArray($picID);
              if($temp) $resultProperties[$code]['VALUE'] = $fileArray;
              else {
                $result = [];
                $result['VALUE'] = $fileArray;
                //$result['DESCRIPTION'] = 'Anyera';
                $resultProperties[$code][] = $result;
              }
              continue;
            }
          }
        }

        if(!empty($resultProperties) && $picsConverted>0){
          \CIBlockElement::SetPropertyValuesEx($id, $props['IBLOCK_ID'], $resultProperties);
        }
        if(isset($props['PICS'])){
          $picsArray = [];
          foreach($props['PICS'] as $picType => $picID){
            $file = \CFile::GetPath($picID);
            if(\CFile::IsImage($file)){
              $file = $_SERVER['DOCUMENT_ROOT'].$file;
              $img = Webp::imageCreateFromAny($file);
              if($img){
                $path = str_replace(['.jpg', '.jpeg', '.png', '.bmp', '.gif', '.webp'], '.webp', $file);
                unlink($file);
                Webp::makeWebp($img, $path, $quality);
                $fileArray = \CFile::MakeFileArray($path);
                $picsArray[$picType] = $fileArray;
              }
            }
          }
          if(!empty($picsArray)){
            $picsArray['TIMESTAMP_X'] = FALSE;
            $res = $el->Update($id, $picsArray);
          }
        }
      }
    }
    return true;
  }
}