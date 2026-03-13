<?php

namespace mindtwo\LaravelPlatformManager;

use mindtwo\LaravelPlatformManager\Models\Platform as PlatformModel;

class Platform
{
    private ?PlatformModel $model = null;

    private ?string $resolverName = null;

    /** @var array<string> Merged platform base scopes + any extra scopes. */
    private array $effectiveScopes = [];

    /** @param array<string> $scopes */
    public function set(PlatformModel $platform, string $resolver, array $scopes = []): void
    {
        $this->model = $platform;
        $this->resolverName = $resolver;
        $this->effectiveScopes = array_values(array_unique(array_merge($platform->scopes ?? [], $scopes)));
        $this->applyConfigOverrides();
    }

    /**
     * Returns true when the scope is granted by the platform baseline or
     * widened by the resolved token.
     */
    public function can(string $scope): bool
    {
        return in_array($scope, $this->effectiveScopes);
    }

    /**
     * Restore from DB by PK — used by HasPlatformContext in queued jobs.
     */
    public function restoreFromId(int|string $id): void
    {
        $model = config('platform.model', PlatformModel::class)::findOrFail($id);
        $this->set($model, 'queue-restore');
    }

    public function isResolved(): bool
    {
        return $this->model !== null;
    }

    public function get(): ?PlatformModel
    {
        return $this->model;
    }

    public function resolver(): ?string
    {
        return $this->resolverName;
    }

    /**
     * Read platform settings with dot notation: setting('mail.from')
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->model?->setting($key, $default) ?? $default;
    }

    /**
     * Temporarily switch to a different platform for the duration of the callback,
     * then restore the previous platform context.
     */
    public function use(PlatformModel $platform, callable $callback): mixed
    {
        $previousModel = $this->model;
        $previousResolver = $this->resolverName;

        try {
            $this->set($platform, 'override');

            return $callback($platform);
        } finally {
            $this->model = $previousModel;
            $this->resolverName = $previousResolver;
        }
    }

    /**
     * Persist the current platform's PK to the session.
     * Optionally accepts a model to set() and save in one call:
     *
     *   platform()->saveToSession($selected);        // short form
     *   platform()->set($selected, 'admin');         // equivalent long form
     *   platform()->saveToSession();
     *
     * @throws \LogicException when no platform is resolved and no model is passed
     */
    public function saveToSession(?PlatformModel $platform = null): void
    {
        if ($platform !== null) {
            $this->set($platform, 'session');
        }

        if ($this->model === null) {
            throw new \LogicException('Cannot save to session: no platform is resolved.');
        }

        session()->put(config('platform.session_key', 'platform_id'), $this->model->getKey());
    }

    /**
     * Remove the platform selection from the session (e.g. on logout or platform switch).
     */
    public function clearFromSession(): void
    {
        session()->forget(config('platform.session_key', 'platform_id'));
    }

    /**
     * Serialize for queue: only store PK, re-fetch on restore.
     */
    /** @return array<string, mixed> */
    public function serializeForQueue(): array
    {
        return [
            'platform_id' => $this->model?->getKey(),
            'resolver'    => $this->resolverName,
        ];
    }

    /**
     * Proxy property reads to the model.
     */
    public function __get(string $name): mixed
    {
        return $this->model?->{$name};
    }

    public function __isset(string $name): bool
    {
        return $this->model !== null && isset($this->model->{$name});
    }

    private function applyConfigOverrides(): void
    {
        $overrides = $this->model?->setting('config');
        if (is_array($overrides) && count($overrides) > 0) {
            config($overrides);
        }
    }
}
