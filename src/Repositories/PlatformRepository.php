<?php

namespace mindtwo\LaravelPlatformManager\Repositories;

use Chiiya\Common\Repositories\AbstractRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use mindtwo\LaravelPlatformManager\Models\AuthToken;
use mindtwo\LaravelPlatformManager\Models\Platform as PlatformModel;

/**
 * @extends AbstractRepository<PlatformModel>
 */
class PlatformRepository extends AbstractRepository
{
    protected string $model = PlatformModel::class;

    public function __construct()
    {
        $this->model = config('platform.model', PlatformModel::class);
        parent::__construct();
    }

    // -------------------------------------------------------------------------
    // Overrides
    // -------------------------------------------------------------------------

    /**
     * @return array<string>
     */
    protected function searchableFields(): array
    {
        return ['hostname', 'context', 'uuid'];
    }

    /**
     * Supported parameters:
     *   - is_active (bool) — filter by active state
     *   - hostname  (string) — exact or wildcard match
     *   - context   (string) — exact match
     *
     * @param  Builder<PlatformModel>  $builder
     * @return Builder<PlatformModel>
     */
    protected function applyFilters(Builder $builder, array $parameters): Builder
    {
        if (isset($parameters['is_active'])) {
            $builder->where('is_active', $parameters['is_active']);
        }

        if (isset($parameters['hostname'])) {
            $builder->byHostname($parameters['hostname']);
        }

        if (isset($parameters['context'])) {
            $builder->byContext($parameters['context']);
        }

        return $builder;
    }

    // -------------------------------------------------------------------------
    // Queries returning collections
    // -------------------------------------------------------------------------

    /**
     * All active platforms.
     *
     * @return Collection<int, PlatformModel>
     */
    public function allActive(): Collection
    {
        /** @var Collection<int, PlatformModel> */
        return $this->index(['is_active' => true]);
    }

    // -------------------------------------------------------------------------
    // Resolver lookups — request-aware
    // -------------------------------------------------------------------------

    /**
     * Resolve a platform from the token header, returning the platform and its
     * effective scopes (platform baseline merged with token scopes).
     *
     * @return array{0: PlatformModel, 1: array<string>}|null
     */
    public function resolveByToken(Request $request): ?array
    {
        $token = $request->header(config('platform.header_names.token', 'X-Platform-Token'));

        if (! $token && $legacyHeader = config('platform.header_names.token_legacy')) {
            $token = $request->header($legacyHeader);
        }

        if (! is_string($token)) {
            return null;
        }

        return $this->findByTokenWithScopes($token);
    }

    public function resolveByHostname(Request $request): ?PlatformModel
    {
        return $this->findByHostname($request->getHost());
    }

    public function resolveByContext(Request $request): ?PlatformModel
    {
        if (! $context = $request->header('X-Platform-Context')) {
            return null;
        }

        return $this->findByContext($context);
    }

    public function resolveBySession(Request $request): ?PlatformModel
    {
        if (! $id = $request->session()->get(config('platform.session_key', 'platform_id'))) {
            return null;
        }

        return $this->findActiveById($id);
    }

    // -------------------------------------------------------------------------
    // Finder lookups — value-based
    // -------------------------------------------------------------------------

    public function findByHostname(string $hostname): ?PlatformModel
    {
        /** @var PlatformModel|null */
        return $this->newQuery()
            ->isActive()
            ->byHostname($hostname)
            ->first();
    }

    public function findByContext(string $context): ?PlatformModel
    {
        /** @var PlatformModel|null */
        return $this->newQuery()
            ->isActive()
            ->byContext($context)
            ->first();
    }

    public function findActiveById(int $id): ?PlatformModel
    {
        /** @var PlatformModel|null */
        return $this->newQuery()
            ->isActive()
            ->find($id);
    }

    public function findByUuid(string $uuid): ?PlatformModel
    {
        /** @var PlatformModel|null */
        return $this->newQuery()
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Find a platform by raw token value, returning the platform and its
     * effective scopes (platform baseline merged with token scopes).
     *
     * @return array{0: PlatformModel, 1: array<string>}|null
     */
    public function findByTokenWithScopes(string $token): ?array
    {
        $authToken = AuthToken::query()
            ->with('platform')
            ->where('token', $token)
            ->notExpired()
            ->first();

        if ($authToken === null || ! $authToken->platform->is_active) {
            return null;
        }

        $scopes = array_values(array_unique(array_merge(
            $authToken->platform->scopes ?? [],
            $authToken->scopes ?? [],
        )));

        return [$authToken->platform, $scopes];
    }
}
