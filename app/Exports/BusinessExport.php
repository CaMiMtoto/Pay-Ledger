<?php

namespace App\Exports;

use App\Models\Business;
use App\Services\BusinessService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\HigherOrderWhenProxy;
use LaravelIdea\Helper\App\Models\_IH_Business_QB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BusinessExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    private BusinessService $service;

    private string $search = '';

    private string $filterStatus = '';

    private string $sortField = '';

    private string $sortDirection = '';

    public function __construct(string $search, string $filterStatus, string $sortField, string $sortDirection)
    {
        $this->search = $search;
        $this->filterStatus = $filterStatus;
        $this->sortField = $sortField;
        $this->sortDirection = $sortDirection;
        $this->service = new BusinessService;
    }

    public function query(): _IH_Business_QB|Relation|\Illuminate\Database\Eloquent\Builder|HigherOrderWhenProxy|Builder|Business
    {
        return $this->service->getListBuilder($this->search, $this->filterStatus, $this->sortField, $this->sortDirection);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Business List';
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->phone,
            $row->subscription_status,
            $row->email,
            $row->address,
            $row->created_at,
        ];
    }

    public function headings(): array
    {
        return [
            ['List of Businesses'],
            [
                'Name',
                'Phone',
                'Subscription Status',
                'Email',
                'Address',
                'Created At',
            ],
        ];
    }

    public function registerEvents(): array
    {
        // make phone number visible in Excel
        return [];
    }
}
