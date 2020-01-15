<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli\Db;

use Php;
use Oxidio\Core;
use Php\Cli\IO;

class Define
{
    /**
     * @var iterable|callable
     */
    private $versions;

    public function __construct($versions)
    {
        $this->versions = $versions;
    }

    /**
     * @param IO            $io
     * @param Core\Database $db
     * @param string|null   $filter
     * @param bool          $dryRun
     * @param bool          $down
     */
    public function __invoke(IO $io, Core\Database $db, string $filter = null, bool $dryRun = false, bool $down = false)
    {
        $versions = Php::isCallable($this->versions) ? call_user_func($this->versions) : $this->versions;
        $versions = Php::traverse($versions, static function ($version, $name) use ($io, $filter) {
            if ($filter && stripos($name, $filter) === false) {
                $io->isVerbose() && $io->note("filter: $name");
                return null;
            }
            return $version;
        });
        $diff = (new Core\DataDefine($db, $versions))->diff($down, static function (string $name) use ($io) {
            $io->title($name);
        });
        foreach ($diff($dryRun) as $sql => $count) {
            $io->writeln(Php::str("$sql: %s", json_encode($count)));
        }
    }
}
