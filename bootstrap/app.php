<?php

declare(strict_types=1);

use App\Application;
use App\Support\Env;

require_once base_path('vendor/autoload.php');
Env::load(base_path('.env'));
date_default_timezone_set((string) env('APP_TIMEZONE', 'UTC'));
return new Application(base_path());
