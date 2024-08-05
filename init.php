<?
namespace Bitrix\Crm\Service;
use \Bitrix\Main\Result;
use \Bitrix\Main\DI;
use \Bitrix\Main\Application;
use \Bitrix\Crm\Item;
use \Bitrix\Crm\Service;
use \Bitrix\Crm\Service\Operation;
use \Bitrix\Im;
use \Bitrix\Iblock;

if (\Bitrix\Main\Loader::includeModule('crm'))
{
    $container = new class extends Service\Container
    {
        public function getFactory(int $entityTypeId): ?Service\Factory
        {
            if ($entityTypeId === 159)
            {
                if(file_exists(__DIR__ .'/run'))
                {
                    $start = file_get_contents(__DIR__ .'/run');
                    if(date('Ymd', $start) < date('Ymd'))
                    {
                        file_put_contents(__DIR__ .'/run', time());
                        $connection = Application::getConnection();
                        $connection->query("UPDATE `b_crm_dynamic_items_159` SET `STAGE_ID` = 'DT159_1:NEW' WHERE `STAGE_ID` != 'DT159_1:SUCCESS' AND `STAGE_ID` != 'DT159_1:FAIL' AND `CLOSEDATE` < '".date('Y-m-d')."'");
                        $connection->query("UPDATE `b_crm_dynamic_items_159` SET `STAGE_ID` = 'DT159_1:PREPARATION' WHERE `STAGE_ID` != 'DT159_1:SUCCESS' AND `STAGE_ID` != 'DT159_1:FAIL' AND `CLOSEDATE` >= '".date('Y-m-d')."' AND `CLOSEDATE` <= '".date('Y-m-d', strtotime('+7 day'))."'");
                        $connection->query("UPDATE `b_crm_dynamic_items_159` SET `STAGE_ID` = 'DT159_1:CLIENT' WHERE `STAGE_ID` != 'DT159_1:SUCCESS' AND `STAGE_ID` != 'DT159_1:FAIL' AND `CLOSEDATE` > '".date('Y-m-d', strtotime('+7 day'))."' AND `CLOSEDATE` <= '".date('Y-m-d', strtotime('+14 day'))."'");
                        $connection->query("UPDATE `b_crm_dynamic_items_159` SET `STAGE_ID` = 'DT159_1:UC_OZ2CEE' WHERE `STAGE_ID` != 'DT159_1:SUCCESS' AND `STAGE_ID` != 'DT159_1:FAIL' AND `CLOSEDATE` > '".date('Y-m-d', strtotime('+14 day'))."'");
                    }
                }

                    $type = $this->getTypeByEntityTypeId($entityTypeId);


                    public function getAddOperation(Item $item, Context $context = null): Operation\Add
                    {
                        $operation = parent::getAddOperation($item, $context);
                        return $operation ->addAction(
                            Operation::ACTION_AFTER_SAVE,
                            new class extends Operation\Action {
                                public function process(Item $item): Result
                                {
                                    $connection = Application::getConnection();
                                    if(!empty($_POST['data']['PARENT_ID_2']) && $_POST['data']['PARENT_ID_2'] > 0)
                                    {
                                        $recordset = $connection->query("SELECT `d`.`TITLE`, `d`.`COMPANY_ID`, `sd`.`UF_MYCOMPANY_ID` 
                                                                         FROM `b_crm_deal` AS `d`
                                                                         LEFT JOIN `b_uts_crm_deal` AS `sd` ON `sd`.`VALUE_ID` = `d`.`ID`
                                                                         WHERE `d`.`ID` = ".(int)$_POST['data']['PARENT_ID_2']);
                                        $record = $recordset->fetch();
                                        $item->setMycompanyId($record['UF_MYCOMPANY_ID']);
                                        $item->setCompanyId($record['COMPANY_ID']);
                                        $connection->query("UPDATE `b_crm_dynamic_items_159` SET `TITLE` = 'Счёт (".$record['TITLE'].")' WHERE `ID` = ".(int)$item->getId());
                                    }
                                    $recordset = $connection->query("SELECT `CREATED_BY`, `ASSIGNED_BY_ID` FROM `b_crm_dynamic_items_159` WHERE `ID` = ".(int)$item->getId());
                                    $chatUsers = $recordset->fetch();

                                    if(\Bitrix\Main\Loader::includeModule('im'))
                                    {
                                        $chat = new \CIMChat;
                                        $chatId = $chat->Add(array(
                                            'TITLE' => 'Счёт ('.$record['TITLE'].')',
                                            'USERS' => array($chatUsers['CREATED_BY'], $chatUsers['ASSIGNED_BY_ID']),
                                            'TYPE' => IM_MESSAGE_CHAT,
                                            'ENTITY_TYPE' => 'CRM',
                                            'AUTHOR_ID' => $chatUsers['CREATED_BY'],
                                            'ENTITY_ID' => 'DYNAMIC_159|'.(int)$item->getId()
                                        ));
                                        $chat->AddMessage(array(
                                            "TO_CHAT_ID" => $chatId,
                                            "FROM_USER_ID" => 0,
                                            "SYSTEM" => 'Y',
                                            "MESSAGE"  => '[b]Создан чат для обсуждения элемента сущности "Счета"[/b][BR][URL=/crm/type/159/details/'.(int)$item->getId().'/]Счёт ('.$record['TITLE'].')[/URL]',
                                        ));
                                    }

                                    return new Result();
                                }
                            }
                        )
                            ->addAction(
                            Operation::ACTION_AFTER_SAVE,
                            new class extends Operation\Action {
                                public function process(Item $item): Result
                                {
                                    if($item->getUfAutopart() != 0)
                                    {
                                        $arTypeCrm[1] = 'L_';
                                        $arTypeCrm[2] = 'D_';
                                        $arTypeCrm[133] = 'T85_';
                                        $connection = Application::getConnection();
                                        $sql = $connection->query("SELECT `d`.`ID`, `d`.`TITLE`, `d`.`OPPORTUNITY`, `d`.`MYCOMPANY_ID`, `r`.`SRC_ENTITY_ID` AS `deal`, `c`.`RQ_INN`, `r`.`SRC_ENTITY_TYPE_ID` AS `type`
                                                                               FROM `b_crm_dynamic_items_159` AS `d`
                                                                               LEFT JOIN `b_crm_requisite` AS `c` ON `c`.`ENTITY_ID` = `d`.`COMPANY_ID`
                                                                               LEFT JOIN `b_crm_entity_relation` AS `r` ON `r`.`DST_ENTITY_ID` = `d`.`ID` AND `r`.`DST_ENTITY_TYPE_ID` = 159
                                                                               WHERE `d`.`UF_SMART_PAY_TYPE` = '29' AND `d`.`OPPORTUNITY` > 0 AND `d`.`ID` = ".(int)$item->getId());
                                        $iResult = $sql->fetch();
                                        if(!empty($iResult) && !empty($iResult['RQ_INN']))
                                        {
                                            $sql = $connection->query("SELECT `e`.`ID`, `e`.`NAME`, `p1`.`VALUE` AS `p150`, `p2`.`VALUE` AS `p152`, `p3`.`VALUE` AS `p156`, 
                                                                                          `p4`.`VALUE` AS `p157`,`p5`.`VALUE` AS `p161`,`p6`.`VALUE_NUM` AS `p147`, `p7`.`VALUE` AS `p146`,    
                                                                                          `p8`.`VALUE` AS `p148`,`p9`.`VALUE` AS `p149`,`p10`.`VALUE` AS `p151`, `p11`.`VALUE` AS `p153`,    
                                                                                          `p12`.`VALUE` AS `p154`,`p13`.`VALUE` AS `p155`,`p14`.`VALUE` AS `p158`, `p15`.`VALUE` AS `p159`,    
                                                                                          `p16`.`VALUE` AS `p160`,`p17`.`VALUE` AS `p162`,`p18`.`VALUE` AS `p163`, `p19`.`VALUE` AS `p164`,    
                                                                                          `p20`.`VALUE` AS `p165`, `p6`.`VALUE_NUM` AS `sum`
                                                                                   FROM `b_iblock_element` AS `e`
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p1` ON `p1`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p1`.`IBLOCK_PROPERTY_ID` = 150
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p2` ON `p2`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p2`.`IBLOCK_PROPERTY_ID` = 152
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p3` ON `p3`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p3`.`IBLOCK_PROPERTY_ID` = 156
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p4` ON `p4`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p4`.`IBLOCK_PROPERTY_ID` = 157
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p5` ON `p5`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p5`.`IBLOCK_PROPERTY_ID` = 161
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p6` ON `p6`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p6`.`IBLOCK_PROPERTY_ID` = 147
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p7` ON `p7`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p7`.`IBLOCK_PROPERTY_ID` = 146
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p8` ON `p8`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p8`.`IBLOCK_PROPERTY_ID` = 148
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p9` ON `p9`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p9`.`IBLOCK_PROPERTY_ID` = 149
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p10` ON `p10`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p10`.`IBLOCK_PROPERTY_ID` = 151
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p11` ON `p11`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p11`.`IBLOCK_PROPERTY_ID` = 153
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p12` ON `p12`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p12`.`IBLOCK_PROPERTY_ID` = 154
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p13` ON `p13`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p13`.`IBLOCK_PROPERTY_ID` = 155
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p14` ON `p14`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p14`.`IBLOCK_PROPERTY_ID` = 158
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p15` ON `p15`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p15`.`IBLOCK_PROPERTY_ID` = 159
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p16` ON `p16`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p16`.`IBLOCK_PROPERTY_ID` = 160
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p17` ON `p17`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p17`.`IBLOCK_PROPERTY_ID` = 162
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p18` ON `p18`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p18`.`IBLOCK_PROPERTY_ID` = 163
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p19` ON `p19`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p19`.`IBLOCK_PROPERTY_ID` = 164
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p20` ON `p20`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p20`.`IBLOCK_PROPERTY_ID` = 165                                                                               
                                                                                   WHERE `e`.`IBLOCK_ID` = 28
                                                                                    AND `p1`.`VALUE` = '".$iResult['RQ_INN']."'
                                                                                    AND `p2`.`VALUE` = '".$iResult['MYCOMPANY_ID']."'
                                                                                    AND `p6`.`VALUE_NUM` > 0
                                                                                    AND (`p3`.`VALUE` IS NULL OR `p3`.`VALUE` = '' OR `p3`.`VALUE` = 0)
                                                                                    AND (`p4`.`VALUE` IS NULL OR `p4`.`VALUE` = '' OR `p4`.`VALUE` = 0)
                                                                                    AND (`p5`.`VALUE` IS NULL OR `p5`.`VALUE` = '' OR `p5`.`VALUE` = 0)
                                                                                   GROUP BY `e`.`ID`");
                                            $iPay = $sql->fetchAll();
                                            if(!empty($iPay))
                                            {
                                                if(count($iPay) > 1)
                                                {
                                                    $ppInfo = '';
                                                    $i = 0;
                                                    $idIsPay = 0;
                                                    foreach ($iPay as $id => $val)
                                                    {
                                                        $ppInfo .= 'Платёж #'.$val['ID'].' от '.date('d.m.Y', strtotime($val['p146'])).' (Платёжное поручение №'.$val['p149'].')[BR]';
                                                        
                                                        if($val['sum'] == $iResult['OPPORTUNITY'])
                                                        {
                                                            $i++;
                                                            $idIsPay = $id;
                                                        }
                                                    }

                                                    if($i == 1)
                                                    {
                                                        $arRow = $iPay[$idIsPay];
                                                        if(\Bitrix\Main\Loader::includeModule('iblock'))
                                                        {
                                                            $el = new \CIBlockElement;
                                                            $idPay = $el->Update($arRow['ID'],
                                                                array(
                                                                    'NAME' => $arRow['NAME'],
                                                                    'ACTIVE_FROM' => date('d.m.Y H:i:s'),
                                                                    'MODIFIED_BY' => (int)$item->getUpdatedBy(),
                                                                    'ACTIVE' => 'Y',
                                                                    'IBLOCK_ID' => 28,
                                                                    'PROPERTY_VALUES' => array(
                                                                        '146' => date('d.m.Y', strtotime($arRow['p146'])),                       #   DATE
                                                                        '147' => $arRow['p147'],                                #   SUM
                                                                        '148' => $arRow['p148'],                                #   SUM  OSN
                                                                        '149' => $arRow['p149'],                       #   PP
                                                                        '150' => $arRow['p150'],                       #   INN
                                                                        '151' => $arRow['p151'],                       #   CONTR_NAME
                                                                        '152' => $arRow['p152'],                       #   MY COMPANY
                                                                        '153' => $arRow['p153'],                       #   R/S
                                                                        '154' => $arRow['p154'],                       #   NAZNACH
                                                                        '155' => $arTypeCrm[$iResult['type']].$iResult['deal'],                     #   DEAL/LEAD
                                                                        '156' => (int)$iResult['ID'],                       #   ID INVOICE
                                                                        '157' => 0,                                     #   TASK ID
                                                                        '158' => '<a href="/crm/type/159/details/'.(int)$iResult['ID'].'/">'.$iResult['TITLE'].'</a>',
                                                                        '159' => '',                                    #   TASK LINK
                                                                        '160' => 0,
                                                                        '161' => 0,                                     #   ZP
                                                                        '162' => date('d.m.Y'),                         #   DATE EDIT
                                                                        '163' => (int)$item->getUpdatedBy(),                       #   OPER
                                                                        '164' => $arRow['p164'],                       #   OTVETSTV
                                                                        '165' => $arRow['p165']                        #   COMMENT
                                                                    )
                                                                )
                                                            );
                                                            if($idPay > 0)
                                                            {
                                                                #$item->set('CLOSEDATE', $arRow['p146']);
                                                                #$item->set('UF_ID_PAY', $arRow['ID']);
                                                                #$item->set('UF_PAY_NOTE', $arRow['p154']);
                                                                #$item->set('UF_PAY_DATE', $arRow['p146']);
                                                                #$item->set('STAGE_ID', 'DT159_1:SUCCESS');
                                                                $connection->query("UPDATE `b_crm_dynamic_items_159` SET `STAGE_ID` = 'DT159_1:SUCCESS', `CLOSEDATE` = '".$arRow['p146']."', `UF_PAY_DATE` = '".$arRow['p146']."', `UF_ID_PAY` = ".$arRow['ID'].", `UF_PAY_NOTE` = '".$arRow['p154']."' WHERE `ID` = ".(int)$iResult['ID']);
                                                                if(\Bitrix\Main\Loader::includeModule('im'))
                                                                {
                                                                    $sql = $connection->query("SELECT `ID` FROM `b_im_chat` WHERE `ENTITY_ID` = 'DYNAMIC_159|".(int)$iResult['ID']."'");
                                                                    $iChat = $sql->fetch();
                                                                    $chat = new \CIMChat;
                                                                    $chat->AddMessage(array(
                                                                        "TO_CHAT_ID" => $iChat['ID'],
                                                                        "FROM_USER_ID" => 0,
                                                                        "SYSTEM" => 'Y',
                                                                        "MESSAGE"  => '[b]Оплата счёта произведена[/b]'
                                                                    ));
                                                                }
                                                            }
                                                        }
                                                    }
                                                    elseif($i > 1 && \Bitrix\Main\Loader::includeModule('im'))
                                                    {
                                                        $sql = $connection->query("SELECT `ID` FROM `b_im_chat` WHERE `ENTITY_ID` = 'DYNAMIC_159|".(int)$item->getId()."'");
                                                        $iChat = $sql->fetch();
                                                        $chat = new \CIMChat;
                                                        $chat->AddMessage(array(
                                                            "TO_CHAT_ID" => $iChat['ID'],
                                                            "FROM_USER_ID" => 0,
                                                            "SYSTEM" => 'Y',
                                                            "MESSAGE"  => 'Имеются подходящие платежи для закрытия счёта[BR]'.$ppInfo
                                                        ));
                                                    }
                                                }
                                                elseif(count($iPay) == 1)
                                                {
                                                    $arRow = current($iPay);
                                                    if($arRow['sum'] == $iResult['OPPORTUNITY'])
                                                    {
                                                        if(\Bitrix\Main\Loader::includeModule('iblock'))
                                                        {
                                                            $el = new \CIBlockElement;
                                                            $idPay = $el->Update($arRow['ID'],
                                                                array(
                                                                    'NAME' => $arRow['NAME'],
                                                                    'ACTIVE_FROM' => date('d.m.Y H:i:s'),
                                                                    'MODIFIED_BY' => (int)$item->getUpdatedBy(),
                                                                    'ACTIVE' => 'Y',
                                                                    'IBLOCK_ID' => 28,
                                                                    'PROPERTY_VALUES' => array(
                                                                        '146' => date('d.m.Y', strtotime($arRow['p146'])),                       #   DATE
                                                                        '147' => $arRow['p147'],                                #   SUM
                                                                        '148' => $arRow['p148'],                                #   SUM  OSN
                                                                        '149' => $arRow['p149'],                       #   PP
                                                                        '150' => $arRow['p150'],                       #   INN
                                                                        '151' => $arRow['p151'],                       #   CONTR_NAME
                                                                        '152' => $arRow['p152'],                       #   MY COMPANY
                                                                        '153' => $arRow['p153'],                       #   R/S
                                                                        '154' => $arRow['p154'],                       #   NAZNACH
                                                                        '155' => $arTypeCrm[$iResult['type']].$iResult['deal'],                     #   DEAL/LEAD
                                                                        '156' => (int)$iResult['ID'],                       #   ID INVOICE
                                                                        '157' => 0,                                     #   TASK ID
                                                                        '158' => '<a href="/crm/type/159/details/'.(int)$iResult['ID'].'/">'.$iResult['TITLE'].'</a>',
                                                                        '159' => '',                                    #   TASK LINK
                                                                        '160' => 0,
                                                                        '161' => 0,                                     #   ZP
                                                                        '162' => date('d.m.Y'),                         #   DATE EDIT
                                                                        '163' => (int)$item->getUpdatedBy(),                       #   OPER
                                                                        '164' => $arRow['p164'],                       #   OTVETSTV
                                                                        '165' => $arRow['p165']                        #   COMMENT
                                                                    )
                                                                )
                                                            );
                                                            if($idPay > 0)
                                                            {
                                                                #$item->set('CLOSEDATE', $arRow['p146']);
                                                                #$item->set('UF_ID_PAY', $arRow['ID']);
                                                                #$item->set('UF_PAY_NOTE', $arRow['p154']);
                                                                #$item->set('UF_PAY_DATE', $arRow['p146']);
                                                                #$item->set('STAGE_ID', 'DT159_1:SUCCESS');
                                                                $connection->query("UPDATE `b_crm_dynamic_items_159` SET `STAGE_ID` = 'DT159_1:SUCCESS', `CLOSEDATE` = '".$arRow['p146']."', `UF_PAY_DATE` = '".$arRow['p146']."', `UF_ID_PAY` = ".$arRow['ID'].", `UF_PAY_NOTE` = '".$arRow['p154']."' WHERE `ID` = ".(int)$iResult['ID']);
                                                                if(\Bitrix\Main\Loader::includeModule('im'))
                                                                {
                                                                    $sql = $connection->query("SELECT `ID` FROM `b_im_chat` WHERE `ENTITY_ID` = 'DYNAMIC_159|".(int)$iResult['ID']."'");
                                                                    $iChat = $sql->fetch();
                                                                    $chat = new \CIMChat;
                                                                    $chat->AddMessage(array(
                                                                        "TO_CHAT_ID" => $iChat['ID'],
                                                                        "FROM_USER_ID" => 0,
                                                                        "SYSTEM" => 'Y',
                                                                        "MESSAGE"  => '[b]Оплата счёта произведена[/b]'
                                                                    ));
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    return new Result();
                                }
                            }
                        );
                    }

                    public function getUpdateOperation(Item $item, Context $context = null): Operation\Update
                    {
                        $connection = Application::getConnection();
                        $sql = $connection->query("SELECT * FROM `b_crm_dynamic_items_159` WHERE `ID` = ".(int)$item->getId());
                        $iResult = $sql->fetch();
                        if($iResult['STAGE_ID'] == 'DT159_1:SUCCESS' && $item->getStageId() != 'DT159_1:SUCCESS')
                        {
                            $sql = $connection->query("SELECT `VALUE` FROM `b_iblock_element_property` WHERE `IBLOCK_PROPERTY_ID` = 156 AND `VALUE` = ".(int)$item->getId());
                            $iPay = $sql->fetch();
                            if(!empty($iPay))
                            {
                                $item->setStageId('DT159_1:SUCCESS');
                                if(\Bitrix\Main\Loader::includeModule('im'))
                                {
                                    $sql = $connection->query("SELECT `ID` FROM `b_im_chat` WHERE `ENTITY_ID` = 'DYNAMIC_159|".(int)$item->getId()."'");
                                    $iChat = $sql->fetch();
                                    $chat = new \CIMChat;
                                    $chat->AddMessage(array(
                                        "TO_CHAT_ID" => $iChat['ID'],
                                        "FROM_USER_ID" => 0,
                                        "SYSTEM" => 'Y',
                                        "MESSAGE"  => 'Существует разбитый на счёт платёж. Смена статуса недоступна'
                                    ));
                                }
                            }
                        }

                            $operation = parent::getUpdateOperation($item, $context);
                            return $operation
                                    ->addAction(
                                        Operation::ACTION_AFTER_SAVE,
                                        new class extends Operation\Action {
                                            public function process(Item $item): Result
                                            {
                                                if($item->getUfAutopart() != 0)
                                                {
                                                    $connection = Application::getConnection();
                                                    $sql = $connection->query("SELECT `d`.`ID`, `d`.`TITLE`, `d`.`OPPORTUNITY`, `d`.`MYCOMPANY_ID`, `r`.`SRC_ENTITY_ID` AS `deal`, `r`.`SRC_ENTITY_TYPE_ID` AS `type`, `c`.`RQ_INN`
                                                                               FROM `b_crm_dynamic_items_159` AS `d`
                                                                               LEFT JOIN `b_crm_requisite` AS `c` ON `c`.`ENTITY_ID` = `d`.`COMPANY_ID`
                                                                               LEFT JOIN `b_crm_entity_relation` AS `r` ON `r`.`DST_ENTITY_ID` = `d`.`ID` AND `r`.`DST_ENTITY_TYPE_ID` = 159
                                                                               WHERE `d`.`UF_SMART_PAY_TYPE` = '29' AND `d`.`OPPORTUNITY` > 0 AND `d`.`ID` = ".(int)$item->getId());
                                                    $iResult = $sql->fetch();
                                                    $arTypeCrm[1] = 'L_';
                                                    $arTypeCrm[2] = 'D_';
                                                    $arTypeCrm[133] = 'T85_';
                                                    if(!empty($iResult) && !empty($iResult['RQ_INN']))
                                                    {
                                                        $sql = $connection->query("SELECT `e`.`ID`, `e`.`NAME`, `p1`.`VALUE` AS `p150`, `p2`.`VALUE` AS `p152`, `p3`.`VALUE` AS `p156`, 
                                                                                          `p4`.`VALUE` AS `p157`,`p5`.`VALUE` AS `p161`,`p6`.`VALUE_NUM` AS `p147`, `p7`.`VALUE` AS `p146`,    
                                                                                          `p8`.`VALUE` AS `p148`,`p9`.`VALUE` AS `p149`,`p10`.`VALUE` AS `p151`, `p11`.`VALUE` AS `p153`,    
                                                                                          `p12`.`VALUE` AS `p154`,`p13`.`VALUE` AS `p155`,`p14`.`VALUE` AS `p158`, `p15`.`VALUE` AS `p159`,    
                                                                                          `p16`.`VALUE` AS `p160`,`p17`.`VALUE` AS `p162`,`p18`.`VALUE` AS `p163`, `p19`.`VALUE` AS `p164`,    
                                                                                          `p20`.`VALUE` AS `p165`, `p6`.`VALUE_NUM` AS `sum`
                                                                                   FROM `b_iblock_element` AS `e`
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p1` ON `p1`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p1`.`IBLOCK_PROPERTY_ID` = 150
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p2` ON `p2`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p2`.`IBLOCK_PROPERTY_ID` = 152
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p3` ON `p3`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p3`.`IBLOCK_PROPERTY_ID` = 156
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p4` ON `p4`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p4`.`IBLOCK_PROPERTY_ID` = 157
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p5` ON `p5`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p5`.`IBLOCK_PROPERTY_ID` = 161
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p6` ON `p6`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p6`.`IBLOCK_PROPERTY_ID` = 147
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p7` ON `p7`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p7`.`IBLOCK_PROPERTY_ID` = 146
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p8` ON `p8`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p8`.`IBLOCK_PROPERTY_ID` = 148
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p9` ON `p9`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p9`.`IBLOCK_PROPERTY_ID` = 149
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p10` ON `p10`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p10`.`IBLOCK_PROPERTY_ID` = 151
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p11` ON `p11`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p11`.`IBLOCK_PROPERTY_ID` = 153
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p12` ON `p12`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p12`.`IBLOCK_PROPERTY_ID` = 154
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p13` ON `p13`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p13`.`IBLOCK_PROPERTY_ID` = 155
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p14` ON `p14`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p14`.`IBLOCK_PROPERTY_ID` = 158
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p15` ON `p15`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p15`.`IBLOCK_PROPERTY_ID` = 159
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p16` ON `p16`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p16`.`IBLOCK_PROPERTY_ID` = 160
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p17` ON `p17`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p17`.`IBLOCK_PROPERTY_ID` = 162
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p18` ON `p18`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p18`.`IBLOCK_PROPERTY_ID` = 163
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p19` ON `p19`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p19`.`IBLOCK_PROPERTY_ID` = 164
                                                                                   LEFT JOIN `b_iblock_element_property` AS `p20` ON `p20`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p20`.`IBLOCK_PROPERTY_ID` = 165                                                                               
                                                                                   WHERE `e`.`IBLOCK_ID` = 28
                                                                                    AND `p1`.`VALUE` = '".$iResult['RQ_INN']."'
                                                                                    AND `p2`.`VALUE` = '".$iResult['MYCOMPANY_ID']."'
                                                                                    AND `p6`.`VALUE_NUM` > 0
                                                                                    AND (`p3`.`VALUE` IS NULL OR `p3`.`VALUE` = '' OR `p3`.`VALUE` = 0)
                                                                                    AND (`p4`.`VALUE` IS NULL OR `p4`.`VALUE` = '' OR `p4`.`VALUE` = 0)
                                                                                    AND (`p5`.`VALUE` IS NULL OR `p5`.`VALUE` = '' OR `p5`.`VALUE` = 0)
                                                                                   GROUP BY `e`.`ID`");
                                                        $iPay = $sql->fetchAll();
                                                        if(!empty($iPay))
                                                        {
                                                            if(count($iPay) > 1)
                                                            {
                                                                $i = 0;
                                                                $idIsPay = 0;
                                                                $ppInfo = '';
                                                                foreach ($iPay as $id => $val)
                                                                {
                                                                    $ppInfo .= 'Платёж #'.$val['ID'].' от '.date('d.m.Y', strtotime($val['p146'])).' (Платёжное поручение №'.$val['p149'].')[BR]';
                                                                    
                                                                    if($val['sum'] == $iResult['OPPORTUNITY'])
                                                                    {
                                                                        $i++;
                                                                        $idIsPay = $id;
                                                                    }
                                                                }

                                                                if($i == 1)
                                                                {
                                                                    $arRow = $iPay[$idIsPay];
                                                                    if(\Bitrix\Main\Loader::includeModule('iblock'))
                                                                    {
                                                                        $el = new \CIBlockElement;
                                                                        $idPay = $el->Update($arRow['ID'],
                                                                            array(
                                                                                'NAME' => $arRow['NAME'],
                                                                                'ACTIVE_FROM' => date('d.m.Y H:i:s'),
                                                                                'MODIFIED_BY' => (int)$item->getUpdatedBy(),
                                                                                'ACTIVE' => 'Y',
                                                                                'IBLOCK_ID' => 28,
                                                                                'PROPERTY_VALUES' => array(
                                                                                    '146' => date('d.m.Y', strtotime($arRow['p146'])),                       #   DATE
                                                                                    '147' => $arRow['p147'],                                #   SUM
                                                                                    '148' => $arRow['p148'],                                #   SUM  OSN
                                                                                    '149' => $arRow['p149'],                       #   PP
                                                                                    '150' => $arRow['p150'],                       #   INN
                                                                                    '151' => $arRow['p151'],                       #   CONTR_NAME
                                                                                    '152' => $arRow['p152'],                       #   MY COMPANY
                                                                                    '153' => $arRow['p153'],                       #   R/S
                                                                                    '154' => $arRow['p154'],                       #   NAZNACH
                                                                                    '155' => $arTypeCrm[$iResult['type']].$iResult['deal'],                     #   DEAL/LEAD
                                                                                    '156' => (int)$iResult['ID'],                       #   ID INVOICE
                                                                                    '157' => 0,                                     #   TASK ID
                                                                                    '158' => '<a href="/crm/type/159/details/'.(int)$iResult['ID'].'/">'.$iResult['TITLE'].'</a>',
                                                                                    '159' => '',                                    #   TASK LINK
                                                                                    '160' => 0,
                                                                                    '161' => 0,                                     #   ZP
                                                                                    '162' => date('d.m.Y'),                         #   DATE EDIT
                                                                                    '163' => (int)$item->getUpdatedBy(),                       #   OPER
                                                                                    '164' => $arRow['p164'],                       #   OTVETSTV
                                                                                    '165' => $arRow['p165']                        #   COMMENT
                                                                                )
                                                                            )
                                                                        );
                                                                        if($idPay > 0)
                                                                        {
                                                                            $connection->query("UPDATE `b_crm_dynamic_items_159` SET `STAGE_ID` = 'DT159_1:SUCCESS', `CLOSEDATE` = '".$arRow['p146']."', `UF_PAY_DATE` = '".$arRow['p146']."', `UF_ID_PAY` = ".$arRow['ID'].", `UF_PAY_NOTE` = '".$arRow['p154']."' WHERE `ID` = ".(int)$iResult['ID']);
                                                                            #$item->set('CLOSEDATE', date('d.m.Y', strtotime($arRow['p146'])));
                                                                            #$item->set('UF_ID_PAY', $idPay);
                                                                            #$item->set('UF_PAY_NOTE', $arRow['p154']);
                                                                            #$item->set('UF_PAY_DATE', date('d.m.Y', strtotime($arRow['p146'])));
                                                                            #$item->set('STAGE_ID', 'DT159_1:SUCCESS');
                                                                            if(\Bitrix\Main\Loader::includeModule('im'))
                                                                            {
                                                                                $sql = $connection->query("SELECT `ID` FROM `b_im_chat` WHERE `ENTITY_ID` = 'DYNAMIC_159|".(int)$iResult['ID']."'");
                                                                                $iChat = $sql->fetch();
                                                                                $chat = new \CIMChat;
                                                                                $chat->AddMessage(array(
                                                                                    "TO_CHAT_ID" => $iChat['ID'],
                                                                                    "FROM_USER_ID" => 0,
                                                                                    "SYSTEM" => 'Y',
                                                                                    "MESSAGE"  => '[b]Оплата счёта произведена[/b]'
                                                                                ));
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                                elseif(\Bitrix\Main\Loader::includeModule('im'))
                                                                {
                                                                    $sql = $connection->query("SELECT `ID` FROM `b_im_chat` WHERE `ENTITY_ID` = 'DYNAMIC_159|".(int)$item->getId()."'");
                                                                    $iChat = $sql->fetch();
                                                                    $chat = new \CIMChat;
                                                                    $chat->AddMessage(array(
                                                                        "TO_CHAT_ID" => $iChat['ID'],
                                                                        "FROM_USER_ID" => 0,
                                                                        "SYSTEM" => 'Y',
                                                                        "MESSAGE"  => 'Имеются подходящие платежи для закрытия счёта[BR]'.$ppInfo
                                                                    ));
                                                                }
                                                            }
                                                            elseif(count($iPay) == 1)
                                                            {
                                                                $arRow = current($iPay);
                                                                if($arRow['sum'] == $iResult['OPPORTUNITY'])
                                                                {
                                                                    if(\Bitrix\Main\Loader::includeModule('iblock'))
                                                                    {
                                                                        $el = new \CIBlockElement;
                                                                        $idPay = $el->Update($arRow['ID'],
                                                                            array(
                                                                                'NAME' => $arRow['NAME'],
                                                                                'ACTIVE_FROM' => date('d.m.Y H:i:s'),
                                                                                'MODIFIED_BY' => (int)$item->getUpdatedBy(),
                                                                                'ACTIVE' => 'Y',
                                                                                'IBLOCK_ID' => 28,
                                                                                'PROPERTY_VALUES' => array(
                                                                                    '146' => date('d.m.Y', strtotime($arRow['p146'])),                       #   DATE
                                                                                    '147' => $arRow['p147'],                                #   SUM
                                                                                    '148' => $arRow['p148'],                                #   SUM  OSN
                                                                                    '149' => $arRow['p149'],                       #   PP
                                                                                    '150' => $arRow['p150'],                       #   INN
                                                                                    '151' => $arRow['p151'],                       #   CONTR_NAME
                                                                                    '152' => $arRow['p152'],                       #   MY COMPANY
                                                                                    '153' => $arRow['p153'],                       #   R/S
                                                                                    '154' => $arRow['p154'],                       #   NAZNACH
                                                                                '155' => $arTypeCrm[$iResult['type']].$iResult['deal'],                     #   DEAL/LEAD
                                                                                '156' => (int)$iResult['ID'],                       #   ID INVOICE
                                                                                    '157' => 0,                                     #   TASK ID
                                                                                '158' => '<a href="/crm/type/159/details/'.(int)$iResult['ID'].'/">'.$iResult['TITLE'].'</a>',
                                                                                    '159' => '',                                    #   TASK LINK
                                                                                    '160' => 0,
                                                                                    '161' => 0,                                     #   ZP
                                                                                    '162' => date('d.m.Y'),                         #   DATE EDIT
                                                                                '163' => (int)$item->getUpdatedBy(),                       #   OPER
                                                                                    '164' => $arRow['p164'],                       #   OTVETSTV
                                                                                    '165' => $arRow['p165']                        #   COMMENT
                                                                                )
                                                                            )
                                                                        );
                                                                        if($idPay > 0)
                                                                        {
                                                                            $connection->query("UPDATE `b_crm_dynamic_items_159` SET `STAGE_ID` = 'DT159_1:SUCCESS', `CLOSEDATE` = '".$arRow['p146']."', `UF_ID_PAY` = ".$arRow['ID'].", `UF_PAY_DATE` = '".$arRow['p146']."',  `UF_PAY_NOTE` = '".$arRow['p154']."' WHERE `ID` = ".(int)$iResult['ID']);
                                                                            #$item->set('CLOSEDATE', date('d.m.Y', strtotime($arRow['p146'])));
                                                                            #$item->set('UF_ID_PAY', $idPay);
                                                                            #$item->set('UF_PAY_NOTE', $arRow['p154']);
                                                                            #$item->set('UF_PAY_DATE', date('d.m.Y', strtotime($arRow['p146'])));
                                                                            #$item->set('STAGE_ID', 'DT159_1:SUCCESS');
                                                                            if(\Bitrix\Main\Loader::includeModule('im'))
                                                                            {
                                                                                $sql = $connection->query("SELECT `ID` FROM `b_im_chat` WHERE `ENTITY_ID` = 'DYNAMIC_159|".(int)$iResult['ID']."'");
                                                                                $iChat = $sql->fetch();
                                                                                $chat = new \CIMChat;
                                                                                $chat->AddMessage(array(
                                                                                    "TO_CHAT_ID" => $iChat['ID'],
                                                                                    "FROM_USER_ID" => 0,
                                                                                    "SYSTEM" => 'Y',
                                                                                    "MESSAGE"  => '[b]Оплата счёта произведена[/b]'
                                                                                ));
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                                
                                                return new Result();
                                            }
                                        }
                                    )
                                    ->addAction(
                                        Operation::ACTION_BEFORE_SAVE,
                                        new class extends Operation\Action {
                                            public function process(Item $item): Result
                                            {
                                                $connection = Application::getConnection();
                                                    $sql2 = $connection->query("SELECT `d`.`ID`, `d`.`TITLE`, `d`.`OPPORTUNITY`, `d`.`MYCOMPANY_ID`, `r`.`SRC_ENTITY_ID` AS `deal`, `r`.`SRC_ENTITY_TYPE_ID` AS `type`, `c`.`RQ_INN`
                                                                               FROM `b_crm_dynamic_items_159` AS `d`
                                                                               LEFT JOIN `b_crm_requisite` AS `c` ON `c`.`ENTITY_ID` = `d`.`COMPANY_ID`
                                                                               LEFT JOIN `b_crm_entity_relation` AS `r` ON `r`.`DST_ENTITY_ID` = `d`.`ID` AND `r`.`DST_ENTITY_TYPE_ID` = 159
                                                                               WHERE `d`.`UF_SMART_PAY_TYPE` = '28' AND `d`.`OPPORTUNITY` > 0 AND `d`.`ID` = ".(int)$item->getId());
                                                    $iResult2 = $sql2->fetch();
                                                    $arTypeCrm[1] = 'L_';
                                                    $arTypeCrm[2] = 'D_';
                                                    $arTypeCrm[133] = 'T85_';

                                                $sql = $connection->query("SELECT * FROM `b_crm_dynamic_items_159` WHERE `ID` = ".(int)$item->getId());
                                                $iResult = $sql->fetch();
                                                if(!empty($iResult) && $iResult['STAGE_ID'] != 'DT159_1:SUCCESS' && $item->getStageId() == 'DT159_1:SUCCESS')
                                                {
                                                    #   Оплата наличными
                                                    if($iResult['UF_SMART_PAY_TYPE'] == 28 && $iResult['OPPORTUNITY'] > 0)
                                                    {
                                                        $arNalUser = array();
                                                        $sql = $connection->query("SELECT `USER_ID` FROM `b_user_group` WHERE `GROUP_ID` = 30");
                                                        while($resNal = $sql->fetch())
                                                        {
                                                            $arNalUser[$resNal['USER_ID']] = $resNal['USER_ID'];
                                                        }
                                                        if($_SESSION['SESS_AUTH']['ADMIN'] == 1 || in_array((int)$item->getUpdatedBy(), $arNalUser))
                                                        {
                                                            #   Создаём платёж
                                                            if(\Bitrix\Main\Loader::includeModule('iblock'))
                                                            {
                                                                $el = new \CIBlockElement;
                                                                $idPay = $el->Add(
                                                                    array(
                                                                        'NAME' => 'Наличный платёж',
                                                                        'ACTIVE_FROM' => date('d.m.Y H:i:s'),
                                                                        'MODIFIED_BY' => (int)$item->getUpdatedBy(),
                                                                        'ACTIVE' => 'Y',
                                                                        'IBLOCK_ID' => 28,
                                                                        'PROPERTY_VALUES' => array(
                                                                            '147' => $iResult['OPPORTUNITY'],              #   SUM
                                                                            '156' => (int)$item->getId(),                  #   ID INVOICE
                                                                            '164' => $item->getUpdatedBy(),    #   OTVETSTV
                                                                            '146' => date('d.m.Y'),                        #   DATE PAY
                                                                            '162' => date('d.m.Y'),                        #   DATE EDIT
                                                                            '163' => $item->getUpdatedBy(),    #   OPER
                                                                            '152' => $iResult['MYCOMPANY_ID'],             #   MY COMPANY
                                                                            '157' => 0,                                    #   TASK ID
                                                                            '155' => $arTypeCrm[$iResult2['type']].$iResult2['deal'],                                    #   DEAL
                                                                            '159' => '',                                   #   TASK LINK
                                                                            '158' => '<a href="/crm/type/159/details/'.(int)$item->getId().'/">'.$iResult['TITLE'].'</a>',
                                                                            '165' => 'Оплата по счёту №'.(int)$item->getId(),                                   #   COMMENT
                                                                            '161' => 0,                                    #   ZP
                                                                            '160' => 1
                                                                        )
                                                                    )
                                                                );
                                                                if($idPay > 0)
                                                                {
                                                                    $item->set('CLOSEDATE', date('d.m.Y'));
                                                                    $item->set('UF_PAY_DATE', date('d.m.Y'));
                                                                    $item->set('STAGE_ID', 'DT159_1:SUCCESS');
                                                                    $item->set('UF_ID_PAY', $idPay);
                                                                    
                                                                    #$connection->query("UPDATE `b_crm_dynamic_items_159` SET `CLOSEDATE` = '".date('Y-m-d')."', `STAGE_ID` = 'DT159_1:SUCCESS', `UF_ID_PAY` = ".$idPay." WHERE `ID` = ".(int)$item->getId());
                                                                    if(\Bitrix\Main\Loader::includeModule('im'))
                                                                    {
                                                                        $sql = $connection->query("SELECT `ID` FROM `b_im_chat` WHERE `ENTITY_ID` = 'DYNAMIC_159|".(int)$item->getId()."'");
                                                                        $iChat = $sql->fetch();

                                                                        $chat = new \CIMChat;
                                                                        $chat->AddMessage(array(
                                                                            "TO_CHAT_ID" => $iChat['ID'],
                                                                            "FROM_USER_ID" => 0,
                                                                            "SYSTEM" => 'Y',
                                                                            "MESSAGE"  => '[b]'.$_SESSION['SESS_AUTH']['LAST_NAME'].' '.$_SESSION['SESS_AUTH']['FIRST_NAME'].'[/b] получил(а) '.$iResult['OPPORTUNITY'].'руб. в счёт оплаты за сделку'
                                                                        ));
                                                                    }
                                                                }
                                                                else
                                                                    $connection->query("UPDATE `b_crm_dynamic_items_159` SET `STAGE_ID` = `PREVIOUS_STAGE_ID` WHERE `ID` = ".(int)$item->getId());
                                                            }
                                                            else
                                                                $connection->query("UPDATE `b_crm_dynamic_items_159` SET `STAGE_ID` = `PREVIOUS_STAGE_ID` WHERE `ID` = ".(int)$item->getId());
                                                        }
                                                        else
                                                            $item->setStageId($iResult['STAGE_ID']);
                                                    }

                                                    if($iResult['UF_SMART_PAY_TYPE'] == 29)
                                                    {
                                                        #$item->setStageId($iResult['STAGE_ID']);
                                                    }
                                                }

                                                return new Result();
                                            }
                                        }
                                )
                                ->addAction(
                                        Operation::ACTION_BEFORE_SAVE,
                                        new class extends Operation\Action {
                                            public function process(Item $item): Result
                                            {
                                                $connection = Application::getConnection();
                                                $sqlStage = $connection->query("SELECT * FROM `b_crm_dynamic_items_159` WHERE `ID` = ".(int)$item->getId());
                                                $iResultStage = $sqlStage->fetch();

                                                if($iResultStage['STAGE_ID'] != $item->getStageId())
                                                {
                                                    $arUsGroup = array();
                                                    $arUsGroup[487] = 487;
                                                    $sqlG = $connection->query("SELECT * FROM b_user_group WHERE GROUP_ID = 27");
                                                    while($resG = $sqlG->fetch())
                                                    {
                                                        $arUsGroup[$resG['USER_ID']] = $resG['USER_ID'];
                                                    }

                                                    $sqlD = $connection->query("SELECT `d`.`ID`, `d`.`TITLE`, `r`.`SRC_ENTITY_ID` AS `deal`, `r`.`SRC_ENTITY_TYPE_ID` AS `type`
                                                                                FROM `b_crm_dynamic_items_159` AS `d`
                                                                                LEFT JOIN `b_crm_entity_relation` AS `r` ON `r`.`DST_ENTITY_ID` = `d`.`ID` AND `r`.`DST_ENTITY_TYPE_ID` = 159
                                                                                WHERE `d`.`ID` = ".(int)$item->getId());
                                                    $resD = $sqlD->fetch();
                                                    if($resD['type'] == 2)
                                                    {
                                                        $sqlU = $connection->query("SELECT `ATTR` FROM `b_crm_entity_perms` WHERE `ENTITY` = 'DEAL' AND (`ATTR` LIKE 'U%' OR `ATTR` LIKE 'CU%') AND `ENTITY_ID` = ".(int)$resD['deal']);
                                                        while($resU = $sqlU->fetch())
                                                        {
                                                            $tmpU = str_replace(array('C', 'U'), '', $resU['ATTR']);
                                                            $arUsGroup[$tmpU] = $tmpU;
                                                        }
                                                    }

                                                    if($item->getStageId() == 'DT159_1:FAIL')
                                                    {
                                                        $sqlPay = $connection->query("SELECT `e`.`ID` 
                                                                                      FROM `b_iblock_element` AS `e`
                                                                                      LEFT JOIN `b_iblock_element_property` AS `p1` ON `p1`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p1`.`IBLOCK_PROPERTY_ID` = 147
                                                                                      LEFT JOIN `b_iblock_element_property` AS `p2` ON `p2`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p2`.`IBLOCK_PROPERTY_ID` = 156
                                                                                      WHERE `e`.`IBLOCK_ID` = 28 AND `p1`.`VALUE` > 0 AND `p2`.`VALUE` = ".(int)$item->getId());
                                                        $resPayInv = $sqlPay->fetch();
                                                        if(!empty($resPayInv))
                                                        {
                                                            $item->setStageId($iResultStage['STAGE_ID']);
                                                            if (\Bitrix\Main\Loader::includeModule('im')) 
                                                            {
                                                                $im = new \CIMMessenger;
                                                                $im->Add(array(
                                                                    'TO_USER_ID' => $item->getUpdatedBy(),
                                                                    'FROM_USER_ID' => 1,
                                                                    'MESSAGE_TYPE' => 'S',
                                                                    'NOTIFY_MODULE' => 'im',
                                                                    'NOTIFY_MESSAGE' => 'Имеется списанный на этот счёт платёж. Удаление невозможно'
                                                                ));
                                                            }
                                                        }
                                                    }
                                                    elseif(in_array($item->getUpdatedBy(), $arUsGroup))
                                                    {
                                                        if($item->getStageId() != $iResultStage['STAGE_ID'] && $item->getStageId() == 'DT159_1:PREPARATION')
                                                            $item->setClosedate(date('d.m.Y', strtotime('+7 day')));
                                                        elseif($item->getStageId() != $iResultStage['STAGE_ID'] && $item->getStageId() == 'DT159_1:CLIENT')
                                                            $item->setClosedate(date('d.m.Y', strtotime('+14 day')));
                                                        elseif($item->getStageId() != $iResultStage['STAGE_ID'] && $item->getStageId() == 'DT159_1:UC_OZ2CEE')
                                                            $item->setClosedate(date('d.m.Y', strtotime('+30 day')));
                                                    }
                                                    else
                                                    {
                                                        $item->setStageId($iResultStage['STAGE_ID']);
                                                        if (\Bitrix\Main\Loader::includeModule('im')) 
                                                        {
                                                            $im = new \CIMMessenger;
                                                            $im->Add(array(
                                                                'TO_USER_ID' => $item->getUpdatedBy(),
                                                                'FROM_USER_ID' => 1,
                                                                'MESSAGE_TYPE' => 'S',
                                                                'NOTIFY_MODULE' => 'im',
                                                                'NOTIFY_MESSAGE' => 'У вас нет прав на изменение стадии счета.[BR]Данное действие могут выполнить только сотрудники, указанные в группе Контроль за Сделками'
                                                            ));
                                                        }
                                                    }
                                                }

                                                return new Result();
                                            }
                                        }
                                    );
                    }

                    public function getDeleteOperation(Item $item, Context $context = null): Operation\Delete
                    {
                        $operation = parent::getDeleteOperation($item, $context);
                        return $operation;
                    }
                };
                return $factory;
            }
            
            if ($entityTypeId === 128)
            {
                $arStages3 = array();
                $arStages4 = array();
                $arStages3[0] = 'DT128_3:NEW';
                $arStages3[1] = 'DT128_3:PREPARATION';
                $arStages3[2] = 'DT128_3:CLIENT';
                $arStages3[3] = 'DT128_3:1';
                #$arStages3[4] = 'DT128_3:2';
                $arStages3[4] = 'DT128_3:SUCCESS';
                $arStages3[4] = 'DT128_3:FAIL';
                $arStages4[0] = 'DT128_4:NEW';
                $arStages4[1] = 'DT128_4:PREPARATION';
                $arStages4[2] = 'DT128_4:SUCCESS';
                $arStages4[3] = 'DT128_4:FAIL';

                $chatUsers = array();
                $type = $this->getTypeByEntityTypeId($entityTypeId);
                

                $factory = new class($type) extends Service\Factory\Dynamic {
                    public function getAddOperation(Item $item, Context $context = null): Operation\Add
                    {
                        $operation = parent::getAddOperation($item, $context);
                        return $operation ->addAction(
                            Operation::ACTION_AFTER_SAVE,
                            new class extends Operation\Action {
                                public function process(Item $item): Result
                                {
                                    #   Дата - не раньше завтрашнего дня
                                    $tmp = explode('.', $item->getUfCrm_3_1693387031126());
                                    $newDate = (!empty($tmp[2])) ? $tmp[2].'-'.$tmp[1].'-'.$tmp[0] : date('Y-m-d', strtotime($item->getUfCrm_3_1693387031126()));
                                    if($item->getStageId() == 'DT128_3:NEW' && $newDate < date('Y-m-d', strtotime('+1 day')))
                                    {
                                        if(($newDate == date('Y-m-d') && date('H') >= 15) || $newDate < date('Y-m-d'))
                                        {
                                            $day  = true;
                                            $date = date('Y-m-d');
                                            $holyday = array('2024-03-08', '2024-04-29', '2024-04-30', '2024-05-01', '2024-05-09', '2024-05-10', '2024-06-12', '2024-11-04', '2024-12-30', '2024-12-31', '2025-01-01', '2025-01-02', '2025-01-03', '2025-01-06', '2025-01-07');
                                            while($day)
                                            {
                                                if(date('w', strtotime($date.' +1 day')) == 6)
                                                    $date = date('Y-m-d', strtotime($date.' +3 day'));
                                                elseif(date('w', strtotime($date.' +1 day')) == 0)
                                                    $date = date('Y-m-d', strtotime($date.' +2 day'));
                                                else
                                                    $date = date('Y-m-d', strtotime($date.' +1 day'));

                                                if(!in_array(date('Y-m-d', strtotime($date)), $holyday))
                                                    $day = false;
                                                else
                                                    $date = date('Y-m-d', strtotime($date.' +1 day'));
                                            }

                                            $item->setUfCrm_3_1693387031126(date('d.m.Y', strtotime($date)));
                                        }
                                    }

                                    $connection = Application::getConnection();
                                    $recordset  = $connection->query("SELECT `ASSIGNED_BY_ID` FROM `b_crm_dynamic_items_128` WHERE `ID` = ".(int)$item->getId());
                                    $recordUser = $recordset->fetch();
                                    $chatUsers[] = $recordUser['ASSIGNED_BY_ID'];

                                    $sql = $connection->query("SELECT `USER_ID` FROM `b_crm_observer` WHERE `ENTITY_TYPE_ID` = 128 AND `ENTITY_ID` = ".(int)$item->getId());
                                    $resUsers = $sql->fetchAll();
                                    if(!empty($resUsers))
                                    {
                                        foreach($resUsers as $u)
                                        {
                                            $chatUsers[] = $u['USER_ID'];
                                        }
                                    }

                                    $title = str_replace('{ID}', $item->getId(), $item->getTitle());
                                    $item->setTitle($title);
                                    
                                    if(\Bitrix\Main\Loader::includeModule('im'))
                                    {
                                        $chat = new \CIMChat;
                                        $chatId = $chat->Add(array(
                                            'TITLE' => $item->getTitle(),
                                            'USERS' => $chatUsers,
                                            'TYPE' => IM_MESSAGE_CHAT,
                                            'ENTITY_TYPE' => 'CRM',
                                            'AUTHOR_ID' => $recordUser['ASSIGNED_BY_ID'],
                                            'ENTITY_ID' => 'DYNAMIC_128|'.(int)$item->getId()
                                        ));
                                        $chat->AddMessage(array(
                                            "TO_CHAT_ID" => $chatId,
                                            "FROM_USER_ID" => 0,
                                            "SYSTEM" => 'Y',
                                            "MESSAGE"  => '[b]Создан чат для обсуждения элемента сущности "Счёт на оплату"[/b][BR][URL=/crm/type/128/details/'.(int)$item->getId().'/]'.$item->getTitle().'[/URL]',
                                        ));
                                    }

                                    return new Result();
                                }
                            }
                        );
                    }

                    public function getUpdateOperation(Item $item, Context $context = null): Operation\Update
                    {
                        $operation = parent::getUpdateOperation($item, $context);
                        return $operation ->addAction(
                            Operation::ACTION_BEFORE_SAVE,
                            new class extends Operation\Action {
                                public function process(Item $item): Result
                                {
                                    $arStages3 = array();
                                    $arStages4 = array();
                                    $arStages3[0] = 'DT128_3:NEW';
                                    $arStages3[1] = 'DT128_3:PREPARATION';
                                    $arStages3[2] = 'DT128_3:CLIENT';
                                    $arStages3[3] = 'DT128_3:1';
                                    #$arStages3[4] = 'DT128_3:2';
                                    $arStages3[4] = 'DT128_3:SUCCESS';
                                    $arStages3[5] = 'DT128_3:FAIL';
                                    $arStages4[0] = 'DT128_4:NEW';
                                    $arStages4[1] = 'DT128_4:PREPARATION';
                                    $arStages4[2] = 'DT128_4:SUCCESS';
                                    $arStages4[3] = 'DT128_4:FAIL';
                                    
                                    $task_id = $item->getUfTaskNum();
                                    $connection = Application::getConnection();

                                    #   Исходные данные по счёту
                                    $sql = $connection->query("SELECT * FROM `b_crm_dynamic_items_128` WHERE `ID` = ".(int)$item->getId());
                                    $invoice = $sql->fetch();
                                    #   Дата - не раньше завтрашнего дня
                                    $tmp = explode('.', $item->getUfCrm_3_1693387031126());
                                    $newDate = (!empty($tmp[2])) ? $tmp[2].'-'.$tmp[1].'-'.$tmp[0] : 0;
                                    if($item->getStageId() == 'DT128_3:NEW' && $newDate < date('Y-m-d', strtotime('+1 day')))
                                    {
                                        if(($newDate == date('Y-m-d') && date('H') >= 15) || $newDate < date('Y-m-d'))
                                        {
                                            $day  = true;
                                            $date = date('Y-m-d');
                                            $holyday = array('2024-03-08', '2024-04-29', '2024-04-30', '2024-05-01', '2024-05-09', '2024-05-10', '2024-06-12', '2024-11-04', '2024-12-30', '2024-12-31', '2025-01-01', '2025-01-02', '2025-01-03', '2025-01-06', '2025-01-07');
                                            while($day)
                                            {
                                                if(date('w', strtotime($date.' +1 day')) == 6)
                                                    $date = date('Y-m-d', strtotime($date.' +3 day'));
                                                elseif(date('w', strtotime($date.' +1 day')) == 0)
                                                    $date = date('Y-m-d', strtotime($date.' +2 day'));
                                                else
                                                    $date = date('Y-m-d', strtotime($date.' +1 day'));

                                                if(!in_array(date('Y-m-d', strtotime($date)), $holyday))
                                                    $day = false;
                                                else
                                                    $date = date('Y-m-d', strtotime($date.' +1 day'));
                                            }

                                            $item->setUfCrm_3_1693387031126(date('d.m.Y', strtotime($date)));
                                        }
                                    }
                                    #   Смена компании
                    if($item->getMycompanyId() != $invoice['MYCOMPANY_ID'])
                                    {
                                        $arInvComp = array();
                                        $sql = $connection->query("SELECT `USER_ID` FROM `b_user_group` WHERE `GROUP_ID` = 24 OR `GROUP_ID` = 1");
                                        while($ric = $sql->fetch())
                                        {
                                            $arInvComp[$ric['USER_ID']] = $ric['USER_ID'];
                                        }
                                        
                                        if($item->getAssignedById() != $item->getUpdatedBy() && !in_array($item->getUpdatedBy(), $arInvComp))
                                        {
                                            $item->setMycompanyId($invoice['MYCOMPANY_ID']);
                                        }
                                    }

                                    #   Нельзя менять CRM
                                    $crm128 = array();
                                    $crm128[1]   = '';
                                    $crm128[2]   = '';
                                    $crm128[133] = '';
                                    $sql = $connection->query("SELECT `SRC_ENTITY_TYPE_ID`, `SRC_ENTITY_ID` FROM `b_crm_entity_relation` WHERE `DST_ENTITY_TYPE_ID` = 128 AND `DST_ENTITY_ID` = ".(int)$item->getId());
                                    $tmpEnt = $sql->fetch();
                                    if(!empty($tmpEnt))
                                    {
                                        $crm128[$tmpEnt['SRC_ENTITY_TYPE_ID']] = $tmpEnt['SRC_ENTITY_ID'];
                                    }
                                    $item->setParentId_1($crm128[1]);
                                    $item->setParentId_2($crm128[2]);
                                    $item->setParentId_133($crm128[133]);

                                    if($item->getCategoryId() == 4 && $invoice['CATEGORY_ID'] == 3)
                                    {
                                        $item->setStageId('DT128_4:NEW');
                                        $connection->query("UPDATE `b_crm_dynamic_items_128` SET `CATEGORY_ID` = 4, `STAGE_ID` = 'DT128_4:NEW' WHERE `ID` = ".(int)$item->getId());
                                        $connection->query("UPDATE `b_crm_entity_perms` SET `ATTR` = 'STAGE_IDDT128_4:NEW' WHERE `ENTITY` = 'DYNAMIC_128_C3' AND `ATTR` = 'STAGE_IDDT128_3:NEW' AND `ENTITY_ID` = ".(int)$item->getId());
                                        $connection->query("UPDATE `b_crm_entity_perms` SET `ENTITY` = 'DYNAMIC_128_C4' WHERE `ENTITY` = 'DYNAMIC_128_C3' AND `ENTITY_ID` = ".(int)$item->getId());
                                    }
                                    elseif($item->getCategoryId() == 3 && $invoice['CATEGORY_ID'] == 4)
                                    {
                                        $item->setStageId('DT128_3:NEW');
                                        $connection->query("UPDATE `b_crm_dynamic_items_128` SET `CATEGORY_ID` = 3, `STAGE_ID` = 'DT128_3:NEW' WHERE `ID` = ".(int)$item->getId());
                                        $connection->query("UPDATE `b_crm_entity_perms` SET `ATTR` = 'STAGE_IDDT128_3:NEW' WHERE `ENTITY` = 'DYNAMIC_128_C3' AND `ATTR` = 'STAGE_IDDT128_4:NEW' AND `ENTITY_ID` = ".(int)$item->getId());
                                    }
                                    
                                    #   Сделка/Лид/МТР
                                    $sql = $connection->query("SELECT `VALUE`, REPLACE(`VALUE`, 'D_', '') AS `deal`, REPLACE(`VALUE`, 'L_', '') AS `lead`, REPLACE(`VALUE`, 'T85_', '') AS `mtr`  FROM `b_utm_tasks_task` WHERE `VALUE_ID` = ".(int)$task_id);
                                    $t = $sql->fetch();
                                    if(!empty($t))
                                    {
                                        if((int)$t['VALUE'] == 0)
                                        {
                                            if((int)$t['deal'] > 0)
                                                $item->setParentId_2($t['deal']);
                                            elseif((int)$t['lead'] > 0)
                                                $item->setParentId_1($t['lead']);
                                            elseif((int)$t['mtr'] > 0)
                                                $item->setParentId_133($t['mtr']);
                                        }
                                    }

                                    $task_id = $item->getUfTaskNum();

                                    #   Сумма
                                    $sql = $connection->query("SELECT SUM(`VALUE_NUM`) AS `PAYED`
                                                               FROM `b_iblock_element_property`
                                                               WHERE `IBLOCK_PROPERTY_ID` = 147
                                                                AND `IBLOCK_ELEMENT_ID` IN(SELECT `IBLOCK_ELEMENT_ID`
                                                                                           FROM `b_iblock_element_property`
                                                                                           WHERE `IBLOCK_PROPERTY_ID` = 156
                                                                                            AND `VALUE` = ".(int)$item->getId().")
                                                                AND `IBLOCK_ELEMENT_ID` IN(SELECT `IBLOCK_ELEMENT_ID`
                                                                                           FROM `b_iblock_element_property`
                                                                                           WHERE `IBLOCK_PROPERTY_ID` = 157
                                                                                            AND `VALUE` = ".(int)$task_id.")
                                                                AND `IBLOCK_ELEMENT_ID` IN(SELECT `IBLOCK_ELEMENT_ID`
                                                                                           FROM `b_iblock_element_property`
                                                                                           WHERE `IBLOCK_PROPERTY_ID` = 158 
                                                                                            AND `VALUE` NOT LIKE '%/159/%')");
                                    $paySum = $sql->fetch();
                                    $payedSum = $paySum['PAYED'] + $item->getOpportunity();
                                    $item->setUfToPay($payedSum);

                                    if($task_id > 0)
                                    {
                                        $sql = $connection->query("SELECT `TITLE` FROM `b_tasks` WHERE `ID` = ".(int)$task_id);
                                        $resTask = $sql->fetch();
                                        if(!empty($resTask))
                                            $task_link = '<a href="/company/personal/user/0/tasks/task/view/'.$task_id.'/">'.$resTask['TITLE'].'</a>';
                                        else
                                            $task_link = '';
                                    }
                                    else
                                        $task_link = '';

                                    #   Проверка смены статуса
                                    if($item->getStageId() != $invoice['STAGE_ID'])
                                    {
                                        #   Проверка на перепрыг =)
                                        if($item->getCategoryId() == 3)
                                        {
                                            $key1 = array_search($item->getStageId(), $arStages3);
                                            $key2 = array_search($invoice['STAGE_ID'], $arStages3);
                                        }
                                        else
                                        {
                                            $key1 = array_search($item->getStageId(), $arStages4);
                                            $key2 = array_search($invoice['STAGE_ID'], $arStages4);
                                        }

                                        if(($key1 +1) != $key2 && ($key1 -1) != $key2 && $item->getStageId() != 'DT128_3:FAIL' && $item->getStageId() != 'DT128_3:NEW' && $item->getStageId() != 'DT128_3:3' && $item->getStageId() != 'DT128_3:2' && $invoice['STAGE_ID'] != 'DT128_3:3' && $item->getStageId() != 'DT128_3:SUCCESS' && $item->getStageId() != 'DT128_4:FAIL')
                                        {
                                            $item->setStageId($invoice['STAGE_ID']);
                                        }
                                        else
                                        {
                                            if($invoice['STAGE_ID'] == 'DT128_3:SUCCESS' && $item->getStageId() == 'DT128_3:1')
                                            {
                                                $sql = $connection->query("SELECT `GROUP_ID` FROM `b_user_group` WHERE `GROUP_ID` = 25 AND `USER_ID` = ".(int)$item->getUpdatedBy());
                                                $iRes = $sql->fetch();
                                                if(empty($iRes))
                                                {
                                                    $item->setStageId('DT128_3:SUCCESS');
                                                }
                                            }
                                            elseif($invoice['STAGE_ID'] == 'DT128_3:FAIL' && $item->getStageId() == 'DT128_3:NEW')
                                            {
                                                $sql = $connection->query("SELECT `GROUP_ID` FROM `b_user_group` WHERE `GROUP_ID` = 25 AND `USER_ID` = ".(int)$item->getUpdatedBy());
                                                $iRes = $sql->fetch();
                                                if(empty($iRes))
                                                {
                                                    $item->setStageId('DT128_3:FAIL');
                                                }
                                                else
                                                {
                                                    $tmp = explode('.', $item->getUfCrm_3_1693387031126());
                                                    $newDate = (!empty($tmp[2])) ? $tmp[2].'-'.$tmp[1].'-'.$tmp[0] : date('Y-m-d', strtotime($item->getUfCrm_3_1693387031126()));
                                                    if($newDate < date('Y-m-d', strtotime('+1 day')))
                                                    {
                                                        if(($newDate == date('Y-m-d') && date('H') >= 15) || $newDate < date('Y-m-d'))
                                                        {
                                                            $day  = true;
                                                            $date = date('Y-m-d');
                                                            $holyday = array('2024-03-08', '2024-04-29', '2024-04-30', '2024-05-01', '2024-05-09', '2024-05-10', '2024-06-12', '2024-11-04', '2024-12-30', '2024-12-31', '2025-01-01', '2025-01-02', '2025-01-03', '2025-01-06', '2025-01-07');
                                                            while($day)
                                                            {
                                                                if(date('w', strtotime($date.' +1 day')) == 6)
                                                                    $date = date('Y-m-d', strtotime($date.' +3 day'));
                                                                elseif(date('w', strtotime($date.' +1 day')) == 0)
                                                                    $date = date('Y-m-d', strtotime($date.' +2 day'));
                                                                else
                                                                    $date = date('Y-m-d', strtotime($date.' +1 day'));

                                                                if(!in_array(date('Y-m-d', strtotime($date)), $holyday))
                                                                    $day = false;
                                                                else
                                                                    $date = date('Y-m-d', strtotime($date.' +1 day'));
                                                            }

                                                            $item->setUfCrm_3_1693387031126(date('d.m.Y', strtotime($date)));
                                                        }
                                                    }
                                                }
                                            }
                                            elseif($invoice['STAGE_ID'] == 'DT128_3:NEW' && $item->getStageId() == 'DT128_3:FAIL' || $invoice['STAGE_ID'] == 'DT128_4:NEW' && $item->getStageId() == 'DT128_4:FAIL')
                                            {
                                                $sql = $connection->query("SELECT `USER_ID` FROM `b_crm_observer` WHERE `ENTITY_TYPE_ID` = 128 AND `USER_ID` = ".(int)$item->getUpdatedBy()." AND `ENTITY_ID` = ".(int)$item->getId());
                                                $resU = $sql->fetch();
                                                if(empty($resU) && $item->getAssignedById() != $item->getUpdatedBy())
                                                    $item->setStageId($invoice['STAGE_ID']);
                                            }
                                            else
                                            {
                                                #   Проверка на пользователя
                                                $arUsers = array();
                                                $arUsers[3]   = 3;
                                                $arUsers[5]   = 5;
                                                $arUsers[445]   = 445;
                                                $arUsers[121] = 121;
                                                $arUsers[96]  = 96;
                                                $arUsers[25]  = 25;
                                                $arUsers[60]  = 60;
                                                $arUsers[590] = 590;
                                                $arUsers[801] = 801;
                                                
                                                $arNalUser = array();
                                                $sql = $connection->query("SELECT `USER_ID` FROM `b_user_group` WHERE `GROUP_ID` = 30");
                                                while($resNal = $sql->fetch())
                                                {
                                                    $arNalUser[$resNal['USER_ID']] = $resNal['USER_ID'];
                                                }

                                                #   Безнал! Ожидает оплаты -> Оплата сегодня
                                                if($item->getStageId() == 'DT128_3:PREPARATION' && $invoice['STAGE_ID'] != 'DT128_3:PREPARATION')
                                                {
                                                    if(!in_array($item->getUpdatedBy(), $arUsers))
                                                    {
                                                        $item->setStageId($invoice['STAGE_ID']);
                                                        if (\Bitrix\Main\Loader::includeModule('im')) 
                                                        {
                                                            $im = new \CIMMessenger;
                                                            $im->Add(array(
                                                                'TO_USER_ID' => $item->getUpdatedBy(),
                                                                'FROM_USER_ID' => 1,
                                                                'MESSAGE_TYPE' => 'S',
                                                                'NOTIFY_MODULE' => 'im',
                                                                'NOTIFY_MESSAGE' => 'Данное действие могут выполнить [b]Ровнов А., Никитин В., Калёнова Н., Рассолова О., Семенова Е., Капитанова Л.[/b]'
                                                            ));
                                                        }
                                                    } else {
                                                        
                                                    }
                                                }
                                                
                                                if($invoice['STAGE_ID'] == 'DT128_3:CLIENT' && $item->getStageId() != 'DT128_3:CLIENT')
                                                {
                                                    $item->setStageId($invoice['STAGE_ID']);
                                                }
                                                
                                                
                                                if($item->getStageId('DT128_3:1') && $invoice['STAGE_ID'] != 'DT128_3:1' && $item->getUfPpDate() > 0)
                                                {
                                                    $item->setClosedate(date('d.m.Y', strtotime($item->getUfPpDate() . ' +1 month')));
                                                }

                                                #   Безнал! Закрытие счёта
                                                if($invoice['STAGE_ID'] == 'DT128_3:1' && $item->getStageId() == 'DT128_3:3')
                                                {
                                                    $sql = $connection->query("SELECT `GROUP_ID` FROM `b_user_group` WHERE `GROUP_ID` = 25 AND `USER_ID` = ".(int)$item->getUpdatedBy());
                                                    $iRes = $sql->fetch();
                                                    if(!empty($iRes))
                                                    {
                                                        if (\Bitrix\Main\Loader::includeModule('im'))
                                                        {
                                                            $sql = $connection->query("SELECT `ID` FROM `b_im_chat` WHERE `ENTITY_ID` = 'DYNAMIC_128|" . (int)$item->getId() . "'");
                                                            $iChat = $sql->fetch();
                                                            $chat = new \CIMChat;
                                                            $chat->AddMessage(array(
                                                                "TO_CHAT_ID" => $iChat['ID'],
                                                                "FROM_USER_ID" => $item->getUpdatedBy(),
                                                                "SYSTEM" => 'N',
                                                                "MESSAGE" => 'Документы не требуются'
                                                            ));
                                                        }
                                                    }
                                                    else
                                                    {
                                                        $item->setStageId($invoice['STAGE_ID']);
                                                        if (\Bitrix\Main\Loader::includeModule('im'))
                                                        {
                                                            $im = new \CIMMessenger;
                                                            $im->Add(array(
                                                                'TO_USER_ID' => $item->getUpdatedBy(),
                                                                'FROM_USER_ID' => 487,
                                                                'MESSAGE_TYPE' => 'S',
                                                                'NOTIFY_MODULE' => 'im',
                                                                'NOTIFY_MESSAGE' => 'Данное действие могут выполнить только сотрудники, указанные в группе [b]Действия с первичными документами[/b]'
                                                            ));
                                                        }
                                                    }
                                                }
                                                elseif($item->getStageId() == 'DT128_3:SUCCESS' || $item->getStageId() == 'DT128_3:FAIL')
                                                {
                                                    
                                                }

                                                #   Наличка
                                                if($item->getStageId() == 'DT128_4:PREPARATION' && !in_array($item->getUpdatedBy(), $arNalUser))
                                                {
                                                    $item->setStageId($invoice['STAGE_ID']);
                                                }
                                                elseif($item->getStageId() == 'DT128_4:SUCCESS' && $invoice['STAGE_ID'] == 'DT128_4:PREPARATION')
                                                {
                                                    if(!in_array($item->getUpdatedBy(), $arNalUser))
                                                        $item->setStageId($invoice['STAGE_ID']);
                                                    else
                                                    {
                                                        #   Разбиваем платёж
                                                        if (\Bitrix\Main\Loader::includeModule('iblock')) 
                                                        {
                                                            if($item->getParentId_2() > 0)
                                                                $crm = 'D_'.$item->getParentId_2();
                                                            elseif($item->getParentId_1() > 0)
                                                                $crm = 'L_'.$item->getParentId_1();
                                                            elseif($item->getParentId_133() > 0)
                                                                $crm = 'T85_'.$item->getParentId_133();
                                                            else
                                                                $crm = '';

                                                            $sql = $connection->query("SELECT `p1`.`VALUE_NUM` AS `VALUE`
                                                                                       FROM `b_iblock_element` AS `e`
                                                                                       LEFT JOIN `b_iblock_element_property` AS `p1` ON `p1`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p1`.`IBLOCK_PROPERTY_ID` = 421
                                                                                       LEFT JOIN `b_iblock_element_property` AS `p2` ON `p2`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p2`.`IBLOCK_PROPERTY_ID` = 163
                                                                                       LEFT JOIN `b_iblock_element_property` AS `p3` ON `p3`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p3`.`IBLOCK_PROPERTY_ID` = 160
                                                                                       WHERE `e`.`IBLOCK_ID` = 28 AND `p2`.`VALUE` = ".(int)$item->getUpdatedBy()." AND `p3`.`VALUE` = '1'
                                                                                       ORDER BY `e`.`ID` DESC
                                                                                       LIMIT 1");
                                                            $resB = $sql->fetch();
                                                            $sql = $connection->query("SELECT `p1`.`VALUE_NUM` AS `VALUE`
                                                                                       FROM `b_iblock_element` AS `e`
                                                                                       LEFT JOIN `b_iblock_element_property` AS `p1` ON `p1`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p1`.`IBLOCK_PROPERTY_ID` = 422
                                                                                       LEFT JOIN `b_iblock_element_property` AS `p3` ON `p3`.`IBLOCK_ELEMENT_ID` = `e`.`ID` AND `p3`.`IBLOCK_PROPERTY_ID` = 160
                                                                                       WHERE `e`.`IBLOCK_ID` = 28 AND `p3`.`VALUE` = '1'
                                                                                       ORDER BY `e`.`ID` DESC
                                                                                       LIMIT 1");
                                                            $resA = $sql->fetch();

                                                            $el = new \CIBlockElement;
                                                            $idPay = $el->Add(
                                                                array(
                                                                    'NAME' => 'Оплата счёта',
                                                                    'ACTIVE_FROM' => date('d.m.Y H:i:s'),
                                                                    'MODIFIED_BY' => (int)$item->getUpdatedBy(),
                                                                    'ACTIVE' => 'Y',
                                                                    'CODE' => md5(date('d.m.Y H:i:s')),
                                                                    'IBLOCK_ID' => 28,
                                                                    'PROPERTY_VALUES' => array(
                                                                        '146' => date('d.m.Y'),                       #   DATE
                                                                        '147' => (abs($item->getOpportunity()) * -1),                                #   SUM
                                                                        '148' => (abs($item->getOpportunity()) * -1),                                #   SUM  OSN
                                                                        '149' => '',                       #   PP
                                                                        '150' => '',                       #   INN
                                                                        '151' => '',                       #   CONTR_NAME
                                                                        '152' => $item->getMycompanyId(),                       #   MY COMPANY
                                                                        '153' => '',                       #   R/S
                                                                        '155' => $crm,                     #   DEAL/LEAD
                                                                        '156' => (int)$item->getId(),                       #   ID INVOICE
                                                                        '157' => $task_id,                                     #   TASK ID
                                                                        '158' => '<a href="/crm/type/128/details/' . (int)$item->getId() . '/">' . $invoice['TITLE'] . '</a>',
                                                                        '159' => $task_link,                                    #   TASK LINK
                                                                        '160' => 1,
                                                                        '161' => 0,                                     #   ZP
                                                                        '162' => date('d.m.Y'),                         #   DATE EDIT
                                                                        '163' => (int)$item->getUpdatedBy(),                       #   OPER
                                                                        '164' => (int)$item->getAssignedById(),                       #   OTVETSTV
                                                                        '165' => 'Оплата счёта №'.$item->getId(),                        #   COMMENT
                                                                        '421' => ($resB['VALUE'] + (abs($item->getOpportunity()) * -1)),
                                                                        '422' => ($resA['VALUE'] + (abs($item->getOpportunity()) * -1))
                                                                    )
                                                                )
                                                            );
                                                            if((int)$idPay <= 0)
                                                                $item->setStageId($invoice['STAGE_ID']);
                                                        }
                                                        
                                                        if (\Bitrix\Main\Loader::includeModule('im') && $idPay > 0)
                                                        {
                                                            $sql = $connection->query("SELECT `ID` FROM `b_im_chat` WHERE `ENTITY_ID` = 'DYNAMIC_128|" . (int)$item->getId() . "'");
                                                            $iChat = $sql->fetch();
                                                            $chat = new \CIMChat;
                                                            $chat->AddMessage(array(
                                                                "TO_CHAT_ID" => $iChat['ID'],
                                                                "FROM_USER_ID" => 0,
                                                                "SYSTEM" => 'Y',
                                                                "MESSAGE" => '[b]Счёт оплачен[/b]'
                                                            ));
                                                        }
                                                    }
                                                }
                                                elseif($item->getStageId() == 'DT128_4:NEW' && $invoice['STAGE_ID'] == 'DT128_4:FAIL')
                                                {
                                                    $tmp = explode('.', $item->getUfCrm_3_1693387031126());
                                                    $newDate = (!empty($tmp[2])) ? $tmp[2].'-'.$tmp[1].'-'.$tmp[0] : date('Y-m-d', strtotime($item->getUfCrm_3_1693387031126()));
                                                    if($newDate < date('Y-m-d', strtotime('+1 day')))
                                                    {
                                                        if(($newDate == date('Y-m-d') && date('H') >= 15) || $newDate < date('Y-m-d'))
                                                        {
                                                            $day  = true;
                                                            $date = date('Y-m-d');
                                                            $holyday = array('2024-03-08', '2024-04-29', '2024-04-30', '2024-05-01', '2024-05-09', '2024-05-10', '2024-06-12', '2024-11-04', '2024-12-30', '2024-12-31', '2025-01-01', '2025-01-02', '2025-01-03', '2025-01-06', '2025-01-07');
                                                            while($day)
                                                            {
                                                                if(date('w', strtotime($date.' +1 day')) == 6)
                                                                    $date = date('Y-m-d', strtotime($date.' +3 day'));
                                                                elseif(date('w', strtotime($date.' +1 day')) == 0)
                                                                    $date = date('Y-m-d', strtotime($date.' +2 day'));
                                                                else
                                                                    $date = date('Y-m-d', strtotime($date.' +1 day'));

                                                                if(!in_array(date('Y-m-d', strtotime($date)), $holyday))
                                                                    $day = false;
                                                                else
                                                                    $date = date('Y-m-d', strtotime($date.' +1 day'));
                                                            }

                                                            $item->setUfCrm_3_1693387031126(date('d.m.Y', strtotime($date)));
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    if($item->getStageId() == 'DT128_3:2' && date('Y-m-d', strtotime($item->getClosedate())) >= date('Y-m-d'))
                                    {
                                        $item->setStageId('DT128_3:1');
                                    }

                                    if(($item->getStageId() == 'DT128_3:1' || $item->getStageId() == 'DT128_3:2') && $item->getUfToPay() > 0)
                                    {
                                        $item->setStageId('DT128_3:NEW');
                                    }

                                    return new Result();
                                }
                            }
                        );
                    }

                    public function getDeleteOperation(Item $item, Context $context = null): Operation\Delete
                    {
                        $operation = parent::getDeleteOperation($item, $context);
                        return $operation;
                    }
                };
                return $factory;
            }

            if ($entityTypeId === 167)
            {
                $arStages = array();
                $arStages[0] = 'DT167_7:NEW';
                $arStages[1] = 'DT167_7:PREPARATION';
                $arStages[2] = 'DT167_7:1';
                $arStages[3] = 'DT167_7:CLIENT';
                $arStages[4] = 'DT167_7:UC_1G02EK';
                $arStages[5] = 'DT167_7:UC_3TCQCM';
                $arStages[6] = 'DT167_7:SUCCESS';
                $arStages[7] = 'DT167_7:FAIL';
                
                $type = $this->getTypeByEntityTypeId($entityTypeId);
                
                $factory = new class($type) extends Service\Factory\Dynamic {
                    public function getAddOperation(Item $item, Context $context = null): Operation\Add
                    {
                        $operation = parent::getAddOperation($item, $context);
                        return $operation;
                    }
                    
                    public function getUpdateOperation(Item $item, Context $context = null): Operation\Update
                    {
                        $operation = parent::getUpdateOperation($item, $context);
                        return $operation ->addAction(
                            Operation::ACTION_BEFORE_SAVE,
                            new class extends Operation\Action {
                                public function process(Item $item): Result
                                {
                                    $resTeam  = array();
                                    $resUsers = array();
                                    $cDB = Application::getConnection();
                                    
                                    $sql = $cDB->query("SELECT * FROM `b_crm_dynamic_items_167` WHERE `ID` = ".(int)$item->getId());
                                    $resItem = $sql->fetch();
                                    $resUsers[$resItem['ASSIGNED_BY_ID']] = $resItem['ASSIGNED_BY_ID'];

                                    
                                    $sql = $cDB->query("SELECT `USER_ID` FROM `b_crm_observer` WHERE `ENTITY_TYPE_ID` = 167 AND `ENTITY_ID` = ".(int)$item->getId());
                                    $tmpRes = $sql->fetchAll();
                                    if(!empty($tmpRes))
                                    {
                                        foreach($tmpRes as $v)
                                        {
                                            $resUsers[$v['USER_ID']] = $v['USER_ID'];
                                        }
                                    }
                                    
                                    if(!empty($resItem['UF_CRM_6_TEAM']))
                                    {
                                        $arTmp = unserialize($resItem['UF_CRM_6_TEAM']);
                                        if(!empty($arTmp))
                                        {
                                            foreach($arTmp as $v2)
                                            {
                                                $resTeam[] = $v2;
                                            }
                                        }
                                    }
                                    
                                    $resTeam[] = $resItem['ASSIGNED_BY_ID'];
                                    
                                    $arStages = array();
                                    $arStages[0] = 'DT167_7:NEW';
                                    $arStages[1] = 'DT167_7:PREPARATION';
                                    $arStages[2] = 'DT167_7:1';
                                    $arStages[3] = 'DT167_7:CLIENT';
                                    $arStages[4] = 'DT167_7:UC_1G02EK';
                                    $arStages[5] = 'DT167_7:UC_3TCQCM';
                                    $arStages[6] = 'DT167_7:SUCCESS';
                                    $arStages[7] = 'DT167_7:FAIL';
                                    
                                    #   Статус - только вперёд
                                    $key1 = array_search($resItem['STAGE_ID'], $arStages);
                                    $key2 = array_search($item->getStageId(), $arStages);
                                    
                                    if($item->getStageId() == 'DT167_7:UC_1G02EK' && $resItem['STAGE_ID'] == 'DT167_7:SUCCESS')
                                    {
                                        $sql = $cDB->query("SELECT `GROUP_ID` FROM `b_user_group` WHERE `GROUP_ID` = 25 AND `USER_ID` = ".(int)$item->getUpdatedBy());
                                        $result = $sql->fetch();
                                        if(empty($result))
                                        {
                                            $item->setStageId($resItem['STAGE_ID']);

                                            #   Уведомление
                                            $users = array();
                                            $sql = $cDB->query("SELECT CONCAT(`u`.`LAST_NAME`, ' ', LEFT(`u`.`NAME`, 1),'.') AS `user`
                                                                    FROM `b_user_group` AS `g`
                                                                    LEFT JOIN `b_user` AS `u` ON `u`.`ID` = `g`.`USER_ID`
                                                                    WHERE `g`.`GROUP_ID` = 25");
                                            $result = $sql->fetchAll();
                                            if(!empty($result))
                                            {
                                                foreach($result as $val)
                                                {
                                                    $users[] = $val['user'];
                                                }

                                                $im = new \CIMMessenger;
                                                $im->Add(array(
                                                    'TO_USER_ID' => $item->getUpdatedBy(),
                                                    'FROM_USER_ID' => 487,
                                                    'MESSAGE_TYPE' => 'S',
                                                    'NOTIFY_MODULE' => 'im',
                                                    'NOTIFY_MESSAGE' => 'Данное действие могут выполнить только сотрудники, указанные в группе [b]Действия с первичными документами [/b], а именно: [b]'.implode(', ', $users).'[/b]'
                                                ));
                                            }
                                            else
                                            {
                                                $im = new \CIMMessenger;
                                                $im->Add(array(
                                                    'TO_USER_ID' => $item->getUpdatedBy(),
                                                    'FROM_USER_ID' => 487,
                                                    'MESSAGE_TYPE' => 'S',
                                                    'NOTIFY_MODULE' => 'im',
                                                    'NOTIFY_MESSAGE' => 'Данное действие могут выполнить только сотрудники, указанные в группе [b]Действия с первичными документами[/b].'
                                                ));
                                            }
                                        }
                                    }
                                    elseif($key1 != $key2 && ($key1 +1) != $key2 && $item->getStageId() != 'DT167_7:FAIL')
                                    {
                                        $item->setStageId($resItem['STAGE_ID']);
                                    }
                                    else
                                    {
                                        #   Командировка
                                        if($resItem['STAGE_ID'] != 'DT167_7:PREPARATION' && $item->getStageId() == 'DT167_7:PREPARATION')
                                        {
                                            /*
                                            if(empty($resTeam))
                                            {
                                                $item->setStageId($resItem['STAGE_ID']);
                                                $im = new \CIMMessenger;
                                                $im->Add(array(
                                                    'TO_USER_ID' => $item->getUpdatedBy(),
                                                    'FROM_USER_ID' => 487,
                                                    'MESSAGE_TYPE' => 'S',
                                                    'NOTIFY_MODULE' => 'im',
                                                    'NOTIFY_MESSAGE' => 'Не заполнено поле "Команда"'
                                                ));
                                            }
                                            else
                                            {*/
                                                $dateStart = $item->getUfCrm_6Begindate();
                                                $dateStop  = $item->getUfCrm_6Closedate();
                                                
                                                #if(in_array($item->getUpdatedBy(), $resTeam) || in_array($item->getUpdatedBy(), $resUsers))
                                                #{
                                                    if(\Bitrix\Main\Loader::includeModule('iblock'))
                                                    {
                                                        $el = new \CIBlockElement;
                                                        
                                                        foreach($resTeam as $ids)
                                                        {
                                                            $el->Add(array(
                                                                'NAME' => $resItem['TITLE'],
                                                                'ACTIVE_FROM' => $dateStart,
                                                                'ACTIVE_TO'   => $dateStop,
                                                                'CREATED_BY' => (int)$ids,
                                                                'ACTIVE' => 'Y',
                                                                'IBLOCK_ID' => 1,
                                                                'CODE' => $item->getId().$ids,
                                                                'PROPERTY_VALUES' => array(
                                                                '1' => $ids,
                                                                '4' => 2,
                                                            )));
                                                        }
                                                    }
                                                #}
                                                #else
                                                #    $item->setStageId($resItem['STAGE_ID']);
                                            #}
                                        }
                                        
                                        #   Утверждение авансового отчёта
                                        if($resItem['STAGE_ID'] != 'DT167_7:CLIENT' && $item->getStageId() == 'DT167_7:CLIENT')
                                        {
                                            if(!in_array($item->getUpdatedBy(), $resTeam) && !in_array($item->getUpdatedBy(), $resUsers))
                                                $item->setStageId($resItem['STAGE_ID']);
                                        }
                                        
                                        #   Проверка авансового отчёта
                                        if($resItem['STAGE_ID'] != 'DT167_7:UC_1G02EK' && $item->getStageId() == 'DT167_7:UC_1G02EK')
                                        {
                                            $resDeal  = array();
                                            if((int)$item->getParentId_2() > 0)
                                            {
                                                $sql = $cDB->query("SELECT `CREATED_BY_ID`, `ASSIGNED_BY_ID` FROM `b_crm_deal` WHERE `ID` = ".(int)$item->getParentId_2());
                                                $resTmp = $sql->fetch();
                                                $resDeal[$resTmp['CREATED_BY_ID']]  = $resTmp['CREATED_BY_ID'];
                                                $resDeal[$resTmp['ASSIGNED_BY_ID']] = $resTmp['ASSIGNED_BY_ID'];

                                                $sql = $cDB->query("SELECT `USER_ID` FROM `b_crm_observer` WHERE `ENTITY_TYPE_ID` = 2 AND `ENTITY_ID` = ".(int)$item->getParentId_2());
                                                $resTmp2 = $sql->fetchAll();
                                            }
                                            elseif((int)$item->getParentId_1() > 0)
                                            {
                                                $sql = $cDB->query("SELECT `CREATED_BY_ID`, `ASSIGNED_BY_ID` FROM `b_crm_lead` WHERE `ID` = ".(int)$item->getParentId_1());
                                                $resTmp = $sql->fetch();
                                                $resDeal[$resTmp['CREATED_BY_ID']]  = $resTmp['CREATED_BY_ID'];
                                                $resDeal[$resTmp['ASSIGNED_BY_ID']] = $resTmp['ASSIGNED_BY_ID'];

                                                $sql = $cDB->query("SELECT `USER_ID` FROM `b_crm_observer` WHERE `ENTITY_TYPE_ID` = 1 AND `ENTITY_ID` = ".(int)$item->getParentId_1());
                                                $resTmp2 = $sql->fetchAll();
                                            }
                                            elseif((int)$item->getParentId_133() > 0)
                                            {
                                                $sql = $cDB->query("SELECT `CREATED_BY`, `ASSIGNED_BY_ID` FROM `b_crm_dynamic_items_133` WHERE `ID` = ".(int)$item->getParentId_133());
                                                $resTmp = $sql->fetch();
                                                $resDeal[$resTmp['CREATED_BY']]  = $resTmp['CREATED_BY'];
                                                $resDeal[$resTmp['ASSIGNED_BY_ID']] = $resTmp['ASSIGNED_BY_ID'];

                                                $sql = $cDB->query("SELECT `USER_ID` FROM `b_crm_observer` WHERE `ENTITY_TYPE_ID` = 133 AND `ENTITY_ID` = ".(int)$item->getParentId_133());
                                                $resTmp2 = $sql->fetchAll();
                                            }
                                            
                                            if(!empty($resTmp2))
                                            {
                                                foreach($resTmp2 as $v)
                                                {
                                                    $resDeal[$v['USER_ID']] = $v['USER_ID'];
                                                }
                                            }

                                            if(!in_array($item->getUpdatedBy(), $resDeal))
                                                $item->setStageId($resItem['STAGE_ID']);
                                        }
                                    }

                                    # Проверка права на перемещение в стадию 'DT167_7:UC_3TCQCM'
                                    if ($resItem['STAGE_ID'] != 'DT167_7:UC_3TCQCM' && $item->getStageId() == 'DT167_7:UC_3TCQCM') {
                                        $sql = $cDB->query("SELECT `GROUP_ID` FROM `b_user_group` WHERE `GROUP_ID` = 25 AND `USER_ID` = ".(int)$item->getUpdatedBy());
                                        $result = $sql->fetch();
                                        if (empty($result)) {
                                            $item->setStageId($resItem['STAGE_ID']);
                                            $im = new \CIMMessenger;
                                            $im->Add(array(
                                                'TO_USER_ID' => $item->getUpdatedBy(),
                                                'FROM_USER_ID' => 487,
                                                'MESSAGE_TYPE' => 'S',
                                                'NOTIFY_MODULE' => 'im',
                                                'NOTIFY_MESSAGE' => 'Данное действие могут выполнить только сотрудники, указанные в группе [b]Действия с первичными документами[/b].'
                                            ));
                                        }
                                    }
                                    
                                    #   Проверка на смену команды
                                    if($item->getStageId() != 'DT167_7:NEW')
                                    {
                                        $key = array_key_last($resTeam);
                                        if($key)
                                        {
                                            unset($resTeam[$key]);
                                        }
                                        
                                        $item->setAssignedById($resItem['ASSIGNED_BY_ID']);
                                        $item->setUfCrm_6Team($resTeam);
                                    }
                                    
                                    #   Изменение даты в отсутствиях
                                    $start2 = $item->getUfCrm_6Begindate();
                                    $stop2  = $item->getUfCrm_6Closedate();

                                    $isStart = $resItem['UF_CRM_6_BEGINDATE'];
                                    $isStop  = $resItem['UF_CRM_6_CLOSEDATE'];

                                    $tmpstart = explode('-', $isStart);
                                    if(isset($tmpstart[2]))
                                    {
                                        $isStart = $tmpstart[2].'.'.$tmpstart[1].'.'.$tmpstart[0];
                                    }

                                    $tmpstop = explode('-', $isStop);
                                    if(isset($tmpstop[2]))
                                    {
                                        $isStop = $tmpstop[2].'.'.$tmpstop[1].'.'.$tmpstop[0];
                                    }

                                    if($start2 != $isStart || $stop2 != $isStop)
                                    {
                                        if($item->getStageId() == 'DT167_7:NEW' || $item->getStageId() == 'DT167_7:PREPARATION')
                                        {
                                            if(\Bitrix\Main\Loader::includeModule('iblock'))
                                            {
                                                $el = new \CIBlockElement;
                                                foreach($resTeam as $ids)
                                                {
                                                    $timeList = $el->getList(array(), array('=CODE' => $item->getId().$ids, 'IBLOCK_ID' => 1));
                                                    $arTime = $timeList->fetch();
                                                    if(!empty($arTime))
                                                    {
                                                        $idPay = $el->Update($arTime['ID'], array(
                                                            'NAME' => $arTime['NAME'],
                                                            'ACTIVE_FROM' => $start2,
                                                            'ACTIVE_TO'   => $stop2,
                                                            'CREATED_BY' => (int)$ids,
                                                            'ACTIVE' => 'Y',
                                                            'IBLOCK_ID' => 1,
                                                            'CODE' => $item->getId().$ids,
                                                            'PROPERTY_VALUES' => array(
                                                                '1' => $ids,
                                                                '4' => 2,
                                                            )));
                                                    }
                                                }
                                            }
                                        }
                                        else
                                        {
                                            $item->setUfCrm_6Begindate($isStart);
                                            $item->setUfCrm_6Closedate($isStop);
                                        }
                                    }

                                    return new Result();
                                }
                            }
                        );
                    }
                    
                    public function getDeleteOperation(Item $item, Context $context = null): Operation\Delete
                    {
                        $operation = parent::getDeleteOperation($item, $context);
                        return $operation;
                    }
                };
                return $factory;
            }
            
            if ($entityTypeId === 133)
            {
                $type = $this->getTypeByEntityTypeId($entityTypeId);
                
                $factory = new class($type) extends Service\Factory\Dynamic 
                {
                    public function getAddOperation(Item $item, Context $context = null): Operation\Add
                    {
                        $operation = parent::getAddOperation($item, $context);
                        return $operation;
                    }
                    
                    public function getUpdateOperation(Item $item, Context $context = null): Operation\Update
                    {
                        $operation = parent::getUpdateOperation($item, $context);
                        return $operation ->addAction(
                            Operation::ACTION_BEFORE_SAVE,
                            new class extends Operation\Action {
                                public function process(Item $item): Result
                                {
                                    $cDB = Application::getConnection();
                                    $sql = $cDB->query("SELECT `ASSIGNED_BY_ID`, `STAGE_ID` FROM `b_crm_dynamic_items_133` WHERE `ID` = ".(int)$item->getId());
                                    $resItem = $sql->fetch();
                                    
                                    if($resItem['ASSIGNED_BY_ID'] != $item->getAssignedById())
                                    {
                                        $item->setAssignedById($resItem['ASSIGNED_BY_ID']);
                                    }
                                    
                                    if($resItem['STAGE_ID'] != $item->getStageId())
                                    {
                                        $sql = $cDB->query("SELECT `ROLE_ID` FROM `b_crm_role_relation` WHERE `ROLE_ID` = 11 AND `RELATION` = 'U".(int)$item->getUpdatedBy()."'");
                                        $iRole = $sql->fetch();
                                        if(empty($iRole) && $item->getUpdatedBy() != 3 && $item->getUpdatedBy() != 7)
                                        {
                                            $item->setStageId($resItem['STAGE_ID']);
                                            if (\Bitrix\Main\Loader::includeModule('im'))
                                            {
                                                $im = new \CIMMessenger;
                                                $im->Add(array(
                                                    'TO_USER_ID' => $item->getUpdatedBy(),
                                                    'FROM_USER_ID' => 1,
                                                    'MESSAGE_TYPE' => 'S',
                                                    'NOTIFY_MODULE' => 'im',
                                                    'NOTIFY_MESSAGE' => 'У вас нет прав для смены статуса МТР'
                                                ));
                                            }
                                        }
                                    }
                                    
                                    return new Result();
                                }
                            }
                        );
                    }
                
                    public function getDeleteOperation(Item $item, Context $context = null): Operation\Delete
                    {
                        $operation = parent::getDeleteOperation($item, $context);
                        return $operation;
                    }
                };
                return $factory;
            }

            if ($entityTypeId === 144)
            {
                $type = $this->getTypeByEntityTypeId($entityTypeId);
                    $factory = new class($type) extends Service\Factory\Dynamic {
                        public function getAddOperation(Item $item, Context $context = null): Operation\Add
                        {
                                $operation = parent::getAddOperation($item, $context);
        
                                return $operation ->addAction(
                                    Operation::ACTION_AFTER_SAVE,
                                    new class extends Operation\Action {
                                        public function process(Item $item): Result
                                        {
                                            $connection = Application::getConnection();
                                            if(!empty($_POST['data']['PARENT_ID_2']) && $_POST['data']['PARENT_ID_2'] > 0)
                                            {
                                                $recordset = $connection->query("SELECT `d`.`TITLE`, `d`.`COMPANY_ID`, `sd`.`UF_MYCOMPANY_ID` 
                                                                                FROM `b_crm_deal` AS `d`
                                                                                LEFT JOIN `b_uts_crm_deal` AS `sd` ON `sd`.`VALUE_ID` = `d`.`ID`
                                                                                WHERE `d`.`ID` = ".(int)$_POST['data']['PARENT_ID_2']);
                                                $record = $recordset->fetch();
                                            //  $item->setMycompanyId($record['UF_MYCOMPANY_ID']);
                                            //  $item->setCompanyId($record['COMPANY_ID']);
        
                                                $connection->query("UPDATE `b_crm_dynamic_items_144` SET `TITLE` = 'АВР ".(int)$item->getId()." (".$record['TITLE'].")' WHERE `ID` = ".(int)$item->getId());
                                            }
        
                                            return new Result();
                                        }
                                    }
                                );
                        }
        
                       /* public function getUpdateOperation(Item $item, Context $context = null): Operation\Update
                        {
                            $operation = parent::getUpdateOperation($item, $context);
                            return $operation;
                        }
        
                        public function getDeleteOperation(Item $item, Context $context = null): Operation\Delete
                        {
                            $operation = parent::getDeleteOperation($item, $context);
                            return $operation;
                        }*/
        
                    };
        
                    return $factory;
            }

            
            
            return parent::getFactory($entityTypeId);
        }
    };
    DI\ServiceLocator::getInstance()->addInstance('crm.service.container', $container);
}
                                   