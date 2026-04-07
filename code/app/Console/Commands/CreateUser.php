<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    protected $signature = 'user:create';

    protected $description = 'Crea un nuovo utente (gestore o operatore) in modo interattivo';

    public function handle(): int
    {
        $this->info('=== Creazione nuovo utente ===');
        $this->newLine();

        // Ruolo
        $role = $this->choice('Ruolo', ['operatore', 'gestore'], 0);

        // Nome
        $name = $this->askWithValidation('Nome completo', function ($value) {
            if (empty(trim($value))) {
                return 'Il nome non può essere vuoto.';
            }
            return null;
        });

        // Email
        $email = $this->askWithValidation('Email', function ($value) {
            $validator = Validator::make(
                ['email' => $value],
                ['email' => 'required|email|unique:users,email']
            );
            if ($validator->fails()) {
                return $validator->errors()->first('email');
            }
            return null;
        });

        // Password
        $password = $this->askPasswordWithConfirmation();

        // Ore settimanali
        $weeklyHours = (int) $this->askWithValidation(
            'Ore settimanali contrattuali',
            function ($value) {
                if (!is_numeric($value) || (int) $value < 0 || (int) $value > 168) {
                    return 'Inserisci un numero intero tra 0 e 168.';
                }
                return null;
            },
            '40'
        );

        // Colore (solo per operatori, usato nel calendario)
        $color = null;
        if ($role === 'operatore') {
            $color = $this->askWithValidation(
                'Colore nel calendario (hex, es. #3B82F6) — lascia vuoto per nessuno',
                function ($value) {
                    if (empty($value)) {
                        return null;
                    }
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                        return 'Formato non valido. Usa formato esadecimale come #3B82F6';
                    }
                    return null;
                },
                ''
            );
            $color = empty($color) ? null : $color;
        }

        // Riepilogo
        $this->newLine();
        $this->info('Riepilogo:');
        $this->table(
            ['Campo', 'Valore'],
            [
                ['Ruolo',             $role],
                ['Nome',              $name],
                ['Email',             $email],
                ['Ore settimanali',   $weeklyHours],
                ['Colore',            $color ?? '—'],
            ]
        );

        if (!$this->confirm('Confermi la creazione?', true)) {
            $this->warn('Operazione annullata.');
            return self::FAILURE;
        }

        $user = User::create([
            'name'         => $name,
            'email'        => $email,
            'password'     => Hash::make($password),
            'role'         => $role,
            'weekly_hours' => $weeklyHours,
            'color'        => $color,
        ]);

        $this->newLine();
        $this->info("✓ Utente \"{$user->name}\" creato con successo (ID: {$user->id})");

        return self::SUCCESS;
    }

    /**
     * Chiede un valore ripetendo la domanda finché la validazione non passa.
     */
    private function askWithValidation(string $question, callable $validator, ?string $default = null): string
    {
        while (true) {
            $value = $this->ask($question, $default);
            $error = $validator($value ?? '');
            if ($error === null) {
                return $value ?? '';
            }
            $this->error($error);
        }
    }

    /**
     * Chiede la password con conferma, nascosta nell'output.
     */
    private function askPasswordWithConfirmation(): string
    {
        while (true) {
            $password = $this->secret('Password (min. 8 caratteri)');

            if (strlen($password) < 8) {
                $this->error('La password deve essere di almeno 8 caratteri.');
                continue;
            }

            $confirm = $this->secret('Conferma password');

            if ($password !== $confirm) {
                $this->error('Le password non coincidono.');
                continue;
            }

            return $password;
        }
    }
}
