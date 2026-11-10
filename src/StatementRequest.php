<?php
declare(strict_types=1);

namespace App;

use DateTimeImmutable;
use InvalidArgumentException;

final class StatementRequest
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly DateTimeImmutable $dateFrom,
        public readonly DateTimeImmutable $dateTo,
        public readonly string $createdAt,
    ) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Укажите корректный email.');
        }
        if ($dateFrom > $dateTo) {
            throw new InvalidArgumentException('Начальная дата не может быть позже конечной.');
        }
        if ($dateTo > new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('Конечная дата не может быть в будущем.');
        }
    }

    public static function fromForm(array $input): self
    {
        $from = DateTimeImmutable::createFromFormat('!Y-m-d', (string)($input['date_from'] ?? ''));
        $to = DateTimeImmutable::createFromFormat('!Y-m-d', (string)($input['date_to'] ?? ''));
        if (!$from || !$to) {
            throw new InvalidArgumentException('Укажите обе даты.');
        }
        return new self(bin2hex(random_bytes(8)), trim((string)($input['email'] ?? '')), $from, $to, date(DATE_ATOM));
    }

    public static function fromArray(array $data): self
    {
        return new self($data['id'], $data['email'], new DateTimeImmutable($data['dateFrom']), new DateTimeImmutable($data['dateTo']), $data['createdAt']);
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'email' => $this->email, 'dateFrom' => $this->dateFrom->format('Y-m-d'), 'dateTo' => $this->dateTo->format('Y-m-d'), 'createdAt' => $this->createdAt];
    }
}
