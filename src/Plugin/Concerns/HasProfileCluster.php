<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use Rawilk\ProfileFilament\Filament\Pages\Profile\ProfileInfo;

trait HasProfileCluster
{
    protected string $profileClusterSlug = 'profile';

    /**
     * This is the page to redirect to when the root cluster url is visited.
     */
    protected ?string $defaultProfilePage = ProfileInfo::class;

    /**
     * A configuration key of the default profile page to redirect to.
     */
    protected ?string $defaultProfilePageConfiguration = null;

    public function profileCluster(string $slug): static
    {
        $this->profileClusterSlug = $slug;

        return $this;
    }

    public function getProfileClusterSlug(): string
    {
        return $this->profileClusterSlug;
    }

    public function useDefaultProfilePage(?string $defaultPage = ProfileInfo::class, ?string $configuration = null): static
    {
        $this->defaultProfilePage = $defaultPage;
        $this->defaultProfilePageConfiguration = $configuration;

        return $this;
    }

    public function getDefaultProfilePageUrl(): ?string
    {
        if (blank($this->defaultProfilePage)) {
            return null;
        }

        return $this->defaultProfilePage::getUrl(configuration: $this->defaultProfilePageConfiguration);
    }
}
