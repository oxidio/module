<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Framework\Mixin;

trait Context
{
    use BasisContext;

    public function getCurrentShopId(): int
    {
        return $this->contextProxy->getCurrentShopId();
    }

    public function getLogLevel(): string
    {
        return $this->contextProxy->getLogLevel();
    }

    public function getLogFilePath(): string
    {
        return $this->contextProxy->getLogFilePath();
    }

    public function getRequiredContactFormFields(): array
    {
        return $this->contextProxy->getRequiredContactFormFields();
    }

    public function getConfigurationEncryptionKey(): string
    {
        return $this->contextProxy->getConfigurationEncryptionKey();
    }

    public function isEnabledAdminQueryLog(): bool
    {
        return $this->contextProxy->isEnabledAdminQueryLog();
    }

    public function isAdmin(): bool
    {
        return $this->contextProxy->isAdmin();
    }

    public function getAdminLogFilePath(): string
    {
        return $this->contextProxy->getAdminLogFilePath();
    }

    public function getSkipLogTags(): array
    {
        return $this->contextProxy->getSkipLogTags();
    }

    public function getAdminUserId(): string
    {
        return $this->contextProxy->getAdminUserId();
    }
}
