<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Support\Subjects;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Technobase\AuthKit\Contracts\Subjects\SubjectResolver;
use Technobase\AuthKit\Support\Identity\NormalizedIdentity;

final class EloquentSubjectResolver implements SubjectResolver
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function resolve(NormalizedIdentity $identity, string $driver): ?Authenticatable
    {
        $modelClass = $this->config['model'];

        $subject = $modelClass::query()
            ->where('provider', $identity->provider)
            ->where('provider_id', $identity->providerId)
            ->first();

        if ($subject === null && $identity->email !== null) {
            $subject = $this->findByEmail($modelClass, $identity->email);

            if ($subject !== null) {
                $this->linkProvider($subject, $identity);
            }
        }

        if ($subject === null && $identity->phone !== null) {
            $subject = $this->findByPhone($modelClass, $identity->phone);

            if ($subject !== null) {
                $this->linkProvider($subject, $identity);
            }
        }

        if ($subject === null && ($this->config['auto_create_on_social'] ?? false)) {
            $subject = $this->createSubject($modelClass, $identity);
        }

        return $subject;
    }

    private function findByEmail(string $modelClass, string $email): ?Model
    {
        $columns = array_values(array_filter(
            (array) ($this->config['lookup_columns'] ?? ['email']),
            static fn (mixed $column): bool => $column === 'email',
        ));

        if ($columns === []) {
            $columns = ['email'];
        }

        $query = $modelClass::query();

        foreach ($columns as $column) {
            $query->orWhere($column, $email);
        }

        return $query->first();
    }

    private function findByPhone(string $modelClass, string $phone): ?Model
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? $phone;

        return $modelClass::query()->where('phone', $phone)->first();
    }

    private function linkProvider(Model $subject, NormalizedIdentity $identity): void
    {
        $attributes = [
            'provider' => $identity->provider,
            'provider_id' => $identity->providerId,
            'avatar' => $identity->avatar ?? $subject->getAttribute('avatar'),
        ];

        if ($identity->phone !== null) {
            $attributes['phone'] = $identity->phone;
        }

        $subject->forceFill($attributes)->save();
    }

    private function createSubject(string $modelClass, NormalizedIdentity $identity): Model
    {
        $attributes = [
            'provider' => $identity->provider,
            'provider_id' => $identity->providerId,
            'avatar' => $identity->avatar,
        ];

        foreach ((array) ($this->config['lookup_columns'] ?? []) as $column) {
            if ($column === 'email' && $identity->email !== null) {
                $attributes['email'] = $identity->email;
            }

            if ($column === 'phone' && $identity->phone !== null) {
                $attributes['phone'] = $identity->phone;
            }
        }

        if ($identity->phone !== null && ! isset($attributes['phone'])) {
            $attributes['phone'] = $identity->phone;
        }

        if ($identity->email === null && ! isset($attributes['email'])) {
            $attributes['email'] = $identity->provider.'_'.$identity->providerId.'@auth-kit.local';
        }

        if ($identity->name !== null) {
            $nameParts = explode(' ', $identity->name, 2);
            $attributes['first_name'] = $nameParts[0];
            $attributes['last_name'] = $nameParts[1] ?? '';
        }

        return $modelClass::query()->create($attributes);
    }
}
