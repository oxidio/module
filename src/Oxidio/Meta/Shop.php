<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Meta;

use fn;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Facts\Facts;
use OxidEsales\UnifiedNameSpaceGenerator\UnifiedNameSpaceClassMapProvider;
use Oxidio;

/**
 * @property-read ReflectionNamespace $tableNs
 * @property-read ReflectionNamespace|null $fieldNs
 * @property-read ReflectionNamespace $themeNs
 * @property-read EditionClass[] $classes
 * @property-read Table[] $tables
 */
class Shop
{
    use fn\PropertiesTrait\ReadOnly;
    use fn\PropertiesTrait\Init;

    /**
     * @see $tableNs
     * @return ReflectionNamespace
     */
    protected function resolveTableNs(): ReflectionNamespace
    {
        return ReflectionNamespace::get($this->properties['tableNs'] ?? null);
    }

    /**
     * @see $fieldNs
     * @return ReflectionNamespace
     */
    protected function resolveFieldNs(): ?ReflectionNamespace
    {
        $ns = $this->properties['fieldNs'] ?? null;
        return $ns ? ReflectionNamespace::get($ns, ['use' => [substr($this->tableNs, 0, -1)]]) : null;
    }

    /**
     * @see $fieldNs
     * @return ReflectionNamespace
     */
    protected function resolveThemeNs(): ReflectionNamespace
    {
        return ReflectionNamespace::get($this->properties['themeNs'] ?? null);
    }

    /**
     * @see $classes
     * @return EditionClass[]
     */
    public function resolveClasses(): array
    {
        $provider = new UnifiedNameSpaceClassMapProvider(new Facts);
        return fn\keys($provider->getClassMap(), function (string $name) {
            $class = EditionClass::get($name, ['tableNs' => $this->tableNs, 'fieldNs' => $this->fieldNs]);
            return fn\mapKey($name)->andValue($class);
        });
    }

    /**
     * @see $tables
     * @return Table[]
     */
    public function resolveTables(): array
    {
        $base = $this->classes[BaseModel::class] ?? null;
        $tables = Table::cached();
        foreach (Oxidio\db()->tables as $table) {
            $table = $table->getName();
            if (isset($tables[$table])) {
                continue;
            }
            Table::get($table, ['class' => $base]);
        }
        return Table::cached()->traverse;
    }
}
