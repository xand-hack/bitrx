<?php
CModule::IncludeModule("iblock");
CModule::IncludeModule("crm");

$idDeal = {{ID}};

$currentDateTime = new DateTime();
$currentDateTime->modify('+10 days');
$newDateTimeFormatted = $currentDateTime->format('Y-m-d H:i:s');

$connection = Bitrix\Main\Application::getConnection();
$sqlCount = "SELECT UF_PREV_STAGE_2 FROM b_uts_crm_deal WHERE ID = {$idDeal} AND (STAGE_ID LIKE '%WIN%' OR STAGE_ID LIKE '%LOSE%') AND STAGE_ID NOT LIKE '%NEW%'";
$resultCount = $connection->query($sqlCount);
$countRecord = $resultCount->fetch();

if ($countRecord) {
    $sqlUpdate = "UPDATE b_crm_deal SET CLOSEDATE = '{$newDateTimeFormatted}' WHERE ID = {$idDeal}";
    $connection->query($sqlUpdate);
}

//_______________________________________

		$taskID = 52181; 
		
		
        CModule::IncludeModule("tasks");
        $connection = Bitrix\Main\Application::getConnection();
        $sqlCount = "SELECT * FROM b_tasks WHERE ID = {$taskID}";
        $resultCount = $connection->query($sqlCount);
        $countRecord = $resultCount->fetch();

        if ($countRecord){

        }
        function AddM(){
            if(!$IDchat){
                $idAdmin = 1;
                $pic = $_SERVER['DOCUMENT_ROOT'] . '/test.jpg'; 
                $avatarId = \CFile::SaveFile(\CFile::MakeFileArray($pic), 'im'); 
                $chat = new \CIMChat; 
                $chat->Add(array( 
                    'TITLE' => 'Чат сделки'.$taskID, 
                    'DESCRIPTION' => 'Описание тестового чата', 
                    'COLOR' => 'RED',//цвет 
                    'TYPE' => IM_MESSAGE_OPEN,//тип чата 
                    'AUTHOR_ID' => '799',//владелец чата 
                    'AVATAR_ID' => $avatarId,//аватарка чата 
                    'USERS' => false,
                )); 
            }
            $chat = new \CIMChat(0); 
            $chat->AddUser($IDchat, $idAdmin, $hideHistory = false, $skipMessage = false);

            \CIMChat::AddMessage(array( 
                'FROM_USER_ID' => $idAdmin,  //id автора
                'TO_CHAT_ID' => $IDchat,  //id чата
                'MESSAGE' => 'Пишу в чат сделки'
            ));
            \CIMChat::AddSystemMessage(array( 
                'FROM_USER_ID' => $idAdmin,  //id автора
                'CHAT_ID' => $IDchat,  //id чата
                'MESSAGE' => 'системное уведомление' 
            )); 
        }




//___________________________







CModule::IncludeModule("blog");
CModule::IncludeModule("socialnetwork");
CModule::IncludeModule("im");
CModule::IncludeModule("iblock");

