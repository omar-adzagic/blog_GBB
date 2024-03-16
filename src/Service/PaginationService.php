<?php

namespace App\Service;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginationService
{
    private $paginator;
    private $requestStack;

    public function __construct(PaginatorInterface $paginator, RequestStack $requestStack)
    {
        $this->paginator = $paginator;
        $this->requestStack = $requestStack;
    }

    public function paginate($queryBuilder, int $limit = 10)
    {
        $request = $this->requestStack->getCurrentRequest();
        $page = $request->query->getInt('page', 1);

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            $limit
        );
    }
}
