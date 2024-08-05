<?

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

$filter = array(
    '>=DATE_ACTIVE_TO' => $date_one_month_ago,
    '<=DATE_ACTIVE_TO' => $date_today
);

$dbRes = CIBlockElement::GetList(array(), $filter, array("PROPERTY_1", "CNT", "PROPERTY_4"));

$people = array();
while ($arRes = $dbRes->Fetch()) {
    if(isset($arRes['PROPERTY_1_VALUE'])){
        $connection = Bitrix\Main\Application::getConnection();
        $sql = "SELECT * FROM b_user WHERE ID = {$arRes['PROPERTY_1_VALUE']}";
        $sql2 = "SELECT * FROM b_uts_user WHERE VALUE_ID = {$arRes['PROPERTY_1_VALUE']}";
        $recordset = $connection->query($sql);
        $recordset2 = $connection->query($sql2);
        $record = $recordset->fetch();
        $record2 = $recordset2->fetch();
        if ($arRes["CNT"] >= 3 && $record['ACTIVE'] == "Y" && $arRes["PROPERTY_4_VALUE"] == 'прогул' && isset($record2['UF_DEPARTMENT'])&& !empty(unserialize($record2['UF_DEPARTMENT']))) {
            $people[] = $arRes["PROPERTY_1_VALUE"];
        }
    }
}

foreach($people as $IndexMas => $itemas){
    setStrike($itemas, $createdBy, $idBlog);
}


