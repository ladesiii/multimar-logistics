<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('usuaris:hash-passwords', function () {
    $users = DB::table('usuaris')->select('id', 'correu', 'contrasenya')->get();
    $updated = 0;

    foreach ($users as $user) {
        $passwordInfo = password_get_info($user->contrasenya);

        if (($passwordInfo['algo'] ?? 0) !== 0) {
            continue;
        }

        DB::table('usuaris')
            ->where('id', $user->id)
            ->update([
                'contrasenya' => Hash::make($user->contrasenya),
            ]);

        $updated++;
        $this->line("Hasheada: {$user->correu}");
    }

    $this->info("Contraseñas actualizadas: {$updated}");
})->purpose('Hash plain-text passwords stored in usuaris');
