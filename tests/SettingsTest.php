<?php

use MBsoft\Settings\Enums\ConfigFormat;
use MBsoft\Settings\Exceptions\FileDoesNotExistException;
use MBsoft\Settings\Exceptions\InvalidConfigurationException;
use MBsoft\Settings\Settings;

beforeEach(function () {
    $this->fixturePath = __DIR__.'/fixtures';
    if (! is_dir($this->fixturePath)) {
        mkdir($this->fixturePath, 0777, true);
    }
});

afterEach(function () {
    // Clean up fixture files after each test
    $files = glob($this->fixturePath.'/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    if (is_dir($this->fixturePath)) {
        rmdir($this->fixturePath);
    }
});

it('can retrieve a value by key', function () {
    $settings = new Settings(['app.name' => 'Settings app']);
    expect($settings->get('app.name'))->toBe('Settings app');
});

it('returns default value when key does not exist', function () {
    $settings = new Settings;
    expect($settings->get('nonexistent', 'default'))->toBe('default');
});

it('can set a value by key', function () {
    $settings = new Settings;
    $settings->set('app.version', '1.0');
    expect($settings->get('app.version'))->toBe('1.0');
});

it('throws an exception when setting a value on immutable settings', function () {
    $settings = new Settings([], true); // Immutable
    $settings->set('app.version', '1.0');
})->throws(RuntimeException::class);

it('can check if a key exists', function () {
    $settings = new Settings(['app.name' => 'Settings app']);
    expect($settings->has('app.name'))->toBeTrue()
        ->and($settings->has('nonexistent'))->toBeFalse();
});

it('can remove a key', function () {
    $settings = new Settings(['app.name' => 'Settings app']);
    expect($settings->remove('app.name'))->toBeTrue()
        ->and($settings->has('app.name'))->toBeFalse();
});

it('can retrieve all settings', function () {
    $settings = new Settings(['app.name' => 'Settings app', 'app.version' => '1.0']);
    expect($settings->all())->toMatchArray([
        'app.name' => 'Settings app',
        'app.version' => '1.0',
    ]);
});

it('can validate a value before setting it', function () {
    $settings = new Settings;
    $settings->addValidator('age', fn ($value) => is_int($value) && $value > 0);

    $settings->set('age', 30);
    expect($settings->get('age'))->toBe(30);

    $settings->set('age', -5);
})->throws(InvalidArgumentException::class);

it('can retrieve keys', function () {
    $settings = new Settings(['app.name' => 'Settings app', 'app.version' => '1.0']);
    expect($settings->keys())->toMatchArray(['app.name', 'app.version']);
});

it('can load settings from an array', function () {
    $settings = Settings::fromArray(['app.name' => 'Settings app']);
    expect($settings->get('app.name'))->toBe('Settings app');
});

it('can load settings from a PHP array file', /**
 * @throws InvalidConfigurationException
 * @throws FileDoesNotExistException
 */ function () {
    $path = __DIR__.'/fixtures/settings.php';
    file_put_contents($path, "<?php return ['app.name' => 'Settings app'];");

    $settings = Settings::fromPhpArrayFile($path);
    expect($settings->get('app.name'))->toBe('Settings app');

    unlink($path);
});

it('throws an exception when loading from a non-existent file', /**
 * @throws InvalidConfigurationException
 * @throws FileDoesNotExistException
 */ function () {
    Settings::fromPhpArrayFile('/invalid/path');
})->throws(FileDoesNotExistException::class);

it('can save and load settings as JSON', /**
 * @throws JsonException
 */ function () {
    $path = __DIR__.'/fixtures/settings.json';

    $settings = new Settings(['app.name' => 'Settings app']);
    $settings->saveToFile($path, ConfigFormat::JSON);

    $loadedSettings = Settings::loadFromFile($path, ConfigFormat::JSON);
    expect($loadedSettings->get('app.name'))->toBe('Settings app');

    unlink($path);
});

it('can save and load settings as PHP', /**
 * @throws JsonException
 */ function () {
    $path = __DIR__.'/fixtures/settings.php';

    $settings = new Settings(['app.name' => 'Settings app']);
    $settings->saveToFile($path, ConfigFormat::PHP);

    $loadedSettings = Settings::loadFromFile($path, ConfigFormat::PHP);
    expect($loadedSettings->get('app.name'))->toBe('Settings app');

    unlink($path);
});

it('supports dot notation for nested keys', function () {
    $settings = new Settings(['app' => ['name' => 'Settings app']]);
    expect($settings->get('app.name'))->toBe('Settings app');
    $settings->set('app.version', '1.0');
    expect($settings->get('app.version'))->toBe('1.0');
});

