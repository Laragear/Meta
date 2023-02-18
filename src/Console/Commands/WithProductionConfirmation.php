<?php

namespace Laragear\Meta\Console\Commands;

use RuntimeException;
use function sprintf;

/**
 * @deprecated Use `\Illuminate\Console\ConfirmableTrait` instead.
 * @see \Illuminate\Console\ConfirmableTrait
 */
trait WithProductionConfirmation
{
    /**
     * When called, confirms the execution on production environment.
     *
     * @param  string  $ask
     * @return bool
     */
    protected function confirmOnProduction(string $ask = 'The app is on production environment. Proceed?'): bool
    {
        return ! $this->getLaravel()->environment($this->productionEnvironmentName()) || $this->confirm($ask);
    }

    /**
     * When called, blocks the execution on production environment.
     *
     * @param  string  $block
     * @return void
     */
    protected function blockOnProduction(string $block = 'Cannot run %s command in production environment.'): void
    {
        if ($this->getLaravel()->environment($this->productionEnvironmentName())) {
            throw new RuntimeException(sprintf($block, $this->getName()));
        }
    }

    /**
     * Returns the name of the production environment.
     *
     * @return string
     */
    protected function productionEnvironmentName(): string
    {
        return 'production';
    }
}
