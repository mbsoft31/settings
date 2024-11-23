<?php

namespace MBsoft\Settings\Enums;

enum ConfigFormat: string
{
    case PHP = 'php';
    case JSON = 'json';
    case YAML = 'yaml';
}
