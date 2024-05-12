<?php

namespace Smile00112\SpreadsheetsDataImport\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SpreadsheetImport extends Model
{
    protected $table = 'spreadsheet_imports';
    protected $fillable = [
        'tab_name', 'data', 'resource_type', 'resource_id'
    ];
    protected $casts = [
        'data' => 'array',
    ];
    /**
     * Получить родительскую модель (пользователя или поста), к которой относится изображение.
     */
    public function resource(): MorphTo
    {
        return $this->morphTo();
    }
}
