<?
use Bitrix\Main, Bitrix\Iblock;

class test_archive extends CModule
{

    var $MODULE_ID = "test_archive";

    var $MODULE_VERSION;

    var $MODULE_VERSION_DATE;

    var $MODULE_NAME;

    var $MODULE_DESCRIPTION;

    var $MODULE_CSS;

    function test_archive()
    {
        $arModuleVersion = array();
        
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include ($path . "/version.php");
        
        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
        
        $this->MODULE_NAME = "test_archive – модуль с архивацией";
        $this->MODULE_DESCRIPTION = "После установки вы сможете проверить тестовое задание";
    }

    function InstallFiles()
    {
        $src = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/admin/";
        $dst = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin/";
        
        CopyDirFiles($src, $dst,true,true);
        
        return true;
    }

    function UnInstallFiles()
    {
        unlink($_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin/".$this->MODULE_ID.".php" );
        return true;
    }

    function install_structure()
    {
        $errors = [];
        global $DB;
        
        if (\CModule::IncludeModule("iblock")) {
            $arFields = [
                'ID' => 'archivarius_type',
                'SECTIONS' => 'Y',
                'IN_RSS' => 'N',
                'SORT' => 10000,
                'LANG' => Array(
                    'ru' => Array(
                        'NAME' => 'Архив инфоблоков',
                        'SECTION_NAME' => 'Разделы',
                        'ELEMENT_NAME' => 'Элементы'
                    )
                )
            ];
            
            if (! \CIBlockType::GetByID($arFields['ID'])->Fetch()) {
                
                $obBlocktype = new CIBlockType();
                $DB->StartTransaction();
                $res = $obBlocktype->Add($arFields);
                if (! $res) {
                    $DB->Rollback();
                    $errors[] = 'Не смог добавить тип инфоблока: ' . $obBlocktype->LAST_ERROR;
                } else {
                    $DB->Commit();
                }
            } else {
                // $errors[] = 'Такой тип инфоблоков уже есть. Не могу создать архивный блок';
            }
        } else {
            $errors[] = 'no way man. there is no module';
        }
        
        if (sizeof($errors) == 0) {
            $return['success'] = 'Y';
        } else {
            $return['success'] = 'N';
        }
        
        $return['errors'] = $errors;
        
        return $return;
    }

    function install_dummy_data()
    {
        global $DB;
        
        $errors = [];
        
        $return = [
            'SUCCESS' => 'N'
        ];
        
        $rsSites = \Bitrix\Main\SiteTable::getList([
            'order' => [
                "SORT" => "ASC"
            ],
            'select' => [
                'LID'
            ],
            'filter' => [
                'ACTIVE' => 'Y'
            ],
            'limit' => 1
        ]);
        
        if ($arSite = $rsSites->fetch()) {
            $site_id = $arSite['LID'];
        } else {
            $errors[] = 'Проблема с сайтами у вас';
        }
        
        $arFields = [
            'ID' => 'dummy_cars',
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 9000,
            'LANG' => Array(
                'ru' => Array(
                    'NAME' => 'Машины',
                    'SECTION_NAME' => 'Разделы',
                    'ELEMENT_NAME' => 'Элементы'
                )
            ),
            'SITE_ID' => $site_id
        ];
        
        if (! \CIBlockType::GetByID($arFields['ID'])->Fetch()) {
            $obBlocktype = new \CIBlockType();
            $DB->StartTransaction();
            $res = $obBlocktype->Add($arFields);
            if (! $res) {
                $DB->Rollback();
                $errors[] = 'Не смог добавить тип инфоблока: ' . $obBlocktype->LAST_ERROR;
            } else {
                $DB->Commit();
            }
        }
        
        if (sizeof($errors)) {
            $return['ERRORS'] = $errors;
            return $return;
        }
        
        $type = $arFields['ID'];
        
        $arFields = [
            "ACTIVE" => 'N',
            "NAME" => 'Машины',
            "CODE" => 'dummy_cars',
            "IBLOCK_TYPE_ID" => $type,
            "SITE_ID" => $site_id,
            "SORT" => '500'
        ];
        
        $result = \Bitrix\Iblock\IblockTable::getList(array(
            'filter' => array(
                'CODE' => $arFields['CODE']
            ),
            'select' => array(
                "ID"
            )
        ))->fetch();
        
        if ($result) {
            
            $ID = $result['ID'];
        } else {
            
            $ib = new CIBlock();
            $ID = $ib->Add($arFields);
            
            if ($ID <= 0) {
                $errors[] = 'Не смог добавить инфоблок';
            }
        }
        
        if (sizeof($errors)) {
            $return['ERRORS'] = $errors;
            return $return;
        }
        
        if ($ID > 0) {
            
            $propObj = new CIBlockProperty();
            
            $proFields = [
                [
                    "IBLOCK_ID" => $ID,
                    "NAME" => 'Марка',
                    "CODE" => 'mark',
                    "TYPE" => "S"
                ],
                [
                    "IBLOCK_ID" => $ID,
                    "NAME" => 'Модель',
                    "CODE" => 'model',
                    "TYPE" => "S"
                ],
                [
                    "IBLOCK_ID" => $ID,
                    "NAME" => 'Фото',
                    "CODE" => 'photo',
                    "PROPERTY_TYPE" => "F",
                    'FILE_TYPE' => 'jpg, gif, bmp, png, jpeg'
                ]
            ];
            
            foreach ($proFields as $arFields) {
                
                $property = $propObj->GetList(Array(), Array(
                    "IBLOCK_ID" => $ID,
                    "CODE" => $arFields['CODE']
                ))->GetNext();
                
                if (! $property) {
                    $property_id = $propObj->Add($arFields);
                    if ($property_id <= 0) {
                        $errors[] = "Проблема при создании свойства инфоблока {$arFields['NAME']}";
                    }
                }
            }
            
            $dummy_cars = [
                [
                    'IBLOCK_ID' => $ID,
                    'NAME' => 'Skoda',
                    'PROPERTY_VALUES' => [
                        'mark' => 'Some Skoda Car',
                        'model' => 'Some Skoda model',
                        'photo' => CFile::MakeFileArray(__DIR__ . "/dummy_data/01.jpg")
                    ]
                ],
                
                [
                    'IBLOCK_ID' => $ID,
                    'NAME' => 'VAZ',
                    'PROPERTY_VALUES' => [
                        'mark' => 'Some VAZ Car',
                        'model' => 'Some VAZ model',
                        'photo' => CFile::MakeFileArray(__DIR__ . "/dummy_data/02.jpg")
                    ]
                ],
                
                [
                    'IBLOCK_ID' => $ID,
                    'NAME' => 'Mercedes',
                    'PROPERTY_VALUES' => [
                        'mark' => 'Some Mercedez Car',
                        'model' => 'Some Mercedez model',
                        'photo' => CFile::MakeFileArray(__DIR__ . "/dummy_data/03.jpg")
                    ]
                ]
            
            ];
            
            $iblock_element = new \CIBlockElement();
            
            foreach ($dummy_cars as $arFields) {
                
                $result = \Bitrix\Iblock\ElementTable::getList(array(
                    'filter' => array(
                        'IBLOCK_ID' => $ID,
                        "NAME" => $arFields['NAME']
                    ),
                    'select' => array(
                        "ID",
                        "IBLOCK_ID",
                        "ACTIVE"
                    )
                ))->fetch();
                
                if (! $result) {
                    $iblock_element->Add($arFields);
                }
            }
        } else {
            $errors[] = 'Проблема при создании инфоблока демо-данных';
        }
        
        if (sizeof($errors) == 0) {
            $return['success'] = 'Y';
        } else {
            $return['success'] = 'N';
        }
        
        $return['errors'] = $errors;
        
        return $return;
    }

    function DoInstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION, $step;
        
        $FM_RIGHT = $APPLICATION->GetGroupRight($this->MODULE_ID);
        if ($FM_RIGHT != "D") {
            
            $this->InstallFiles();
            
            $step = IntVal($step);
            
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/". $this->MODULE_ID ."/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
			
            if ($step < 2) {
                
                $APPLICATION->IncludeAdminFile("Установка модуля {$this->MODULE_ID}", $DOCUMENT_ROOT . "/bitrix/modules/{$this->MODULE_ID}/install/step1.php");
            } elseif ($step == 2) {
                
                $type_install = $this->install_structure();
                
                if ($type_install['success'] == 'Y') {
                    
                    RegisterModuleDependences('main', 'OnAdminContextMenuShow', $this->MODULE_ID, '\test_archive\events', 'OnAdminContextMenuShow_addArchiveButton');
                    RegisterModuleDependences('main', 'OnProlog', $this->MODULE_ID, '\test_archive\events', 'OnProlog_ArchiveEventHandler');
                    
                    RegisterModule($this->MODULE_ID);
                    
                    if ($_REQUEST['install_demo_data'] == 'Y') {
                        $dummy_data_install = $this->install_dummy_data();
                    }
                }
                
                $errors = array_merge($type_install['ERRORS'], $dummy_data_install['ERRORS']);
                
                $APPLICATION->IncludeAdminFile("Установка модуля {$this->MODULE_ID}", $DOCUMENT_ROOT . "/bitrix/modules/{$this->MODULE_ID}/install/step2.php");
            }
        }
    }

