<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli\Shop;

use Closure;
use Oxidio;
use Php;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

class OptionProvider
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var InputOption
     */
    private $option;

    public function __construct(string $name = 'shop')
    {
        $this->name = $name;

        if ($urls = implode(' | ', Php::keys(Oxidio\Core\Shop::urls()))) {
            $urls = "[ $urls ]";
        }
        $this->option = new InputOption(
            $name,
            null,
            InputOption::VALUE_REQUIRED,
            "Shop url 'mysql://<user>:<pass>@<host>:3306/<db>'" .
            "\nor entries from the .env file 'OXIDIO_SHOP_*' {$urls}"
        );

    }

    public function addTo(Command ...$commands): void
    {
        foreach ($commands as $command) {
            $command->getDefinition()->addOption($this->option);
        }
    }

    public function getFactory(): Closure
    {
        return function (Php\Cli\IO $io): Oxidio\Core\Shop {
            return Oxidio\Core\Shop::get($io->get($this->name, null));
        };
    }
}
