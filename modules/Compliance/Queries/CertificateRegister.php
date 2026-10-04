<?php

declare(strict_types=1);

namespace Modules\Compliance\Queries;

use App\Models\User;
use App\Queries\ClubWorkers;
use Illuminate\Support\Collection;
use Modules\Compliance\Models\Certificate;
use Modules\Compliance\Models\CertificateStatus;

/**
 * Every worker in the club with their certificates and their worst status.
 *
 * Workers come from Core's ClubWorkers query, so a coach with no certificate
 * at all still appears, as "missing". Absence is the most important gap.
 */
final class CertificateRegister
{
    public const array FILTERS = ['attention', 'expired', 'expiring', 'missing', 'all'];

    public function __construct(private readonly ClubWorkers $workers) {}

    /**
     * @return list<array{id: int, name: string, status: string, certificates: list<array<string, mixed>>}>
     */
    public function workers(string $filter): array
    {
        $certificates = Certificate::query()->orderBy('expires_on')->get()->groupBy('user_id');

        $rows = $this->workers->query()->get()->map(function (User $worker) use ($certificates) {
            /** @var Collection<int, Certificate> $held */
            $held = $certificates->get($worker->id, collect());

            $status = $held->isEmpty()
                ? CertificateStatus::Missing
                : $held->map(fn (Certificate $certificate) => $certificate->status())
                    ->sortBy(fn (CertificateStatus $status) => $status->rank())
                    ->first();

            return [
                'id' => $worker->id,
                'name' => $worker->name,
                'status' => $status,
                'certificates' => array_values($held->map($this->certificateRow(...))->all()),
            ];
        });

        $rows = $rows->filter(fn (array $row) => match ($filter) {
            'attention' => $row['status'] !== CertificateStatus::Valid,
            'all' => true,
            default => $row['status']->value === $filter,
        });

        return array_values($rows->sortBy(fn (array $row) => [$row['status']->rank(), $row['name']])
            ->map(fn (array $row) => [...$row, 'status' => $row['status']->value])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function heldBy(User $worker): array
    {
        return array_values(Certificate::query()
            ->where('user_id', $worker->id)
            ->orderBy('expires_on')
            ->get()
            ->map($this->certificateRow(...))
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function certificateRow(Certificate $certificate): array
    {
        return [
            'id' => $certificate->id,
            'type' => $certificate->type,
            'reference' => $certificate->reference,
            'issued_on' => $certificate->issued_on->toDateString(),
            'expires_on' => $certificate->expires_on->toDateString(),
            'status' => $certificate->status()->value,
        ];
    }
}
