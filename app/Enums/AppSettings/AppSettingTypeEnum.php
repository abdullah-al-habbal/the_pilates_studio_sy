<?php
declare(strict_types=1);
namespace App\Enums\AppSettings;
enum AppSettingTypeEnum: string
{
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case NUMBER = 'number';
    case IMAGE = 'image';
    case JSON = 'json';
    case HEX_COLOR = 'hex_color';
    public function label(): string
    {
        return match($this) {
            self::STRING => 'String',
            self::BOOLEAN => 'Boolean',
            self::NUMBER => 'Number',
            self::IMAGE => 'Image',
            self::JSON => 'JSON',
            self::HEX_COLOR => 'Hex Color',
        };
    }
    public function color(): string
    {
        return match($this) {
            self::STRING => 'primary',
            self::BOOLEAN => 'warning',
            self::NUMBER => 'info',
            self::IMAGE => 'success',
            self::JSON => 'gray',
            self::HEX_COLOR => 'danger',
        };
    }
}
