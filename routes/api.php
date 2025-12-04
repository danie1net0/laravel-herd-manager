<?php

declare(strict_types=1);

use FastRoute\RouteCollector;

return function (RouteCollector $r): void {
    $r->addRoute('GET', '/api/sites', 'SiteController@list');
    $r->addRoute('GET', '/api/sites/ip', 'SiteController@getIp');
    $r->addRoute('GET', '/api/sites/check-port', 'SiteController@checkPort');
    $r->addRoute('GET', '/api/sites/debug', 'SiteController@debug');
    $r->addRoute('POST', '/api/sites/apply', 'SiteController@apply');
    $r->addRoute('POST', '/api/sites/status', 'SiteController@status');
    $r->addRoute('POST', '/api/sites/test-apply', 'SiteController@testApply');

    $r->addRoute('GET', '/api/proxies', 'ProxyController@list');
    $r->addRoute('POST', '/api/proxies', 'ProxyController@create');
    $r->addRoute('DELETE', '/api/proxies/{name}', 'ProxyController@delete');
};