function setStrike($itemas, $createdBy, $idBlog) {
    $whom = array($itemas);
    $text = "Выговор получает ";

    if(is_countable($whom) && count($whom) > 1) {
        $text = "Выговор получают ";
    }

    for($i=0; $i<count($whom); $i++) {
        $rsUser = CUser::GetByID($whom[$i]);
        $arUser = $rsUser->Fetch();
    
        $userStr.= "<a href='/company/personal/user/".$arUser["ID"]."/'>".$arUser["NAME"]." ".$arUser["LAST_NAME"]."</a>";

        if(($i+1)<count($whom)) {
            $userStr.=", ";
        }
    }

    $text.=$userStr;
    $text.=" за наличие 3-х и более прогулов в теч месяца ";

    $arFields= array(
        "TITLE" => "Благодарность Выговор",
        "DETAIL_TEXT" => $text,
        "DATE_PUBLISH" => date('d.m.Y H:i:s'),
        "PUBLISH_STATUS" => "P",
        "ENABLE_TRACKBACK" => 'Y',
        "ENABLE_COMMENTS" => 'Y',
        "=DATE_CREATE" => "now()",
        "AUTHOR_ID" => $createdBy,
        "BLOG_ID" => $idBlog,
        "PATH" => "/company/personal/user/1/blog/#post_id#/",
        "MICRO" => 'Y',
        "HAS_SOCNET_ALL" => 'Y',
        "PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
        'HAS_TAGS' => 'N',
        "HAS_IMAGES" => 'N',
        "PERMS_POST" => Array(),
        "PERMS_COMMENT" => Array (),
        "UF_GRATITUDE" => 1400,
        "SOCNET_RIGHTS" => Array
        (
        "UA", "G2"
        ),
    );

    $newID= CBlogPost::Add($arFields);

    if(IntVal($newID)>0){

        $arFields["ID"] = $newID;

        $arParamsNotify = Array(
            "bSoNet" => true,
            "UserID" => $createdBy,
            "user_id" => $createdBy,
        );

        CBlogPost::Notify($arFields, array(), $arParamsNotify);

        $grat_id = 82;
        $honour_iblock_id = 2;

        $el = new CIBlockElement;
        $new_grat_element_id = $el->Add(
            array(
                "IBLOCK_ID" => $honour_iblock_id,
                "DATE_ACTIVE_FROM" => date('d.m.Y H:i:s'),
                "NAME" => "Благодарность Выговор"
            ),
            false,
            false
        );

        if ($new_grat_element_id > 0)
        {
            CIBlockElement::SetPropertyValuesEx(
                $new_grat_element_id,
                $honour_iblock_id,
                array(
                    "USERS" => $whom,
                    "GRATITUDE" => array("VALUE" => $grat_id)
                )
            );

            CBlogPost::Update($newID, array(
                "DETAIL_TEXT_TYPE" => "text",
                "UF_GRATITUDE" => $new_grat_element_id
            ));

            $res = \Bitrix\Socialnetwork\LogTable::getList(array(
                'filter' => array(
                    'EVENT_ID' => \Bitrix\Blog\Integration\Socialnetwork\Log::EVENT_ID_POST,
                    'SOURCE_ID' => $newID
                ),
                'select' => array('ID')
            ));

            if ($logFields = $res->fetch())
            {
                $logId = $logFields['ID'];

                $eventId = \Bitrix\Blog\Integration\Socialnetwork\Log::EVENT_ID_POST_GRAT;

                $logFields = array(
                    "EVENT_ID" => $eventId
                );

                if ($post = \Bitrix\Blog\Item\Post::getById($newID))
                {
                    $logFields["TAG"] = $post->getTags();
                }

                CSocNetLog::Update(intval($logId), $logFields);
                

                $arMessageFields = array(
                    "NOTIFY_TYPE" => IM_NOTIFY_FROM,
                    "FROM_USER_ID" => $createdBy,
                    "NOTIFY_MESSAGE" => "Выговор получает ".$userStr." за наличие 3-х и более прогулов в теч месяца ",
                    "NOTIFY_MESSAGE_OUT" => "Выговор получает ".$userStr." за наличие 3-х и более прогулов в теч месяца ",
                    "NOTIFY_MODULE" => "bizproc",
                    "NOTIFY_EVENT" => "activity"
                );

                for($i=0; $i<count($whom); $i++) {
                    $arMessageFields["TO_USER_ID"] = $whom[$i];
                    CIMNotify::Add($arMessageFields);
                }

             $arMessageFields["TO_USER_ID"] = $createdBy;

             CIMNotify::Add($arMessageFields);
            }
        }
    }
}
$createdBy = 3;
$arBlog = CBlog::GetByOwnerID($createdBy);
$idBlog = $arBlog["ID"];
$NAME = 'Прогул';

$date_today = date("d.m.Y");
$date_one_month_ago = date("d.m.Y", strtotime("-1 month"));
$today = new DateTime();
$tomorrow = clone $today;
$tomorrow->modify('+1 day');

$filter = array(
    '>=DATE_ACTIVE_TO' => $date_one_month_ago,
    '<=DATE_ACTIVE_TO' => $date_today
);

$dbRes = CIBlockElement::GetList(array(), $filter, array("PROPERTY_1", "CNT", "PROPERTY_4"));


$people = array();
while ($arRes = $dbRes->Fetch()) {
    $connection = Bitrix\Main\Application::getConnection();
    $sql = "SELECT * FROM b_user WHERE ID = {$arRes['PROPERTY_1_VALUE']}";
    $recordset = $connection->query($sql);
    $record = $recordset->fetch();
    if ($arRes["CNT"] >= 3 && $record['ACTIVE'] == "Y" && $arRes["PROPERTY_4_VALUE"] == 'прогул') {
        $people[] = $arRes["PROPERTY_1_VALUE"];
    }
}

if ($today->format('m') !== $tomorrow->format('m')) {
    foreach($people as $IndexMas => $itemas){
        setStrike($itemas, $createdBy, $idBlog);
    }
}










//_______________________________________________