it('can get a scoped configuration value', function () {
    $settings = new Settings(['app' => ['name' => 'MyApp']]);
    expect($settings->getScoped('app', 'name'))->toBe('MyApp')
        ->and($settings->getScoped('app', 'nonexistent', 'default'))->toBe('default');
});

it('can set a scoped configuration value', function () {
    $settings = new Settings(['app' => ['name' => 'MyApp']]);
    $settings->setScoped('app', 'version', '1.0');
    expect($settings->getScoped('app', 'version'))->toBe('1.0');
});

it('can check if a scoped configuration key exists', function () {
    $settings = new Settings(['app' => ['name' => 'MyApp']]);
    expect($settings->hasScoped('app', 'name'))->toBeTrue()
        ->and($settings->hasScoped('app', 'nonexistent'))->toBeFalse();
});

it('can cache a configuration value', function () {
    $settings = new Settings(['app' => ['name' => 'MyApp']]);
    expect($settings->getCached('app.name'))->toBe('MyApp')
        ->and($settings->getCached('app.nonexistent', 'default'))->toBe('default');
});

it('can retrieve a typed configuration value', function () {
    $settings = new Settings(['value' => '123', 'flag' => '1', 'name' => 'test']);
    expect($settings->getTyped('value', 'int'))->toBe(123)
        ->and($settings->getTyped('flag', 'bool'))->toBeTrue()
        ->and($settings->getTyped('name', 'string'))->toBe('test')
        ->and($settings->getTyped('nonexistent', 'int', 0))->toBe(0);
});

it('can create settings from environment variables', function () {
    putenv('APP_NAME=MyApp');
    putenv('APP_VERSION=1.0');

    $settings = Settings::fromEnvironment(['name', 'version'], 'APP_');
    expect($settings->get('name'))->toBe('MyApp')
        ->and($settings->get('version'))->toBe('1.0')
        ->and($settings->get('nonexistent', 'default'))->toBe('default');
});

//
it('creates configuration from a Closure', function () {
    $settings = Settings::from(fn () => ['key' => 'value'], false);
    expect($settings->get('key'))->toBe('value');
});

it('throws exception if Closure does not return an array', function () {
    Settings::from(fn () => 'invalid', false);
})->throws(InvalidConfigurationException::class);

it('creates configuration from an array', function () {
    $settings = Settings::from(['key' => 'value'], true);
    expect($settings->get('key'))->toBe('value');
});

it('creates configuration from a PHP array file', function () {
    $path = __DIR__.'/fixtures/settings.php';
    file_put_contents($path, "<?php return ['key' => 'value'];");

    $settings = Settings::from($path, true);
    expect($settings->get('key'))->toBe('value');

    unlink($path);
});

it('throws exception for invalid source', function () {
    Settings::from('nonexistent.php', false);
})->throws(InvalidConfigurationException::class);

//
it('removes a value with dot notation', function () {
    $data = ['parent' => ['child' => 'value']];
    $settings = new Settings($data);

    $result = $settings->remove('parent.child');
    expect($result)->toBeTrue();
    expect($settings->all())->toBe(['parent' => []]);
});

it('does not remove a non-existent value', function () {
    $data = ['parent' => ['child' => 'value']];
    $settings = new Settings($data);

    $result = $settings->remove('parent.nonexistent');
    expect($result)->toBeFalse();
});

it('serializes settings to YAML', function () {
    if (! function_exists('yaml_emit')) {
        $this->markTestSkipped('YAML support is not enabled.');
    }

    $settings = new Settings(['key' => 'value']);
    $yaml = $settings->serializeToYaml();

    expect($yaml)->toContain('key: value');
});

it('throws exception if YAML support is not enabled', function () {
    if (function_exists('yaml_emit')) {
        $this->markTestSkipped('YAML support is enabled.');
    }

    $settings = new Settings(['key' => 'value']);
    $settings->serializeToYaml();
})->throws(RuntimeException::class);

it('deserializes YAML to an array', function () {
    if (! function_exists('yaml_parse')) {
        $this->markTestSkipped('YAML support is not enabled.');
    }

    $yaml = "key: value\n";
    $data = Settings::deserializeFromYaml($yaml);

    expect($data)->toBe(['key' => 'value']);
});

it('throws exception if YAML support is not enabled for deserialize', function () {
    if (function_exists('yaml_parse')) {
        $this->markTestSkipped('YAML support is enabled.');
    }

    Settings::deserializeFromYaml("key: value\n");
})->throws(RuntimeException::class);
