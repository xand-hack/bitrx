<?php
CModule::IncludeModule("im");
$connection = Bitrix\Main\Application::getConnection();
$sql0 = "SELECT * FROM b_crm_deal";
$recordset0 = $connection->query($sql0);

while ($record0 = $recordset0->fetch()) {
    $idDeal = $record0['ID'];
    $createdBy = $record0['CREATED_BY_ID'];
    $title = $record0['title'];

    //по АВР
    $sql1 = "
        SELECT SUM(b_crm_dynamic_items_144.OPPORTUNITY) AS SUM_AVR
        FROM b_crm_entity_relation
        JOIN b_crm_dynamic_items_144 ON b_crm_entity_relation.DST_ENTITY_ID = b_crm_dynamic_items_144.ID
        WHERE b_crm_entity_relation.DST_ENTITY_TYPE_ID = 144
        AND b_crm_entity_relation.SRC_ENTITY_ID = {$idDeal}
        AND b_crm_dynamic_items_144.STAGE_ID NOT LIKE '%FAIL%'
    ";
    $recordset1 = $connection->query($sql1);
    $sumAVR = $recordset1->fetch()["SUM_AVR"] ?? 0;

    //по счетам
    $sql2 = "
        SELECT SUM(b_crm_dynamic_items_159.OPPORTUNITY) AS SUM_SCORE
        FROM b_crm_entity_relation
        JOIN b_crm_dynamic_items_159 ON b_crm_entity_relation.DST_ENTITY_ID = b_crm_dynamic_items_159.ID
        WHERE b_crm_entity_relation.DST_ENTITY_TYPE_ID = 159
        AND b_crm_entity_relation.SRC_ENTITY_ID = {$idDeal}
        AND b_crm_dynamic_items_159.STAGE_ID NOT LIKE '%FAIL%'
    ";
    $recordset2 = $connection->query($sql2);
    $sumScore = $recordset2->fetch()["SUM_SCORE"] ?? 0;

    if ($sumAVR > $sumScore) {
        $sql3 = "SELECT * FROM b_im_chat WHERE ENTITY_ID = 'DEAL|{$idDeal}'";
        $recordset3 = $connection->query($sql3);
        $record3 = $recordset3->fetch();
        
        if (!$record3) {
            $chat = new \CIMChat; 
            $chatId = $chat->Add(array( 
                'TITLE' => 'Сделка: '.$title, 
                'TYPE' => 'C',
                'AUTHOR_ID' => $createdBy,
                'ENTITY_TYPE' => 'CRM',
                'ENTITY_ID' => 'DEAL|'.$idDeal,
                'EXTRANET' => 'Y'
            ));

            $attach = new CIMMessageParamAttach(null, "#95c255");
            $attach->AddLink(array(
                "NAME" => $name,
                "LINK" => "https://gkyw.ru/crm/deal/details/".$idDeal."/"
            ));

            \CIMChat::AddMessage(array( 
                'FROM_USER_ID' => 487,  
                'TO_CHAT_ID' => $chatId,
                'SYSTEM' => 'Y',
                'MESSAGE' => 'Создан чат для обсуждения сделки',
                "ATTACH" => array($attach)
            ));
        } else {
            $chatId = $record3["ID"];
        }

        $chat = new \CIMChat(0);
        $chat->AddUser($chatId, $createdBy, $hideHistory = false, $skipMessage = false);
        $chat->AddUser($chatId, 487, $hideHistory = false, $skipMessage = false);

        \CIMChat::AddSystemMessage(array(
            'FROM_USER_ID' => $createdBy,
            'CHAT_ID' => $chatId,
            'MESSAGE' => "В данной Сделке сумма по АВРам превышает сумму выставленных счетов. Вам необходимо создать счёт на эту разницу"
        ));
    }
}




CModule::IncludeModule("im");
$connection = Bitrix\Main\Application::getConnection();

$sql = "
    SELECT d.ID, d.CREATED_BY_ID, d.TITLE, avr.SUM_AVR, score.SUM_SCORE
    FROM b_crm_deal d
    LEFT JOIN (
        SELECT er.SRC_ENTITY_ID, SUM(di144.OPPORTUNITY) AS SUM_AVR
        FROM b_crm_entity_relation er
        JOIN b_crm_dynamic_items_144 di144 ON er.DST_ENTITY_ID = di144.ID
        WHERE er.DST_ENTITY_TYPE_ID = 144
        AND di144.STAGE_ID NOT LIKE '%FAIL%'
        GROUP BY er.SRC_ENTITY_ID
    ) avr ON d.ID = avr.SRC_ENTITY_ID
    LEFT JOIN (
        SELECT er.SRC_ENTITY_ID, SUM(di159.OPPORTUNITY) AS SUM_SCORE
        FROM b_crm_entity_relation er
        JOIN b_crm_dynamic_items_159 di159 ON er.DST_ENTITY_ID = di159.ID
        WHERE er.DST_ENTITY_TYPE_ID = 159
        AND di159.STAGE_ID NOT LIKE '%FAIL%'
        GROUP BY er.SRC_ENTITY_ID
    ) score ON d.ID = score.SRC_ENTITY_ID
    WHERE d.STAGE_ID = 'NEW' AND avr.SUM_AVR > score.SUM_SCORE
";

$recordset = $connection->query($sql);

while ($record = $recordset->fetch()) {
    $idDeal = $record['ID'];
    $createdBy = $record['CREATED_BY_ID'];
    $title = $record['TITLE'];
    $sumAVR = $record['SUM_AVR'] ?? 0;
    $sumScore = $record['SUM_SCORE'] ?? 0;

    $sql3 = "SELECT * FROM b_im_chat WHERE ENTITY_ID = 'DEAL|{$idDeal}'";
    $recordset3 = $connection->query($sql3);
    $record3 = $recordset3->fetch();
    
    if (!$record3) {
        $chat = new \CIMChat; 
        $chatId = $chat->Add(array( 
            'TITLE' => 'Сделка: '.$title, 
            'TYPE' => 'C',
            'AUTHOR_ID' => $createdBy,
            'ENTITY_TYPE' => 'CRM',
            'ENTITY_ID' => 'DEAL|'.$idDeal,
            'EXTRANET' => 'Y'
        ));

        $attach = new CIMMessageParamAttach(null, "#95c255");
        $attach->AddLink(array(
            "NAME" => $title,
            "LINK" => "https://gkyw.ru/crm/deal/details/".$idDeal."/"
        ));

        \CIMChat::AddMessage(array( 
            'FROM_USER_ID' => 487,  
            'TO_CHAT_ID' => $chatId,
            'SYSTEM' => 'Y',
            'MESSAGE' => 'Создан чат для обсуждения сделки',
            "ATTACH" => array($attach)
        ));
    } else {
        $chatId = $record3["ID"];
    }

    $chat = new \CIMChat(0);
    $chat->AddUser($chatId, $createdBy, $hideHistory = false, $skipMessage = false);
    $chat->AddUser($chatId, 487, $hideHistory = false, $skipMessage = false);

    \CIMChat::AddSystemMessage(array(
        'FROM_USER_ID' => $createdBy,
        'CHAT_ID' => $chatId,
        'MESSAGE' => "В данной Сделке сумма по АВРам превышает сумму выставленных счетов. Вам необходимо создать счёт на эту разницу"
    ));
}
