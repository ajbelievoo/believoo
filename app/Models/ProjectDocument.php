<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectDocument extends Model
{
    protected $fillable = [
        'agreement_id',
        'uploaded_by',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'document_type',
        'visibility',
        'download_count',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'download_count' => 'integer',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            'source_code' => 'Source Code',
            'design' => 'Design Files',
            'documentation' => 'Documentation',
            'apk' => 'Mobile App (APK/IPA)',
            'api_docs' => 'API Documentation',
            'database' => 'Database Files',
            'other' => 'Other',
            default => 'Other',
        };
    }

    public function getDocumentTypeIconAttribute(): string
    {
        return match ($this->document_type) {
            'source_code' => 'fa-code',
            'design' => 'fa-palette',
            'documentation' => 'fa-file-alt',
            'apk' => 'fa-mobile-alt',
            'api_docs' => 'fa-plug',
            'database' => 'fa-database',
            default => 'fa-file',
        };
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }
}
