<?php

declare(strict_types=1);

use Phoenix\Database\Adapter\AdapterFactory;
use Phoenix\Database\Adapter\PgsqlAdapterFactory;

// loaded by composer (autoload.files): makes the "pgsql" adapter available
AdapterFactory::registerFactory(new PgsqlAdapterFactory());
