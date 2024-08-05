<?
CModule::IncludeModule('iblock');
CModule::IncludeModule("blog");

$id = "{{ID_ELEM}}";

$arFilter = Array("ID" => $id);
$res = CIBlockElement::GetList(Array(), $arFilter, false, false, Array("PROPERTY_USERS"));

$arFields = $res->fetch();

if ($arFields) {

    $substr1 = "https://gkyw.ru/company/personal/user/";
    $substr2 = "замечание является эквивалентом 3-м предупреждениям";

        $connection = Bitrix\Main\Application::getConnection();

        foreach ($arFields as $userId) {
            $laststring = $substr1 . $userId;
            $sql = "SELECT * 
                    FROM b_blog_post
                    WHERE b_blog_post.DETAIL_TEXT LIKE '%$laststring%'
                    AND b_blog_post.DETAIL_TEXT LIKE '%$substr2%'
                    AND b_blog_post.TITLE = 'Замечание'
                    ORDER BY b_blog_post.DATE_CREATE DESC 
                    LIMIT 1 OFFSET 1";

            $recordset = $connection->query($sql);

            if ($record = $recordset->fetch()) {
                $previousStrikeDate = $record["DATE_CREATE"];
                $formattedDate = date('Y-m-d H:i:s', strtotime($previousStrikeDate));
                $updateSql = "UPDATE b_uts_user SET UF_LAST_STRIKE_DATE = '$formattedDate' WHERE VALUE_ID = $userId";
                $connection->query($updateSql);
            } else {
                $updateSql = "UPDATE b_uts_user SET UF_LAST_STRIKE_DATE = NULL WHERE VALUE_ID = $userId";
                $connection->query($updateSql);
            }
        }
    }

