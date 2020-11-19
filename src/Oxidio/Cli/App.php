<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Cli;


use OxidEsales\EshopCommunity\Internal\Framework\Console\CommandsProvider\CommandsProviderInterface;
use Php;
use Oxidio;

class App extends Php\Cli
{
    private const AVATAR = <<<EOL

                  _     __
      ____  _  __(_)___/ /
     / __ \| |/_/ / __  / 
    / /_/ />  </ / /_/ / _     
    \____/_/|_/_/\__,_/ (_)___ 
                       / / __ \
                      / / /_/ /   
                     /_/\____/

EOL;

    public function __construct(CommandsProviderInterface $provider)
    {
        parent::__construct(static::di(Php\VENDOR\OXIDIO\OXIDIO, Oxidio::di(), [
            Oxidio\Core\Shop::class => ($shop = new Oxidio\Cli\Shop\OptionProvider())->getFactory(),
        ]));
        $this->setVersion($this->getVersion() . self::AVATAR);
        $this->addCommands($provider->getCommands());
        $shop->addTo($this->command('io:meta:tables', new Meta\Tables(), ['dir']));
    }

    public function getCommands(): iterable
    {
        yield 'io:shop:info' => new Shop\Info();
        yield 'io:shop:generate' => new Shop\Generate();
        yield 'io:setup:views' => new Setup\Views();
        yield 'io:meta:theme' => new Meta\Theme();
    }
}
