<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('academy:make-admin {email : E-mail do usuário} {--name=Administrador : Nome usado se o usuário ainda não existir}')]
#[Description('Promove um usuário a administrador, criando-o se necessário')]
class MakeAdminCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = Str::lower((string) $this->argument('email'));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->components->error("E-mail inválido: {$email}");

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $user->forceFill(['role' => UserRole::Admin])->save();

            $this->components->info("{$email} agora é administrador.");

            return self::SUCCESS;
        }

        $password = Str::password(20);

        User::query()->create([
            'name' => (string) $this->option('name'),
            'email' => $email,
            'password' => $password,
        ])->forceFill([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ])->save();

        $this->components->info("Administrador {$email} criado.");
        $this->components->warn("Senha temporária (troque no primeiro acesso): {$password}");

        return self::SUCCESS;
    }
}
