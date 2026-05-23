<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar_path',
        'password',
        'position',
        'bio',
        'tech_stack',
        'experience',
        'education',
        'github_url',
        'linkedin_url',
        'website_url',
        'twitter_url',
        'instagram_url',
        'facebook_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'tech_stack'        => 'array',
            'experience'        => 'array',
            'education'         => 'array',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    public function getInitialsAttribute(): string
    {
        $name = trim((string) $this->name);
        if ($name === '') {
            return 'U';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $parts = array_values(array_filter($parts, fn ($part) => $part !== ''));

        if (count($parts) === 1) {
            return strtoupper(mb_substr($parts[0], 0, 2));
        }

        return strtoupper(
            mb_substr($parts[0], 0, 1)
            . mb_substr($parts[count($parts) - 1], 0, 1)
        );
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function unreadMessageCount(): int
    {
        return Message::where('receiver_id', $this->id)->whereNull('read_at')->count();
    }

    public function repositories()
    {
        return $this->hasMany(Repository::class, 'owner_id');
    }

    public function repositoryCollaborations()
    {
        return $this->hasMany(RepositoryCollaborator::class);
    }

    public function associates()
    {
        return $this->hasMany(Associate::class, 'user_id');
    }

    public function associatedBy()
    {
        return $this->hasMany(Associate::class, 'associate_user_id');
    }

    public function groupChatMemberships()
    {
        return $this->hasMany(GroupChatMember::class, 'user_id');
    }

    public function groupChats()
    {
        return $this->belongsToMany(GroupChat::class, 'group_chat_members')
            ->withPivot('role', 'last_read_message_id')
            ->withTimestamps();
    }
}
