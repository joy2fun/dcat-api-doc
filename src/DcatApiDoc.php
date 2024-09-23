<?php

namespace Joy2fun\DcatApiDoc;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\RouteInfo;

class DcatApiDoc extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo)
    {

        $routeName = $routeInfo->route->getName();
        $routeNameStr = str($routeName);

        $summary = match (true) {
            $routeNameStr->contains('.index') => '列表',
            $routeNameStr->contains('.show') => '详情',
            $routeNameStr->contains('.store') => '新增',
            $routeNameStr->contains('.update') => '更新',
            $routeNameStr->contains('.destroy') => '删除',
            true => null,
        };

        if ($summary) {
            $operation->summary($summary);
        }

    }

}
