<?php

namespace App\Livewire\Storefront\Shared;

use Livewire\Attributes\On;
use Livewire\Component;

class Toast extends Component
{
    public array $messages = [];

    #[On('toast')]
    public function push(string $type, string $message): void
    {
        $this->messages[] = ['id' => uniqid(), 'type' => $type, 'message' => $message];
    }

    public function dismiss(string $id): void
    {
        $this->messages = array_values(array_filter($this->messages, fn ($m) => $m['id'] !== $id));
    }

    public function render()
    {
        return view('livewire.storefront.shared.toast');
    }
}
