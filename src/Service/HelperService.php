<?php

namespace App\Service;

class HelperService
{
    public static function getIdsMapFromArray(array $idsSelectResult): array
    {
        $resultMap = [];
        foreach ($idsSelectResult as $result) {
            $resultMap[$result['id']] = true;
        }

        return $resultMap;
    }
    public static function getIdsFromDoctrine($array): array
    {
        return array_map(function ($item) {
            return $item->getId();
        }, $array);
    }
    public static function getIdsMapFromDoctrine(iterable $array): array
    {
        $resultMap = [];
        foreach ($array as $item) {
            $resultMap[$item->getId()] = true;
        }

        return $resultMap;
    }
}
