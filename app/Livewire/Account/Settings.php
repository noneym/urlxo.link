<?php

namespace App\Livewire\Account;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Account and API')]
class Settings extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $tokenName = '';

    public ?string $plainToken = null;

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public function saveProfile(): void
    {
        $user = auth()->user();
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);
        $user->update($data);
        session()->flash('profile', 'Profile saved.');
    }

    public function savePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        auth()->user()->update(['password' => $this->password]);
        Auth::logoutOtherDevices($this->password);
        $this->reset(['current_password', 'password', 'password_confirmation']);
        session()->flash('password', 'Password changed. Other sessions were signed out.');
    }

    public function createToken(): void
    {
        $this->validate(['tokenName' => ['required', 'string', 'max:60']], ['tokenName.required' => 'Give the key a name, like "Zapier" or "CI".']);

        $user = auth()->user();
        if ($user->tokens()->count() >= 20) {
            $this->addError('tokenName', 'You can have up to 20 API keys. Revoke one first.');

            return;
        }

        $this->plainToken = $user->createToken($this->tokenName, ['links:read', 'links:write'])->plainTextToken;
        $this->tokenName = '';
    }

    public function revokeToken(int $id): void
    {
        auth()->user()->tokens()->whereKey($id)->delete();
        $this->plainToken = null;
    }

    public function render()
    {
        return view('livewire.account.settings', [
            'tokens' => auth()->user()->tokens()->latest('id')->get(),
        ]);
    }
}
