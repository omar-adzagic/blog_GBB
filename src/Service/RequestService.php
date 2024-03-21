<?php

namespace App\Service;

class RequestService
{
    public static function getIdsMapFromArray(array $idsSelectResult): array
    {
        $resultMap = [];
        foreach ($idsSelectResult as $result) {
            $resultMap[$result['id']] = true;
        }

        return $resultMap;
    }
}
