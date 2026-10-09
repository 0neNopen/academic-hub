<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailTest extends TestCase
{
    public function test_mail_test_command_sends_raw_email(): void
    {
        config(['mail.default' => 'array']);

        $this->artisan('mail:test', ['email' => 'mahasiswa@example.com'])
            ->expectsOutputToContain('Email uji coba BERHASIL dikirim ke mahasiswa@example.com!')
            ->assertExitCode(0);
    }

    public function test_mail_test_command_validates_invalid_email(): void
    {
        $this->artisan('mail:test', ['email' => 'bukan-email'])
            ->assertExitCode(1);
    }
}
