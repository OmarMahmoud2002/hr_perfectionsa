<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\ImportStatus;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'file_path',
        'month',
        'year',
        'status',
        'records_count',
        'employees_count',
        'error_log',
        'import_settings',
        'uploaded_by',
    ];

    protected $casts = [
        'month'           => 'integer',
        'year'            => 'integer',
        'status'          => ImportStatus::class,
        'records_count'   => 'integer',
        'employees_count' => 'integer',
        'import_settings' => 'array',  // JSON → array تلقائياً
    ];

    // ========================
    // Relationships
    // ========================

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function publicHolidays(): HasMany
    {
        return $this->hasMany(PublicHoliday::class);
    }

    // ========================
    // Helpers
    // ========================

    /**
     * الحصول على إعداد معين من import_settings أو العودة للقيمة الافتراضية
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->import_settings[$key] ?? $default;
    }

    /**
     * اسم الشهر بالعربية
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس',
            4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
            7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر',
            10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return $months[$this->month] ?? '';
    }

    /**
     * هل تمت المعالجة بنجاح؟
     */
    public function isCompleted(): bool
    {
        return $this->status === ImportStatus::Completed;
    }
}
