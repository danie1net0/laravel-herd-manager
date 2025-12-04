<?php

declare(strict_types=1);

return [
    'herd_command' => 'PATH={{HERD_BIN_PATH}}:"$PATH" {{HERD_EXECUTABLE}} {{COMMAND}} 2>&1',
    'restart_nginx' => 'nohup sh -c \'PATH="{{HERD_BIN_PATH}}:$PATH" herd restart nginx\' > /dev/null 2>&1 &',
    'get_local_ip' => 'ipconfig getifaddr en0 2>/dev/null',
    'nginx_include' => '    include {{CONFIG_FILE}};',
];