$connection = Bitrix\Main\Application::getConnection(); 

$sql = "SELECT * FROM ((SELECT deal.ID AS id1, deal.ASSIGNED_BY_ID as assigned, deal.TITLE as title,  SUM(avr.OPPORTUNITY) AS OpAvr
FROM b_crm_deal AS deal 
INNER JOIN b_crm_entity_relation AS relat ON deal.ID = relat.SRC_ENTITY_ID
INNER JOIN b_crm_dynamic_items_144 AS avr ON relat.DST_ENTITY_ID = avr.ID
WHERE deal.STAGE_ID = 'NEW' AND relat.DST_ENTITY_TYPE_ID = 144 AND relat.SRC_ENTITY_TYPE_ID = 2 GROUP BY id1  ORDER BY id1 ASC) AS TMP_1

LEFT JOIN

(SELECT deal.ID AS id2, SUM(inv.OPPORTUNITY) AS OpInv
FROM b_crm_deal AS deal 
INNER JOIN b_crm_entity_relation AS relat ON deal.ID = relat.SRC_ENTITY_ID
INNER JOIN b_crm_dynamic_items_159 AS inv ON relat.DST_ENTITY_ID = inv.ID
WHERE deal.STAGE_ID = 'NEW' AND relat.DST_ENTITY_TYPE_ID = 159 AND relat.SRC_ENTITY_TYPE_ID = 2 GROUP BY id2  ORDER BY id2 ASC) AS TMP_2

ON TMP_1.id1 = TMP_2.id2) WHERE TMP_1.OpAvr > TMP_2.OpInv OR TMP_2.id2 IS NULL";

$recordset = $connection->query($sql);

while ($record = $recordset->fetch()) {
    $rsUser = CUser::GetByID($record["assigned"]);
    $arUser = $rsUser->Fetch();

    $mess = "[USER=".$record["assigned"]."]".$arUser["NAME"]." ".$arUser["LAST_NAME"]."[/USER]".", по данной сделке проходит АВР на сумму ".$record["OpAvr"]." рублей, а счетов на сумму ".$record["OpInv"]." рублей. Вам нужно выставить необходимые счёта.";

    $title = $record["title"];

    $id = $record["id1"];
    $assigned = $record["assigned"];

    $sql3 = "SELECT * FROM  b_im_chat WHERE ENTITY_ID = 'DEAL|".$id."'";

    $recordset3 = $connection->query($sql3);

    try {

        if ($record3 = $recordset3->fetch()) {
            
			 $chatId = $record3["ID"];

			     $chat = new \CIMChat(); 

       //     \CCrmDeal::AddObserverIDs($id, array(487)); 

			  $chat->AddUser($chatId, $assigned, $hideHistory = false, $skipMessage = false, $skipRecent = false); 
            $chat->AddUser($chatId, 487, $hideHistory = false, $skipMessage = false); 

            \CIMChat::AddMessage(array( 
                'FROM_USER_ID' => 487,  
                'TO_CHAT_ID' => $chatId,  
                'MESSAGE' => $mess
));
        } else {
			 $chat = new \CIMChat; 

            $chatId = $chat->Add(array( 
                'TITLE' => 'Сделка: '.$title, 
                'TYPE' => 'C',
                'AUTHOR_ID' => $assigned,
                'ENTITY_TYPE' => 'CRM',
                'ENTITY_ID' => 'DEAL|'.$id,
                'EXTRANET' => 'Y'
            ));

            $attach = new CIMMessageParamAttach(null, "#95c255");
            $attach->AddLink(Array(
                "NAME" => $name,
                "LINK" => "https://gkyw.ru/crm/deal/details/".$id."/"
            ));

            \CIMChat::AddMessage(array( 
                'FROM_USER_ID' => 487,  
                'TO_CHAT_ID' => $chatId,
                'SYSTEM' => 'Y',
                'MESSAGE' => 'Создан чат для обсуждения сделки',
                "ATTACH" => Array($attach)
            ));

      //      \CCrmDeal::AddObserverIDs($id, array(487)); 

            $chat->AddUser($chatId, $assigned, $hideHistory = false, $skipMessage = false, $skipRecent = false); 
            $chat->AddUser($chatId, 487, $hideHistory = false, $skipMessage = false, $skipRecent = false); 

            \CIMChat::AddMessage(array( 
                'FROM_USER_ID' => 487,  
                'TO_CHAT_ID' => $chatId,  
                'MESSAGE' => $mess
));
        }
    } catch (Exception $e) {
    }
}
//________________________________________________________________________________________


