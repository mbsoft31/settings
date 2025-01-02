<?php

namespace MBsoft\Settings;

use MBsoft\Settings\Commands\SettingsCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SettingsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('settings')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_settings_table')
            ->hasCommand(SettingsCommand::class);
    }
}
