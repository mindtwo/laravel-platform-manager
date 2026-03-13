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
     * data (e.g. config overrides stored under 'config') round-trips safely.
     *
     * @var array<string, mixed>
     */
    protected array $overflow = [];

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
