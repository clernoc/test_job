<?
namespace test_archive;

use Bitrix\Main\Loader;
use Bitrix\Main, Bitrix\Iblock;
if (! Loader::includeModule("fileman"))
    exit();
if (! Loader::includeModule("iblock"))
    exit();

    
// AddEventHandler("main", "OnAdminContextMenuShow", [
//     '\test_archive\events',
//     'OnAdminContextMenuShow_addArchiveButton'
// ]);

// AddEventHandler("main", "OnProlog", [
//     '\test_archive\events',
//     "OnProlog_ArchiveEventHandler"
// ]);

class events
{

    function OnAdminContextMenuShow_addArchiveButton(&$items, &$second)
    {
        // $request = Context::getCurrent()->getRequest();
        if ($GLOBALS["APPLICATION"]->GetCurPage(true) == "/bitrix/admin/iblock_list_admin.php" && isset($_REQUEST['IBLOCK_ID'])) {
            
            if ($_REQUEST['type'] != 'archivarius_type') {
                $items[] = array(
                    "TEXT" => "Добавить в архив",
                    "LINK" => $_SERVER['REQUEST_URI'] . '&action=archive',
                    "TITLE" => "Создать архивную копию, начиная с этого раздела",
                    "ICON" => "btn_new"
                );
            }
        }
    }

    function getPropertiesArray($IBLOCK_ID = 0, $by_code = FALSE)
    {
        if ($IBLOCK_ID <= 0) {
            return false;
        }
        
        $properties = \CIBlockProperty::GetList(Array(
            "sort" => "asc",
            "name" => "asc"
        ), Array(
            "ACTIVE" => "Y",
            "IBLOCK_ID" => $IBLOCK_ID
        ));
        
        $properties_array = [];
        
        while ($prop_fields = $properties->GetNext()) {
            if ($prop_fields["PROPERTY_TYPE"] == "L") {
                $property_enums = \CIBlockPropertyEnum::GetList(Array(
                    "DEF" => "DESC",
                    "SORT" => "ASC"
                ), Array(
                    "IBLOCK_ID" => $IBLOCK_ID,
                    "CODE" => $prop_fields["CODE"]
                ));
                while ($enum_fields = $property_enums->GetNext()) {
                    $prop_fields["VALUES"][] = Array(
                        "VALUE" => $enum_fields["VALUE"],
                        "DEF" => $enum_fields["DEF"],
                        "SORT" => $enum_fields["SORT"]
                    );
                }
            }
            
            if ($by_code) {
                $id = intval(str_replace('arch_', '', $prop_fields['CODE']));
            } else {
                $id = $prop_fields["ID"];
            }
            
            $prop_fields = self::clear_array($prop_fields);
            
            $properties_array[$id] = $prop_fields;
        }
        
        return $properties_array;
    }

    function clear_array($prop_fields = [])
    {
        unset($prop_fields["ID"]);
        foreach ($prop_fields as $k => $v) {
            if (! is_array($v))
                $prop_fields[$k] = trim($v);
            if ($k{0} == '~')
                unset($prop_fields[$k]);
        }
        
        // unset( $prop_fields['TIMESTAMP_X'] );
        // unset( $prop_fields['TIMESTAMP_X_UNIX'] );
        // unset( $prop_fields['DATE_CREATE'] );
        // unset( $prop_fields['SHOW_COUNTER'] );
        // unset( $prop_fields['SHOW_COUNTER_START'] );
        // unset( $prop_fields['SHOW_COUNTER_START_X'] );
        // unset( $prop_fields['CREATED_DATE'] );
        // unset( $prop_fields['CODE'] );
        // unset( $prop_fields['CREATED_BY'] );
        
        return $prop_fields;
    }