    function DoUninstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION, $DB;
        
        UnRegisterModuleDependences('main', 'OnAdminContextMenuShow', $this->MODULE_ID, '\test_archive\events', 'OnAdminContextMenuShow_addArchiveButton');
        UnRegisterModuleDependences('main', 'OnProlog', $this->MODULE_ID, '\test_archive\events', 'OnProlog_ArchiveEventHandler');
        
        $code = 'dummy_cars';
        
        $ib = new \CIBlockElement();
        
		DeleteDirFilesEx("/bitrix/components/test/test_time");
		
        if (\CIBlockType::GetByID($code)->Fetch()) {
            
            $result = \Bitrix\Iblock\IblockTable::getList(array(
                'filter' => array(
                    'CODE' => $code
                ),
                'select' => array(
                    "ID"
                )
            ))->fetch();
            
            if ($result) {
                $list = \CIBlockElement::GetList([], [
                    'IBLOCK_ID' => $result['ID']
                ]);
                
                while ($el = $list->GetNext()) {
                    $ib->Delete($el['ID']);
                }
                
                $DB->StartTransaction();
                if (! \CIBlock::Delete($result['ID'])) {
                    $DB->Rollback();
                } else
                    $DB->Commit();
            }
            
            $DB->StartTransaction();
            if (! \CIBlockType::Delete($code)) {
                $DB->Rollback();
            }
            $DB->Commit();
        }
        
        $result = \Bitrix\Iblock\IblockTable::getList(array(
            'filter' => array(
                'CODE' => 'archivarius_type'
            ),
            'select' => array(
                "ID"
            )
        ));
        
        while ($ib_id = $result->fetch()) {
            $list = \CIBlockElement::GetList([], [
                'IBLOCK_ID' => $ib_id['ID']
            ]);
            while ($el = $list->GetNext()) {
                $ib->Delete($el['ID']);
            }
            
            $DB->StartTransaction();
            if (! \CIBlock::Delete($ib_id['ID'])) {
                $DB->Rollback();
            } else
                $DB->Commit();
        }
        
        $DB->StartTransaction();
        if (! \CIBlockType::Delete('archivarius_type')) {
            $DB->Rollback();
        }
        $DB->Commit();
        
        $this->UnInstallFiles();
        
        UnRegisterModule($this->MODULE_ID);
        
        $APPLICATION->IncludeAdminFile("Деинсталляция модуля test_archive", $DOCUMENT_ROOT . "/bitrix/modules/{$this->MODULE_ID}/install/unstep.php");
    }
}
?>