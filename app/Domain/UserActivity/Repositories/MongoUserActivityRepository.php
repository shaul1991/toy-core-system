<?php

declare(strict_types=1);

namespace App\Domain\UserActivity\Repositories;

use App\Domain\UserActivity\Contracts\UserActivityRepositoryInterface;
use App\Domain\UserActivity\DTOs\UserActivityDTO;
use App\Shared\Database\MongoDB\MongoConnection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;

final class MongoUserActivityRepository implements UserActivityRepositoryInterface
{
    private const COLLECTION = 'user_activities';

    public function __construct(
        private readonly MongoConnection $connection,
    ) {}

    public function find(string $id): ?UserActivityDTO
    {
        if (! $this->isValidObjectId($id)) {
            return null;
        }

        $document = $this->connection->findOne(self::COLLECTION, [
            '_id' => new ObjectId($id),
        ]);

        return $document ? UserActivityDTO::fromDocument($document) : null;
    }

    public function findByUserId(int $userId, int $page = 1, int $perPage = 15): array
    {
        return $this->findAll(['user_id' => $userId], $page, $perPage);
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $mongoFilters = $this->buildFilters($filters);
        $skip = ($page - 1) * $perPage;

        $documents = $this->connection->query(self::COLLECTION, $mongoFilters, [
            'sort' => ['created_at' => -1],
            'skip' => $skip,
            'limit' => $perPage,
        ]);

        $total = $this->connection->count(self::COLLECTION, $mongoFilters);

        $data = array_map(
            fn (object $doc) => UserActivityDTO::fromDocument($doc),
            $documents
        );

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function create(UserActivityDTO $dto): UserActivityDTO
    {
        $document = $dto->toDocument();
        $id = $this->connection->insert(self::COLLECTION, $document);

        return $this->find($id) ?? throw new \RuntimeException('Failed to retrieve created activity');
    }

    public function update(string $id, UserActivityDTO $dto): ?UserActivityDTO
    {
        if (! $this->isValidObjectId($id)) {
            return null;
        }

        $document = $dto->toDocument();
        unset($document['_id'], $document['created_at']);

        $modified = $this->connection->update(
            self::COLLECTION,
            ['_id' => new ObjectId($id)],
            ['$set' => $document]
        );

        if ($modified === 0) {
            $exists = $this->find($id);
            if (! $exists) {
                return null;
            }
        }

        return $this->find($id);
    }

    public function delete(string $id): bool
    {
        if (! $this->isValidObjectId($id)) {
            return false;
        }

        $deleted = $this->connection->delete(self::COLLECTION, [
            '_id' => new ObjectId($id),
        ]);

        return $deleted > 0;
    }

    public function deleteByUserId(int $userId): int
    {
        return $this->connection->delete(
            self::COLLECTION,
            ['user_id' => $userId],
            limit: false
        );
    }

    public function countByAction(int $userId): array
    {
        $pipeline = [
            ['$match' => ['user_id' => $userId]],
            ['$group' => [
                '_id' => '$action',
                'count' => ['$sum' => 1],
            ]],
        ];

        $results = $this->connection->aggregate(self::COLLECTION, $pipeline);

        $counts = [];
        foreach ($results as $result) {
            $counts[$result->_id] = (int) $result->count;
        }

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildFilters(array $filters): array
    {
        $mongoFilters = [];

        if (isset($filters['user_id'])) {
            $mongoFilters['user_id'] = (int) $filters['user_id'];
        }

        // Exact action match takes precedence over action_like
        if (isset($filters['action'])) {
            $mongoFilters['action'] = $filters['action'];
        } elseif (isset($filters['action_like'])) {
            // Escape regex special characters to prevent ReDoS/injection
            $escapedPattern = preg_quote($filters['action_like'], '/');
            $mongoFilters['action'] = new Regex($escapedPattern, 'i');
        }

        if (isset($filters['target_type'])) {
            $mongoFilters['target_type'] = $filters['target_type'];
        }

        if (isset($filters['target_id'])) {
            $mongoFilters['target_id'] = $filters['target_id'];
        }

        // Validate and parse date filters safely
        if (isset($filters['from_date'])) {
            $timestamp = strtotime($filters['from_date']);
            if ($timestamp !== false) {
                $mongoFilters['created_at']['$gte'] = new \MongoDB\BSON\UTCDateTime(
                    (new \DateTime)->setTimestamp($timestamp)
                );
            }
        }

        if (isset($filters['to_date'])) {
            $timestamp = strtotime($filters['to_date']);
            if ($timestamp !== false) {
                $mongoFilters['created_at']['$lte'] = new \MongoDB\BSON\UTCDateTime(
                    (new \DateTime)->setTimestamp($timestamp)
                );
            }
        }

        return $mongoFilters;
    }

    private function isValidObjectId(string $id): bool
    {
        return preg_match('/^[a-f\d]{24}$/i', $id) === 1;
    }
}
