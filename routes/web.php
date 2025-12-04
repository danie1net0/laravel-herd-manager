<?php

declare(strict_types=1);

use FastRoute\RouteCollector;

return function (RouteCollector $r): void {
    $r->addRoute('GET', '/', 'WebController@index');
};
