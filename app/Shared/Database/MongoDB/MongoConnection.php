<?php

declare(strict_types=1);

namespace App\Shared\Database\MongoDB;

use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use RuntimeException;

final class MongoConnection
{
    private ?Manager $manager = null;

    private string $database;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        string $database,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly ?string $authSource = null,
    ) {
        $this->database = $database;
    }

    public static function fromConfig(?string $connection = 'mongodb'): self
    {
        $config = config("database.connections.{$connection}");

        if (! $config) {
            throw new RuntimeException("MongoDB connection [{$connection}] not configured.");
        }

        return new self(
            host: $config['host'] ?? '127.0.0.1',
            port: (int) ($config['port'] ?? 27017),
            database: $config['database'] ?? 'admin',
            username: $config['username'] ?? null,
            password: $config['password'] ?? null,
            authSource: $config['options']['authSource'] ?? 'admin',
        );
    }

    public function getManager(): Manager
    {
        if ($this->manager === null) {
            $this->manager = new Manager($this->buildUri());
        }

        return $this->manager;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * Execute a query on a collection
     *
     * @return array<int, object>
     */
    public function query(string $collection, array $filter = [], array $options = []): array
    {
        $query = new Query($filter, $options);
        $cursor = $this->getManager()->executeQuery(
            "{$this->database}.{$collection}",
            $query
        );

        return $cursor->toArray();
    }

    /**
     * Insert a document into a collection
     */
    public function insert(string $collection, array $document): string
    {
        $bulk = new BulkWrite;
        $id = $bulk->insert($document);
        $this->getManager()->executeBulkWrite("{$this->database}.{$collection}", $bulk);

        return (string) $id;
    }

    /**
     * Update documents in a collection
     */
    public function update(string $collection, array $filter, array $update, bool $multi = false, bool $upsert = false): int
    {
        $bulk = new BulkWrite;
        $bulk->update($filter, $update, ['multi' => $multi, 'upsert' => $upsert]);
        $result = $this->getManager()->executeBulkWrite("{$this->database}.{$collection}", $bulk);

        return $result->getModifiedCount();
    }

    /**
     * Delete documents from a collection
     */
    public function delete(string $collection, array $filter, bool $limit = true): int
    {
        $bulk = new BulkWrite;
        $bulk->delete($filter, ['limit' => $limit ? 1 : 0]);
        $result = $this->getManager()->executeBulkWrite("{$this->database}.{$collection}", $bulk);

        return $result->getDeletedCount();
    }

    /**
     * Execute a database command
     *
     * @throws RuntimeException When command returns empty result
     */
    public function command(array $command): object
    {
        $cmd = new Command($command);
        $cursor = $this->getManager()->executeCommand($this->database, $cmd);
        $result = current($cursor->toArray());

        if ($result === false) {
            $commandName = array_key_first($command) ?? 'unknown';
            throw new RuntimeException(
                "MongoDB command '{$commandName}' returned empty result on database '{$this->database}'"
            );
        }

        return $result;
    }

    /**
     * Count documents in a collection
     */
    public function count(string $collection, array $filter = []): int
    {
        $result = $this->command([
            'count' => $collection,
            'query' => $filter,
        ]);

        return (int) ($result->n ?? 0);
    }

    /**
     * Find one document
     */
    public function findOne(string $collection, array $filter): ?object
    {
        $results = $this->query($collection, $filter, ['limit' => 1]);

        return $results[0] ?? null;
    }

    /**
     * Aggregate pipeline
     *
     * @return array<int, object>
     */
    public function aggregate(string $collection, array $pipeline): array
    {
        $result = $this->command([
            'aggregate' => $collection,
            'pipeline' => $pipeline,
            'cursor' => new \stdClass,
        ]);

        return $result->cursor->firstBatch ?? [];
    }

    private function buildUri(): string
    {
        $uri = 'mongodb://';

        if ($this->username && $this->password) {
            $uri .= urlencode($this->username).':'.urlencode($this->password).'@';
        }

        $uri .= "{$this->host}:{$this->port}";

        if ($this->authSource !== null && $this->authSource !== '') {
            $uri .= '/?authSource='.rawurlencode($this->authSource);
        }

        return $uri;
    }
}