CModule::IncludeModule("im");
$connection = Bitrix\Main\Application::getConnection(); 
$idDeal = {{ID}};
$sumAVR = 0;
$sumScore = 0;

$sql1 = "SELECT * FROM b_crm_entity_relation WHERE DST_ENTITY_TYPE_ID = 144 AND SRC_ENTITY_ID = {$idDeal}"; // АВР
$sql2 = "SELECT * FROM b_crm_entity_relation WHERE DST_ENTITY_TYPE_ID = 159 AND SRC_ENTITY_ID = {$idDeal}"; // Счета

$recordset1 = $connection->query($sql1);
$recordset2 = $connection->query($sql2);

while($record1 = $recordset1->fetch()) {
    $sql3 = "SELECT * FROM b_crm_dynamic_items_144 WHERE ID = {$record1['DST_ENTITY_ID']} AND STAGE_ID NOT LIKE '%FAIL%'";
    $recordset3 = $connection->query($sql3);
    if ($record3 = $recordset3->fetch()) {
        $sumAVR += $record3["OPPORTUNITY"];
    }
}

while($record2 = $recordset2->fetch()) {
    $sql4 = "SELECT * FROM b_crm_dynamic_items_159 WHERE ID = {$record2['DST_ENTITY_ID']} AND STAGE_ID NOT LIKE '%FAIL%'"; 
    $recordset4 = $connection->query($sql4);
    if ($record4 = $recordset4->fetch()) {
        $sumScore += $record4["OPPORTUNITY"];
    }
}

if ($sumAVR > $sumScore) {
    $createdBy = "{{Ответственный}}";
    $sql5 = "SELECT * FROM b_im_chat WHERE ENTITY_ID = 'DEAL|{$idDeal}'";
    $recordset5 = $connection->query($sql5);
    $record5 = $recordset5->fetch();
    
    $chatId = $record5["ID"];
    $chat = new \CIMChat(0);

    $chat->AddUser($chatId, $createdBy, $hideHistory = false, $skipMessage = false);
    
    \CIMChat::AddMessage(array(
        'FROM_USER_ID' => $createdBy,
        'TO_CHAT_ID' => $chatId,
        'MESSAGE' => "В данной Сделке сумма по АВРам превышает сумму выставленных счетов. Вам необходимо создать счёт на эту разницу"
    ));
}
//___________________________________________________________________

use Bitrix\Crm\Service;
use \Bitrix\Main\Application;
CModule::IncludeModule("im");

$cDB = Application::getConnection();
$id = "{{ID}}";

$factory = Service\Container::getInstance()->getFactory(167);
$item  = $factory->getItem($id);
$stage = $item->getStageId();
$prevStage = $item->getPreviousStageId();

if($stage == 'DT167_7:SUCCESS')
{
    $user = $item->getUpdatedBy();

    $sql = $cDB->query("SELECT `GROUP_ID` FROM `b_user_group` WHERE `GROUP_ID` = 25 AND `USER_ID` = ".(int)$user);
    $result = $sql->fetch();
    if(empty($result))
    {
        $item->setStageId($prevStage);
        $item->save();

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

            $message = array("NOTIFY_TYPE" => IM_NOTIFY_FROM,
                "FROM_USER_ID" => 487,
                "TO_USER_ID" => $user,
                "NOTIFY_MESSAGE" => 'Данное действие могут выполнить только сотрудники, указанные в группе [b]Действия с первичными документами [/b], а именно: [b]'.implode(', ', $users).'[/b]',
                "NOTIFY_MESSAGE_OUT" => 'Данное действие могут выполнить только сотрудники, указанные в группе [b]Действия с первичными документами[/b], а именно: [b]'.implode(', ', $users).'[/b]',
                "NOTIFY_MODULE" => "bizproc",
                "NOTIFY_EVENT" => "activity"
            );
            CIMNotify::Add($message);
        }
        else
        {
            $message = array("NOTIFY_TYPE" => IM_NOTIFY_FROM,
                "FROM_USER_ID" => 487,
                "TO_USER_ID" => $user,
                "NOTIFY_MESSAGE" => 'Данное действие могут выполнить только сотрудники, указанные в группе [b]Действия с первичными документами[/b].',
                "NOTIFY_MESSAGE_OUT" => 'Данное действие могут выполнить только сотрудники, указанные в группе [b]Действия с первичными документами[/b].',
                "NOTIFY_MODULE" => "bizproc",
                "NOTIFY_EVENT" => "activity"
            );
            CIMNotify::Add($message);
        }
    }
}

