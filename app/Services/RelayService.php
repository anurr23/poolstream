<?php

namespace App\Services;

use App\Models\Table;

class RelayService
{
    /**
     * Kirim perintah on/off ke script relay hardware USB HID.
     */
    public static function control(int $channel, string $state): void
    {
        $scriptPath = base_path('app/Services/relay_controller.py');
        $pyCmd = 'py';
        $channelArg = escapeshellarg($channel);
        $stateArg = escapeshellarg($state);
        $scriptPathArg = escapeshellarg($scriptPath);

        $command = escapeshellcmd("$pyCmd $scriptPathArg $channelArg $stateArg");
        shell_exec($command);
    }

    /**
     * Set status lampu meja (update DB + trigger hardware relay).
     */
    public static function setLight(Table $table, bool $on): void
    {
        $table->is_light_on = $on;
        $table->save();

        self::control($table->relay_channel, $on ? 'on' : 'off');
    }

    /**
     * Toggle status lampu meja secara manual/test.
     */
    public static function toggle(Table $table): bool
    {
        $newState = ! (bool) $table->is_light_on;
        self::setLight($table, $newState);

        return $newState;
    }
}
