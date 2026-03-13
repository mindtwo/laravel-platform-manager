<?php

namespace mindtwo\LaravelPlatformManager\Settings;

class PlatformSettings
{
    /**
     * Properties listed here are encrypted before being stored and decrypted
     * after being loaded. Use the property name, not a dot-notation path.
     *
     * @var array<string>
     */
    protected array $encrypted = [];

    /**
     * Holds JSON keys that have no matching declared property, so arbitrary
     * data round-trips safely.
     *
     * @var array<string, mixed>
     */
    protected array $overflow = [];

    /**
     * Maps settings keys to Laravel config paths. Each entry will be injected
     * into the application config when the platform is resolved.
     *
     * Example:
     *   protected array $configKeys = [
     *       'mail_host' => 'mail.mailers.smtp.host',
     *       'app_name'  => 'app.name',
     *   ];
     *
     * @var array<string, string>
     */
    protected array $configKeys = [];

    /**
     * Hydrate from a raw (storage) array. Encrypted fields are decrypted.
     */
    public static function fromArray(array $data): static
    {
        $instance = new static;

        foreach ($data as $key => $value) {
            if ($key === 'encrypted' || $key === 'overflow') {
                continue;
            }

            if (property_exists($instance, $key)) {
                $instance->{$key} = in_array($key, $instance->encrypted)
                    ? decrypt($value)
                    : $value;
            } else {
                $instance->overflow[$key] = $value;
            }
        }

        return $instance;
    }

    /**
     * Build the config overrides array for injection into the Laravel config.
     * Only entries defined in $configKeys with a non-null value are included.
     *
     * @return array<string, mixed>
     */
    public function configOverrides(): array
    {
        $overrides = [];

        foreach ($this->configKeys as $settingsKey => $configPath) {
            if (property_exists($this, $settingsKey)) {
                $value = $this->{$settingsKey};
            } elseif (array_key_exists($settingsKey, $this->overflow)) {
                $value = $this->overflow[$settingsKey];
            } else {
                continue;
            }

            if ($value !== null) {
                $overrides[$configPath] = $value;
            }
        }

        return $overrides;
    }

    /**
     * Return all values as a plain array (decrypted). Suitable for reading
     * and dot-notation access via data_get().
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $props = [];

        foreach ((new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $props[$prop->getName()] = $this->{$prop->getName()};
        }

        return array_merge($props, $this->overflow);
    }

    /**
     * Return the array for JSON storage. Declared encrypted properties are
     * encrypted; overflow values are stored as-is.
     *
     * @return array<string, mixed>
     */
    public function toStorageArray(): array
    {
        $props = [];

        foreach ((new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $name = $prop->getName();
            $value = $this->{$name};

            if ($value === null) {
                continue;
            }

            $props[$name] = in_array($name, $this->encrypted) ? encrypt($value) : $value;
        }

        return array_merge($props, $this->overflow);
    }
}
