<?php

namespace Barth\SimpleConfigBundle\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ConfigService
{
    /**
     * @var string
     */
    private $projectDir;
    /**
     * @var string
     */
    private $overrideDir;

    public function __construct(
        string $projectDir,
        string $overrideDir
    ) {
        $this->projectDir = $projectDir;
        $this->overrideDir = $overrideDir;
    }

    public function saveNewConfig(string $package, array $config): void
    {
        $fs = new Filesystem();
        $packageOverrideFile = $this->getOverridePackagePath() . \DIRECTORY_SEPARATOR . $package . '.yaml';
        $config = $this->parseConfig($config);

        $fs->dumpFile($packageOverrideFile, Yaml::dump([$package => $config], 4));
    }

    public function parseConfig(array $config): array
    {
        foreach ($config as $key => $value) {
            if (\strpos('-', $key)) {
                unset($config[$key]);
                $key = \str_replace('.', '-', $key);
                $config[$key] = $value;
            }
            if (\strpos($key, ':')) {
                $this->unflattenArray($config, $key, $value);
                unset($config[$key]);
            }
        }
        return $config;
    }

    public function isOverrideConfigForPackageExist(string $package): bool
    {
        $fs = new Filesystem();
        $overridePackagePath = $this->getOverridePackagePath();
        if (!$fs->exists($overridePackagePath)) {
            return false;
        }
        $finder = new Finder();
        $finder->in($overridePackagePath)->name($package . '*');

        return $finder->count() > 0;
    }

    /**
     * Big thank to https://stackoverflow.com/a/39118759
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param mixed  $value
     */
    protected function unflattenArray(array &$array, string $key, $value, string $separator = ':'): array
    {
        if (null === $key) {
            return $array = $value;
        }

        $keys = \explode($separator, $key);

        while (\count($keys) > 1) {
            $key = \array_shift($keys);

            if (!isset($array[$key]) || !\is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[\array_shift($keys)] = $value;

        return $array;
    }

    protected function getOverridePackagePath(): string
    {
        $configPath = $this->projectDir . \DIRECTORY_SEPARATOR . 'config/packages';
        $overridePackagePath = $configPath . \DIRECTORY_SEPARATOR . $this->overrideDir;

        return $overridePackagePath;
    }
}
