<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportExportSetting extends Model
{
    protected $fillable = [
        'file_format',
        'separator'
    ];

    // Common file format constants
    public const FORMAT_CSV = 'csv';
    public const FORMAT_EXCEL = 'excel';
    public const FORMAT_XML = 'xml';
    public const FORMAT_JSON = 'json';

    // Common separator constants
    public const SEPARATOR_COMMA = ',';
    public const SEPARATOR_SEMICOLON = ';';
    public const SEPARATOR_TAB = '\t';
    public const SEPARATOR_PIPE = '|';
} 