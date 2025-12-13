<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories;

use App\Domain\Auth\Contracts\AuthEventRepositoryInterface;
use App\Domain\Auth\DTOs\AuthEventDTO;
use App\Shared\Database\MongoDB\MongoConnection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;

/**
 * MongoDB 인증 이벤트 Repository 구현체
 */
final class MongoAuthEventRepository implements AuthEventRepositoryInterface
{
    private const COLLECTION = 'auth_events';

    public function __construct(
        private readonly MongoConnection $connection,
    ) {}

    public function find(string $id): ?AuthEventDTO
    {
        if (! $this->isValidObjectId($id)) {
            return null;
        }

        $document = $this->connection->findOne(self::COLLECTION, [
            '_id' => new ObjectId($id),
        ]);

        return $document ? AuthEventDTO::fromDocument($document) : null;
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
            fn (object $doc) => AuthEventDTO::fromDocument($doc),
            $documents
        );

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function create(AuthEventDTO $dto): AuthEventDTO
    {
        $document = $dto->toDocument();
        $id = $this->connection->insert(self::COLLECTION, $document);

        return $this->find($id) ?? throw new \RuntimeException('Failed to retrieve created auth event');
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

    public function findRecentLogins(int $userId, int $limit = 10): array
    {
        $documents = $this->connection->query(self::COLLECTION, [
            'user_id' => $userId,
            'action' => AuthEventDTO::ACTION_LOGIN,
            'result' => AuthEventDTO::RESULT_SUCCESS,
        ], [
            'sort' => ['created_at' => -1],
            'limit' => $limit,
        ]);

        return array_map(
            fn (object $doc) => AuthEventDTO::fromDocument($doc),
            $documents
        );
    }

    public function countFailedEvents(int $userId, int $minutes = 60): int
    {
        $since = new UTCDateTime(
            (new \DateTime)->modify("-{$minutes} minutes")
        );

        return $this->connection->count(self::COLLECTION, [
            'user_id' => $userId,
            'result' => AuthEventDTO::RESULT_FAILURE,
            'created_at' => ['$gte' => $since],
        ]);
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

        if (isset($filters['action'])) {
            $mongoFilters['action'] = $filters['action'];
        } elseif (isset($filters['action_like'])) {
            $escapedPattern = preg_quote($filters['action_like'], '/');
            $mongoFilters['action'] = new Regex($escapedPattern, 'i');
        }

        if (isset($filters['result'])) {
            $mongoFilters['result'] = $filters['result'];
        }

        if (isset($filters['provider'])) {
            $mongoFilters['provider'] = $filters['provider'];
        }

        if (isset($filters['from_date'])) {
            $timestamp = strtotime($filters['from_date']);
            if ($timestamp !== false) {
                $mongoFilters['created_at']['$gte'] = new UTCDateTime(
                    (new \DateTime)->setTimestamp($timestamp)
                );
            }
        }

        if (isset($filters['to_date'])) {
            $timestamp = strtotime($filters['to_date']);
            if ($timestamp !== false) {
                $mongoFilters['created_at']['$lte'] = new UTCDateTime(
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