    function OnProlog_ArchiveEventHandler()
    {
        // if(!check_bitrix_sessid())
        // return;
        $errors = [];
        
        if ($GLOBALS["APPLICATION"]->GetCurPage(true) == "/bitrix/admin/iblock_list_admin.php" && isset($_REQUEST['IBLOCK_ID']) && $_REQUEST['action'] == 'archive') {
            
            $IBLOCK_ID = intval($_REQUEST['IBLOCK_ID']);
            
            if ($IBLOCK_ID <= 0) {
                return;
            }
            
            $res = \CIBlock::GetByID($IBLOCK_ID);
            if ($ar_res = $res->GetNext()) {
                
                $arch_type = 'archivarius_type';
                
                $ib = new \CIBlock();
                
                $arFields = \CIBlock::GetArrayByID($IBLOCK_ID);
                $arFields["GROUP_ID"] = \CIBlock::GetGroupPermissions($IBLOCK_ID);
                $arFields["CODE"] = $arFields["ID"] . "_arch";
                
                $arFields['NAME'] .= ' Архив';
                
                $result = \CIBlock::GetList([], [
                    'CODE' => $arFields["CODE"],
                    'TYPE' => $arch_type
                ])->GetNext();
                
                if (! $result) {
                    
                    $arFields['IBLOCK_TYPE_ID'] = $arch_type;
                    
                    // /////
                    
                    $arFields['ACTIVE'] = 'N';
                    $arFields['INDEX_ELEMENT'] = 'N';
                    $arFields['INDEX_SECTION'] = 'N';
                    
                    // /////
                    
                    $ARCH_IBLOCK_ID = $ib->Add($arFields);
                } else {
                    $ARCH_IBLOCK_ID = $result['ID'];
                }
            } else {
                return FALSE;
            }
            
            $properties_from = self::getPropertiesArray($IBLOCK_ID);
            
            $properties_to = self::getPropertiesArray($ARCH_IBLOCK_ID, TRUE);
            
            // топорная проверка чтобы сравнить типы в том числе
            
            $properties_add = [];
            
            foreach ($properties_from as $key => $prop_fields) {
                unset($prop_fields['CODE']);
                if (! $properties_to[$key]) {
                    unset($properties_to[$key]['CODE']);
                    $prop_fields['CODE'] = "arch_$key";
                    $prop_fields["IBLOCK_ID"] = $ARCH_IBLOCK_ID;
                    $properties_add[] = $prop_fields;
                }
            }
            
            if (sizeof($properties_add)) {
                $ibp = new \CIBlockProperty();
                foreach ($properties_add as $add_fields) {
                    $PropID = $ibp->Add($add_fields);
                    if (intval($PropID) <= 0) {
                        $errors[] = "Ошибка при копирование свойства $key";
                    }
                }
            }
            
            $arFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("IBLOCK_{$IBLOCK_ID}_SECTION");
            
            $arFields_arch = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("IBLOCK_{$ARCH_IBLOCK_ID}_SECTION");
            
            if (is_array($arFields)) {
                
                $oUserTypeEntity = new \CUserTypeEntity();
                
                foreach ($arFields as $key => $aUserFields) {
                    
                    if (! $arFields_arch[$key]) {
                        
                        $aUserFields['ENTITY_ID'] = "IBLOCK_{$ARCH_IBLOCK_ID}_SECTION";
                        
                        unset($aUserFields['ID']);
                        
                        $iUserFieldId = $oUserTypeEntity->Add($aUserFields);
                        
                        if (intval($iUserFieldId) <= 0) {
                            $errors[] = "Ошибка при копирование свойства раздела " . $aUserFields['NAME'];
                        }
                    }
                }
            }
            
            if (isset($_REQUEST['SECTION_ID'])) {
                
                $par_sect_id = intval($_REQUEST['SECTION_ID']);
                
                if ($par_sect_id > 0) {
                    $rsParentSection = \CIBlockSection::GetList([], [
                        "IBLOCK_ID" => $IBLOCK_ID,
                        "ID" => $par_sect_id
                    ], false, [
                        "ID",
                        "IBLOCK_SECTION_ID",
                        "IBLOCK_ID",
                        "NAME",
                        "DEPTH_LEVEL",
                        "LEFT_MARGIN",
                        "RIGHT_MARGIN"
                    ]);
                }
            }
            
            if (! $rsParentSection) {
                $rsParentSection = \CIBlockSection::GetList([
                    'name' => 'asc'
                ], [
                    "DEPTH_LEVEL" => 1,
                    "IBLOCK_ID" => $IBLOCK_ID
                ], false, [
                    "*",
                    "UF_*"
                ]);
            }
            
            $ibsc = new \CIBlockSection();
            
            $sect_id_arch = [];
            
            $parent_sect_ids = [];
            
            while ($arParentSection = $rsParentSection->GetNext()) {
                $arFilter = [
                    'IBLOCK_ID' => $arParentSection['IBLOCK_ID'],
                    '>LEFT_MARGIN' => $arParentSection['LEFT_MARGIN'],
                    '<RIGHT_MARGIN' => $arParentSection['RIGHT_MARGIN'],
                    '>DEPTH_LEVEL' => $arParentSection['DEPTH_LEVEL']
                ];
                
                $rsSect = \CIBlockSection::GetList([
                    'left_margin' => 'asc'
                ], $arFilter, true, [
                    "*",
                    "UF_*"
                ]);
                
                $arParentSection['IBLOCK_ID'] = $ARCH_IBLOCK_ID;
                $arParentSection['NAME'] .= ' ' . date('Y-m-d H:i:s');
                
                $sect_id = $arParentSection['ID'];
                
                $parent_sect_ids[] = $sect_id;
                
                $arParentSection = self::clear_array($arParentSection);
                
                $new_sect_id = $ibsc->Add($arParentSection);
                
                if ($new_sect_id > 0) {
                    $sect_id_arch[$sect_id] = $new_sect_id;
                }
                
                while ($sect = $rsSect->Fetch()) {
                    $sect['IBLOCK_ID'] = $ARCH_IBLOCK_ID;
                    $sect['NAME'] .= ' ' . date('Y-m-d H:i:s');
                    
                    $sect_id = $sect['ID'];
                    
                    $sect = self::clear_array($sect);
                    
                    $new_sect_id = $ibsc->Add($sect);
                    
                    if ($new_sect_id > 0) {
                        $sect_id_arch[$sect_id] = $new_sect_id;
                    }
                }
            }
            
            $ib = new \CIBlockElement();
            
            $ns = " архивная копия от " . date('Y-m-d');
            
            if (! ($_REQUEST['SECTION_ID'])) {
                
                $arFilter = [
                    'IBLOCK_ID' => $IBLOCK_ID,
                    'IBLOCK_SECTION_ID' => NULL
                ];
                
                $result = \Bitrix\Iblock\ElementTable::getList(array(
                    'filter' => $arFilter,
                    'select' => array(
                        "*"
                    )
                ));
                
                while ($el = $result->fetch()) {
                    
                    $props = $ib->GetPropertyValues($IBLOCK_ID, [
                        "ID" => $el['ID']
                    ])->Fetch();
                    $props_to_enter = [];
                    foreach ($props as $key => $value) {
                        if (is_numeric($key)) {
                            $props_to_enter["arch_$key"] = $value;
                        }
                    }
                    $el['IBLOCK_ID'] = $ARCH_IBLOCK_ID;
                    if ($el['PREVIEW_PICTURE']) {
                        $el['PREVIEW_PICTURE'] = \CFile::GetFileArray($el['PREVIEW_PICTURE']);
                        
                        $el['PREVIEW_PICTURE'] = \CFile::MakeFileArray($el['PREVIEW_PICTURE']['SRC']);
                    }
                    if ($el['DETAIL_PICTURE']) {
                        $el['DETAIL_PICTURE'] = \CFile::GetFileArray($el['DETAIL_PICTURE']);
                        
                        $el['DETAIL_PICTURE'] = \CFile::MakeFileArray($el['DETAIL_PICTURE']['SRC']);
                    }
                    
                    $el['NAME'] .= $ns;
                    
                    $new_el_id = $ib->Add($el);
                    
                    if (intval($new_el_id) > 0) {
                        $ib->SetPropertyValuesEx($new_el_id, $ARCH_IBLOCK_ID, $props_to_enter, [
                            'NewElement'
                        ]);
                        \Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex($ARCH_IBLOCK_ID, $new_el_id);
                    } else {
                        $errors[] = "Ошибка при копирование элемента " . $el['NAME'];
                    }
                }
            }
            
            $rsParentSection = \CIBlockSection::GetList([], [
                "IBLOCK_ID" => $IBLOCK_ID,
                "ID" => $parent_sect_ids
            ], false, [
                "ID"
            ]);
            
            while ($arParentSection = $rsParentSection->GetNext()) {
                
                $arFilter = [
                    'IBLOCK_ID' => $IBLOCK_ID,
                    'IBLOCK_SECTION_ID' => $arParentSection['ID']
                ];
                
                $result = \Bitrix\Iblock\ElementTable::getList(array(
                    'filter' => $arFilter,
                    'select' => array(
                        "*"
                    )
                ));
                
                while ($el = $result->fetch()) {
                    
                    $props = $ib->GetPropertyValues($IBLOCK_ID, [
                        "ID" => $el['ID']
                    ])->Fetch();
                    $props_to_enter = [];
                    foreach ($props as $key => $value) {
                        if (is_numeric($key)) {
                            $props_to_enter["arch_$key"] = $value;
                        }
                    }
                    $el['IBLOCK_ID'] = $ARCH_IBLOCK_ID;
                    if ($el['PREVIEW_PICTURE']) {
                        $el['PREVIEW_PICTURE'] = \CFile::GetFileArray($el['PREVIEW_PICTURE']);
                        
                        $el['PREVIEW_PICTURE'] = \CFile::MakeFileArray($el['PREVIEW_PICTURE']['SRC']);
                    }
                    if ($el['DETAIL_PICTURE']) {
                        $el['DETAIL_PICTURE'] = \CFile::GetFileArray($el['DETAIL_PICTURE']);
                        
                        $el['DETAIL_PICTURE'] = \CFile::MakeFileArray($el['DETAIL_PICTURE']['SRC']);
                    }
                    
                    $el['NAME'] .= $ns;
                    
                    $el['IBLOCK_SECTION_ID'] = $sect_id_arch[$el['IBLOCK_SECTION_ID']];
                    
                    $new_el_id = $ib->Add($el);
                    
                    if (intval($new_el_id) > 0) {
                        $ib->SetPropertyValuesEx($new_el_id, $ARCH_IBLOCK_ID, $props_to_enter, [
                            'NewElement'
                        ]);
                        
                        $db_old_groups = \CIBlockElement::GetElementGroups($el['ID'], FALSE, [
                            "ID"
                        ]);
                        
                        $old_sect_ids = [];
                        
                        $new_sect_ids = [];
                        
                        while ($ar_group = $db_old_groups->GetNext()) {
                            $old_sect_ids[] = $ar_group['ID'];
                        }
                        
                        foreach ($old_sect_ids as $k => $v) {
                            if ($sect_id_arch[$v] > 0) {
                                $new_sect_ids[] = $sect_id_arch[$v];
                            }
                        }
                        
                        $ib->SetElementSection($new_el_id, $new_sect_ids);
                        
                        \Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex($ARCH_IBLOCK_ID, $new_el_id);
                    } else {
                        $errors[] = "Ошибка при копирование элемента " . $el['NAME'];
                    }
                }
            }
            
            
            
            header("Location: /bitrix/admin/iblock_admin.php?type=archivarius_type&lang=ru&admin=N", TRUE, 301);
            
//             $e = new \CAdminException(['text' => 'Проверка']);
//             $GLOBALS["APPLICATION"]->ThrowException($e);
//             $message = new \CAdminMessage(GetMessage("Ошибка повещения в архив"), $e);
            
        }
    }
}

?>
