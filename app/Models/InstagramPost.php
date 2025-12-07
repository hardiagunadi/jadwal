<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonImmutable;
use DateTimeZone;
use InvalidArgumentException;

class InstagramPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_type',
        'caption_prompt',
        'generated_caption',
        'scheduled_at',
        'status',
        'storage_path',
        'template_id',
        'ig_publish_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_FAILED => 'Failed',
        ];
    }
    /**
     * @var list<string>
     */
    protected $fillable = [
        'caption',
        'media_path',
        'status',
        'scheduled_at',
        'publish_attempted_at',
        'published_at',
        'failure_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'immutable_datetime',
            'publish_attempted_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
        ];
    }

    public function scopeScheduledAndDue($query)
    {
        return $query
            ->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    public function setScheduledAtAttribute($value): void
    {
        $this->attributes['scheduled_at'] = $this->validateTimezone('scheduled_at', $value);
    }

    public function setPublishAttemptedAtAttribute($value): void
    {
        $this->attributes['publish_attempted_at'] = $this->validateTimezone('publish_attempted_at', $value);
    }

    /**
     * @param mixed $value
     */
    private function validateTimezone(string $attribute, $value): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $hasTimezoneInfo = preg_match('/(Z|[+-]\d{2}:?\d{2})/', $value) === 1
                || preg_match('/[A-Za-z_]+\/[A-Za-z_]+/', $value) === 1
                || in_array($value, DateTimeZone::listIdentifiers(), true);

            if (! $hasTimezoneInfo) {
                throw new InvalidArgumentException(sprintf('The %s value must include timezone information.', $attribute));
            }
        }

        $date = $value instanceof CarbonImmutable
            ? $value
            : CarbonImmutable::parse($value);

        $timezone = $date->getTimezone();

        try {
            new DateTimeZone($timezone->getName());
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException(
                sprintf('The %s value must include a valid timezone: %s', $attribute, $exception->getMessage()),
                previous: $exception,
            );
        }

        return $date;
    }
}
