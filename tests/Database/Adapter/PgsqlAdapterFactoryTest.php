<?php

declare(strict_types=1);

namespace Phoenix\Tests\Database\Adapter;

use Phoenix\Config\EnvironmentConfig;
use Phoenix\Database\Adapter\AdapterFactory;
use Phoenix\Database\Adapter\PgsqlAdapter;
use Phoenix\Database\Adapter\PgsqlAdapterFactory;
use Phoenix\Database\QueryBuilder\PgsqlQueryBuilder;
use Phoenix\Exception\InvalidArgumentValueException;
use PHPUnit\Framework\TestCase;

final class PgsqlAdapterFactoryTest extends TestCase
{
    public function testIsRegisteredByBootstrap(): void
    {
        $this->assertContains('pgsql', AdapterFactory::getAdapterNames());
    }

    public function testAdapterName(): void
    {
        $this->assertSame('pgsql', (new PgsqlAdapterFactory())->getAdapterName());
    }

    public function testCreate(): void
    {
        $config = new EnvironmentConfig([
            'adapter' => 'pgsql',
            'dsn' => 'sqlite::memory:',
        ]);
        $adapter = AdapterFactory::instance($config);
        $this->assertInstanceOf(PgsqlAdapter::class, $adapter);
        $this->assertInstanceOf(PgsqlQueryBuilder::class, $adapter->getQueryBuilder());
        $this->assertSame('utf8', $adapter->getCharset());
    }

    public function testUnknown(): void
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Unknown adapter "unknown".');
        AdapterFactory::instance(new EnvironmentConfig(['adapter' => 'unknown', 'dsn' => 'sqlite::memory:']));
    }
}
