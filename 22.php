<?
AddEventHandler("iblock", "OnBeforeIBlockElementDelete", "MyIblockElementDelete");

function MyIblockElementDelete($ID)
{
    $res = CIBlockElement::GetByID($ID);
    if ($arElement = $res->GetNext()) {
        if ($arElement['IBLOCK_ID'] == 2 && $arElement["NAME"] == "Замечание") {
            $documentId = CBPVirtualDocument::CreateDocument(
                0,
                array(
                    "IBLOCK_ID" => 128,
                    "NAME" => "Удаление замечания",
                    "CREATED_BY" => "user_".$GLOBALS["USER"]->GetID(),
                    "PROPERTY_ID_ELEM" => $arElement['ID'],
                )
            );

            $arErrorsTmp = array();
            $wfId = CBPDocument::StartWorkflow(
                351,
                array("bizproc", "CBPVirtualDocument", $documentId),
                array_merge(array(), array("TargetUser" => "user_".intval($GLOBALS["USER"]->GetID()))),
                $arErrorsTmp
            );
        }
    }
}
