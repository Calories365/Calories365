<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Something a user sent about a meal that waits for the calculation of the
 * meal draft: a voice message (its words), a typed text or a photo with its
 * caption, see MealDraftService::collect.
 *
 * @property int $id
 * @property int $user_id
 * @property string $kind
 * @property string|null $text
 * @property string|null $photo_path
 * @property string|null $photo_mime
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class MealDraftInput extends Model
{
    public const KIND_VOICE = 'voice';

    public const KIND_TEXT = 'text';

    public const KIND_PHOTO = 'photo';

    /**
     * Inputs one calculation takes at most, photos among them: the model reads
     * them in one request.
     */
    public const MAX_PENDING = 12;

    public const MAX_PHOTOS = 8;

    /**
     * The disk the photos wait on; they never become public.
     */
    public const DISK = 'local';

    protected $fillable = ['user_id', 'kind', 'text', 'photo_path', 'photo_mime'];

    protected static function booted(): void
    {
        static::deleted(static function (self $input) {
            if ($input->photo_path !== null) {
                Storage::disk(self::DISK)->delete($input->photo_path);
            }
        });
    }

    /**
     * The user's inputs waiting for the calculation, oldest first; the ones left
     * longer than a draft lives are deleted with their photos.
     *
     * @return Collection<int, self>
     */
    public static function pendingFor(int $userId): Collection
    {
        /** @var Collection<int, self> $inputs */
        $inputs = self::query()->where('user_id', $userId)->orderBy('id')->get();

        $abandoned = $inputs->filter(static fn (self $input) => $input->created_at->lt(now()->subHours(MealDraft::LIFETIME_HOURS)));
        $abandoned->each->delete();

        return $inputs->diff($abandoned)->values();
    }

    /**
     * The photo itself, or null for a voice message or a text.
     *
     * @return array{mime: string, contents: string}|null
     */
    public function photo(): ?array
    {
        if ($this->photo_path === null) {
            return null;
        }

        $contents = Storage::disk(self::DISK)->get($this->photo_path);

        return $contents === null ? null : ['mime' => (string) $this->photo_mime, 'contents' => $contents];
    }

    /**
     * The input as the bot panel and the site list it.
     *
     * @return array{id: int, kind: string, text: string|null}
     */
    public function toPayload(): array
    {
        return ['id' => $this->id, 'kind' => $this->kind, 'text' => $this->text];
    }
}
