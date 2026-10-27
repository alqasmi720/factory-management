<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateOwner extends Command
{
    protected $signature = 'factory:create-owner {email} {name}';
    protected $description = 'Create the first administrator account using a password prompt';
    public function handle(): int
    {
        if (!DB::table('users')->where('role','Administrator / Owner')->exists()) { }
        $password=$this->secret('Choose a password (minimum 12 characters)');
        if (!$password || strlen($password)<12) { $this->error('Password must contain at least 12 characters.'); return self::FAILURE; }
        $email=$this->argument('email');
        if (DB::table('users')->where('email',$email)->exists()) { $this->error('That email address is already registered.'); return self::FAILURE; }
        DB::table('users')->insert(['name'=>$this->argument('name'),'email'=>$email,'password'=>Hash::make($password),'role'=>'Administrator / Owner','active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $this->info('Owner account created. Sign in at /login.'); return self::SUCCESS;
    }
}
