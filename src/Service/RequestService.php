<?php

namespace App\Service;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
