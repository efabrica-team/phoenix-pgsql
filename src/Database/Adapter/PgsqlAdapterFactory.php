<?php

declare(strict_types=1);

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Database\Adapter\Pdo\PdoAdapter;
use Phoenix\Database\Adapter\Pdo\PdoAdapterFactory;

final class PgsqlAdapterFactory extends PdoAdapterFactory
{
    public const string ADAPTER_NAME = 'pgsql';

    public function getAdapterName(): string
    {
        return self::ADAPTER_NAME;
    }

    protected function createAdapter(PDO $pdo, ?string $version): PdoAdapter
    {
        return new PgsqlAdapter($pdo, $version);
    }
}
