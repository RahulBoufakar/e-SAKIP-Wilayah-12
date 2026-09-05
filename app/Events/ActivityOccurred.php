<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityOccurred
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Model  $subject      Model yang jadi objek aktivitas, mis. UsulanProgramKerja
     * @param  string  $description  Deskripsi aksi, mis. "menyetujui Usulan Program Kerja"
     * @param  User|null  $causer   User yang melakukan aksi (null = sistem)
     * @param  iterable<User>  $recipients  User yang perlu menerima notifikasi in-app
     * @param  array  $properties   Data tambahan untuk activity log (opsional)
     */
    public function __construct(
        public Model $subject,
        public string $description,
        public ?User $causer = null,
        public iterable $recipients = [],
        public array $properties = [],
        public ?string $url = null,
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
