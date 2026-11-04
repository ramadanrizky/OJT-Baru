<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationFlag extends Model
{
    protected $table = 'migration_flags';

    protected $primaryKey = 'module';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = ['module', 'driver'];

    public static function driverFor(string $module): string
    {
        $driver = self::query()->whereKey($module)->value('driver');

        return $driver ?: config("migration.modules.{$module}", 'flask');
    }
}
