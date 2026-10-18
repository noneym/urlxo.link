<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.auth')]
#[Title('Create account')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $accept_terms = false;

    public function mount(): void
    {
        abort_unless(config('urlxo.registration_enabled'), 403, 'Registration is closed at the moment.');
    }

    public function register()
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'accept_terms' => ['accepted'],
        ], ['accept_terms.accepted' => 'Please accept the Terms of Service and Privacy Policy.']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        Auth::login($user, true);
        session()->regenerate();

        return redirect()->route('links.index');
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
